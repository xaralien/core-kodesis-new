/**
 * OCR dokumen umum dengan peningkatan akurasi (Tesseract.js)
 *
 * 1. Cek kualitas foto (buram, resolusi rendah, gelap, kontras rendah) -> peringatan
 * 2. Buat beberapa versi gambar: dinormalisasi (hilangkan bayangan), hitam-putih, asli
 * 3. OCR tiap versi, pilih hasil dengan skor teks-yakin tertinggi
 *    (confidence bawaan tesseract tidak bisa dipakai sendiri: tetap tinggi walau sebagian besar teks hilang)
 * 4. Buang baris noise, kembalikan kata-kata beserta tingkat keyakinannya untuk ditandai
 */
var OcrSmart = (function() {
	var MIN_WIDTH = 1800; // gambar kecil diperbesar agar huruf cukup tinggi untuk tesseract
	var MAX_SCALE = 3;
	var MAX_SIDE = 3500;
	var LOW_CONF = 60; // kata di bawah ini ditandai "ragu"

	// ------------------------------------------------------------------
	// gambar

	function loadImage(file) {
		return new Promise(function(resolve, reject) {
			var url = URL.createObjectURL(file);
			var img = new Image();
			img.onload = function() {
				URL.revokeObjectURL(url);
				resolve(img);
			};
			img.onerror = function() {
				URL.revokeObjectURL(url);
				reject(new Error('Gambar tidak bisa dibaca'));
			};
			img.src = url;
		});
	}

	function loadDataUrl(url) {
		return new Promise(function(resolve, reject) {
			var img = new Image();
			img.onload = function() {
				var c = document.createElement('canvas');
				c.width = img.naturalWidth;
				c.height = img.naturalHeight;
				c.getContext('2d').drawImage(img, 0, 0);
				resolve(c);
			};
			img.onerror = reject;
			img.src = url;
		});
	}

	function drawScaled(img, w, h) {
		var c = document.createElement('canvas');
		c.width = w;
		c.height = h;
		var ctx = c.getContext('2d');
		ctx.imageSmoothingQuality = 'high';
		ctx.drawImage(img, 0, 0, w, h);
		return c;
	}

	function toGray(canvas) {
		var px = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
		var g = new Float32Array(canvas.width * canvas.height);
		for (var i = 0, j = 0; i < px.length; i += 4, j++) {
			g[j] = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
		}
		return g;
	}

	function grayToCanvas(g, w, h) {
		var c = document.createElement('canvas');
		c.width = w;
		c.height = h;
		var ctx = c.getContext('2d');
		var data = ctx.createImageData(w, h);
		for (var i = 0, j = 0; j < g.length; i += 4, j++) {
			var v = g[j] < 0 ? 0 : g[j] > 255 ? 255 : g[j];
			data.data[i] = data.data[i + 1] = data.data[i + 2] = v;
			data.data[i + 3] = 255;
		}
		ctx.putImageData(data, 0, 0);
		return c;
	}

	function percentiles(g, ps) {
		var hist = new Uint32Array(256);
		for (var i = 0; i < g.length; i++) hist[g[i] < 0 ? 0 : g[i] > 255 ? 255 : g[i] | 0]++;
		return ps.map(function(p) {
			var target = g.length * p,
				sum = 0;
			for (var v = 0; v < 256; v++) {
				sum += hist[v];
				if (sum >= target) return v;
			}
			return 255;
		});
	}

	// rata-rata kotak (box blur) memakai integral image
	function boxMean(g, w, h, r) {
		var I = new Float64Array((w + 1) * (h + 1));
		for (var y = 0; y < h; y++) {
			var row = 0;
			for (var x = 0; x < w; x++) {
				row += g[y * w + x];
				I[(y + 1) * (w + 1) + x + 1] = I[y * (w + 1) + x + 1] + row;
			}
		}
		var out = new Float32Array(w * h);
		for (y = 0; y < h; y++) {
			var y0 = Math.max(0, y - r),
				y1 = Math.min(h, y + r + 1);
			for (x = 0; x < w; x++) {
				var x0 = Math.max(0, x - r),
					x1 = Math.min(w, x + r + 1);
				var s = I[y1 * (w + 1) + x1] - I[y0 * (w + 1) + x1] - I[y1 * (w + 1) + x0] + I[y0 * (w + 1) + x0];
				out[y * w + x] = s / ((x1 - x0) * (y1 - y0));
			}
		}
		return out;
	}

	function maxFilter(g, w, h, r) {
		var tmp = new Float32Array(w * h),
			out = new Float32Array(w * h),
			x, y, k, m;
		for (y = 0; y < h; y++) {
			for (x = 0; x < w; x++) {
				m = 0;
				for (k = Math.max(0, x - r); k <= Math.min(w - 1, x + r); k++) m = Math.max(m, g[y * w + k]);
				tmp[y * w + x] = m;
			}
		}
		for (y = 0; y < h; y++) {
			for (x = 0; x < w; x++) {
				m = 0;
				for (k = Math.max(0, y - r); k <= Math.min(h - 1, y + r); k++) m = Math.max(m, tmp[k * w + x]);
				out[y * w + x] = m;
			}
		}
		return out;
	}

	// perkiraan latar (kertas + bayangan) di resolusi kecil:
	// max filter menghapus huruf gelap, lalu dihaluskan
	function estimateBackground(g, w, h) {
		var f = Math.max(1, Math.round(w / 200));
		var sw = Math.ceil(w / f),
			sh = Math.ceil(h / f);
		var small = new Float32Array(sw * sh);
		for (var y = 0; y < sh; y++) {
			for (var x = 0; x < sw; x++) {
				small[y * sw + x] = g[Math.min(h - 1, y * f) * w + Math.min(w - 1, x * f)];
			}
		}
		var bg = boxMean(maxFilter(small, sw, sh, 4), sw, sh, 6);

		// kembalikan ke ukuran penuh (bilinear)
		var out = new Float32Array(w * h);
		for (y = 0; y < h; y++) {
			var fy = Math.min(sh - 1.001, y / f),
				y0 = fy | 0,
				dy = fy - y0;
			for (x = 0; x < w; x++) {
				var fx = Math.min(sw - 1.001, x / f),
					x0 = fx | 0,
					dx = fx - x0;
				var a = bg[y0 * sw + x0],
					b = bg[y0 * sw + x0 + 1],
					c = bg[(y0 + 1) * sw + x0],
					d = bg[(y0 + 1) * sw + x0 + 1];
				out[y * w + x] = (a * (1 - dx) + b * dx) * (1 - dy) + (c * (1 - dx) + d * dx) * dy;
			}
		}
		return out;
	}

	function makeVariants(img) {
		var scale = Math.min(MAX_SCALE, Math.max(1, MIN_WIDTH / img.naturalWidth));
		scale = Math.min(scale, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
		var w = Math.round(img.naturalWidth * scale),
			h = Math.round(img.naturalHeight * scale);
		var base = drawScaled(img, w, h);
		var g = toGray(base),
			i;

		// teks terang di latar gelap -> balik
		if (percentiles(g, [0.5])[0] < 110) {
			for (i = 0; i < g.length; i++) g[i] = 255 - g[i];
		}

		// hilangkan bayangan / pencahayaan tidak rata, lalu regangkan kontras
		var bg = estimateBackground(g, w, h);
		var norm = new Float32Array(g.length);
		for (i = 0; i < g.length; i++) norm[i] = Math.min(255, g[i] / Math.max(bg[i], 1) * 255);
		var p = percentiles(norm, [0.01, 0.6]);
		var range = Math.max(p[1] - p[0], 1);
		for (i = 0; i < norm.length; i++) norm[i] = (norm[i] - p[0]) * 255 / range;

		// hitam-putih adaptif (rata-rata lokal)
		var local = boxMean(norm, w, h, Math.max(8, Math.round(w / 60)));
		var bin = new Float32Array(g.length);
		for (i = 0; i < norm.length; i++) bin[i] = norm[i] < local[i] * 0.85 ? 0 : 255;

		return [{
			name: 'normal',
			canvas: grayToCanvas(norm, w, h)
		}, {
			name: 'hitam-putih',
			canvas: grayToCanvas(bin, w, h)
		}, {
			name: 'asli',
			canvas: base
		}];
	}

	// ------------------------------------------------------------------
	// kualitas foto

	function checkQuality(img) {
		var s = Math.min(1, 1000 / img.naturalWidth);
		var w = Math.round(img.naturalWidth * s),
			h = Math.round(img.naturalHeight * s);
		var g = toGray(drawScaled(img, w, h));
		var warnings = [];

		// ketajaman: variansi laplacian
		var sum = 0,
			sum2 = 0,
			n = 0;
		for (var y = 1; y < h - 1; y++) {
			for (var x = 1; x < w - 1; x++) {
				var i = y * w + x;
				var lap = 4 * g[i] - g[i - 1] - g[i + 1] - g[i - w] - g[i + w];
				sum += lap;
				sum2 += lap * lap;
				n++;
			}
		}
		var sharpness = n ? sum2 / n - Math.pow(sum / n, 2) : 0;
		var p = percentiles(g, [0.02, 0.5, 0.98]);

		if (img.naturalWidth < 800) warnings.push('Resolusi gambar rendah (' + img.naturalWidth + 'px). Gunakan foto/scan minimal 1000px.');
		if (sharpness < 150) warnings.push('Gambar terlihat buram. Pastikan kamera fokus dan tidak goyang.');
		// p[2] < 150: tidak ada area terang sama sekali (bukan sekadar teks putih di latar gelap)
		if (p[1] < 80 && p[2] < 150) warnings.push('Gambar terlalu gelap. Foto di tempat yang lebih terang.');
		if (p[2] - p[0] < 80) warnings.push('Kontras rendah, tulisan kurang jelas dibanding latar.');

		return {
			warnings: warnings,
			sharpness: Math.round(sharpness)
		};
	}

	// ------------------------------------------------------------------
	// hasil

	function alnumLength(s) {
		return (s.match(/[0-9A-Za-zÀ-ɏ]/g) || []).length;
	}

	// jumlah karakter yang terbaca dengan yakin; tinggi jika teks banyak DAN yakin
	function score(data) {
		return (data.words || []).reduce(function(t, w) {
			return t + alnumLength(w.text) * Math.pow(w.confidence / 100, 2);
		}, 0);
	}

	function isNoiseLine(line) {
		var text = line.text.replace(/\s/g, '');
		if (!text) return true;
		var words = line.words || [];
		var avg = words.reduce(function(t, w) {
			return t + w.confidence;
		}, 0) / Math.max(words.length, 1);
		return avg < 40 && alnumLength(text) / text.length < 0.5;
	}

	// coretan/bayangan di tepi sering terbaca sebagai 1-2 karakter acak di awal/akhir baris
	function trimEdgeNoise(words) {
		var isNoise = function(w) {
			return w.confidence < 50 && alnumLength(w.text) <= 2;
		};
		var start = 0,
			end = words.length;
		while (start < end && isNoise(words[start])) start++;
		while (end > start && isNoise(words[end - 1])) end--;
		return words.slice(start, end);
	}

	// susun teks dari paragraf/baris, buang baris noise
	function buildResult(data) {
		var paragraphs = [],
			lines = [],
			words = 0,
			low = 0,
			confSum = 0;

		(data.paragraphs || []).forEach(function(par) {
			var kept = [];
			(par.lines || []).forEach(function(line) {
				if (isNoiseLine(line)) return;
				var lineWords = trimEdgeNoise(line.words || []);
				if (!lineWords.length) return;
				kept.push(lineWords.map(function(w) {
					return w.text;
				}).join(' '));
				lines.push(lineWords.map(function(w) {
					words++;
					confSum += w.confidence;
					if (w.confidence < LOW_CONF) low++;
					return {
						text: w.text,
						conf: Math.round(w.confidence)
					};
				}));
			});
			if (kept.length) paragraphs.push(kept.join('\n'));
		});

		return {
			text: paragraphs.join('\n\n'),
			lines: lines,
			confidence: words ? confSum / words : 0,
			lowWords: low,
			totalWords: words
		};
	}

	/**
	 * options: lang ('ind', 'eng', 'ind+eng'), psm ('auto' atau angka), onProgress(fn)
	 */
	async function recognize(file, options) {
		options = options || {};
		var report = function(label, pct) {
			if (options.onProgress) options.onProgress({
				status: label,
				progress: pct
			});
		};

		var img = await loadImage(file);
		var quality = checkQuality(img);
		var variants = makeVariants(img);
		var autoPsm = !options.psm || options.psm === 'auto';
		var psmList = autoPsm ? ['3'] : [String(options.psm)];

		var worker = await Tesseract.createWorker((options.lang || 'ind+eng').split('+'), 1, {
			logger: function(m) {
				if (m.status !== 'recognizing text' && m.progress !== undefined) report('Memuat engine', m.progress);
			}
		});

		var best = null,
			attempts = [];
		var tries = [];
		variants.forEach(function(v) {
			psmList.forEach(function(psm) {
				tries.push({
					variant: v,
					psm: psm
				});
			});
		});

		async function run(t) {
			report('Percobaan ' + (attempts.length + 1) + ' (' + t.variant.name + ')', attempts.length / (tries.length + 1));
			await worker.setParameters({
				tessedit_pageseg_mode: t.psm
			});
			var r = await worker.recognize(t.variant.canvas, {
				rotateAuto: true
			}, {
				text: true,
				blocks: true
			});
			var s = score(r.data);
			attempts.push({
				variant: t.variant.name,
				psm: t.psm,
				score: Math.round(s)
			});
			if (!best || s > best.score) best = {
				score: s,
				data: r.data,
				variant: t.variant,
				psm: t.psm
			};
		}

		// sudah sangat yakin, tidak perlu mencoba versi lain
		var confident = function() {
			var res = buildResult(best.data);
			return res.totalWords > 3 && res.lowWords / res.totalWords < 0.03 && res.confidence > 90;
		};

		try {
			for (var i = 0; i < tries.length; i++) {
				await run(tries[i]);
				if (confident()) break;
			}

			// mode otomatis: tata letak "satu blok" sering lebih lengkap untuk kartu, formulir & struk
			if (autoPsm && !confident()) {
				await run({
					variant: best.variant,
					psm: '6'
				});
			}
		} finally {
			await worker.terminate();
		}

		var result = buildResult(best.data);
		if (result.totalWords && result.lowWords / result.totalWords > 0.15) {
			quality.warnings.push(result.lowWords + ' dari ' + result.totalWords + ' kata kurang yakin terbaca (ditandai). Periksa kembali atau gunakan foto yang lebih jelas.');
		}

		result.variant = best.variant.name;
		result.psm = best.psm;
		result.warnings = quality.warnings;
		result.attempts = attempts;
		return result;
	}

	return {
		recognize: recognize,
		loadImage: loadImage,
		loadDataUrl: loadDataUrl,
		checkQuality: checkQuality,
		LOW_CONF: LOW_CONF
	};
})();
