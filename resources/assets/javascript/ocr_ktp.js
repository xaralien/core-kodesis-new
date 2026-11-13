/**
 * OCR khusus KTP (Tesseract.js)
 *
 * Butuh ocr_smart.js (helper gambar & cek kualitas).
 *
 * 1. Preprocessing: buang area foto (sisi kanan), perbesar, grayscale, contrast stretch
 * 2. OCR teks utama (bahasa ind, psm 6 = satu blok teks)
 * 3. Baca ulang baris NIK saja dengan whitelist angka (font NIK sering salah terbaca)
 * 4. Parsing per field + koreksi dengan kamus nilai baku KTP
 */
var OcrKtp = (function() {
	var CROP_RATIO = 0.73; // foto KTP mulai di ~71% lebar kartu
	var TARGET_WIDTH = 1600;

	var FIELDS = [
		['provinsi', 'Provinsi'],
		['kota', 'Kabupaten/Kota'],
		['nik', 'NIK'],
		['nama', 'Nama'],
		['tempat_lahir', 'Tempat Lahir'],
		['tgl_lahir', 'Tanggal Lahir'],
		['jenis_kelamin', 'Jenis Kelamin'],
		['gol_darah', 'Gol. Darah'],
		['alamat', 'Alamat'],
		['rt_rw', 'RT/RW'],
		['kel_desa', 'Kel/Desa'],
		['kecamatan', 'Kecamatan'],
		['agama', 'Agama'],
		['status_perkawinan', 'Status Perkawinan'],
		['pekerjaan', 'Pekerjaan'],
		['kewarganegaraan', 'Kewarganegaraan'],
		['berlaku_hingga', 'Berlaku Hingga']
	];

	// pola label (toleran terhadap salah baca umum, mis. Tgl -> Tgi, Berlaku -> Beriaku)
	var LABELS = [
		['nama', /^\W*nam[a4]\b/i],
		['ttl', /^\W*te\w{0,3}at\W{0,2}tg\w{0,2}\W{0,2}la\w*/i],
		['jenis_kelamin', /^\W*jen[i1l]s\s*ke\w*/i],
		['alamat', /^\W*a[l1]am\w*/i],
		['rt_rw', /^\W*rt\s?[\W1Il]{0,3}\s?rw\w*/i],
		['kel_desa', /^\W*ke[il1]\W{0,2}des\w*/i],
		['kecamatan', /^\W*kecam\w*/i],
		['agama', /^\W*aga\w*/i],
		['status_perkawinan', /^\W*stat\w*\s*per\w*/i],
		['pekerjaan', /^\W*peker\w*/i],
		['kewarganegaraan', /^\W*kewarga\w*/i],
		['berlaku_hingga', /^\W*ber[li1]aku\s*\w*/i]
	];

	var DICT = {
		agama: ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU', 'KEPERCAYAAN TERHADAP TUHAN YME'],
		jenis_kelamin: ['LAKI-LAKI', 'PEREMPUAN'],
		status_perkawinan: ['BELUM KAWIN', 'KAWIN', 'CERAI HIDUP', 'CERAI MATI'],
		kewarganegaraan: ['WNI', 'WNA']
	};

	// ------------------------------------------------------------------
	// gambar

	// cari batas kartu dari warna biru KTP, agar latar belakang (meja, dll) terbuang
	function findCardBox(img) {
		var W = img.naturalWidth,
			H = img.naturalHeight;
		var s = Math.min(1, 300 / W);
		var c = document.createElement('canvas');
		c.width = Math.round(W * s);
		c.height = Math.round(H * s);
		var ctx = c.getContext('2d');
		ctx.drawImage(img, 0, 0, c.width, c.height);
		var px = ctx.getImageData(0, 0, c.width, c.height).data;

		var cols = new Array(c.width).fill(0),
			rows = new Array(c.height).fill(0),
			blueTotal = 0;
		for (var y = 0; y < c.height; y++) {
			for (var x = 0; x < c.width; x++) {
				var i = (y * c.width + x) * 4;
				if (px[i + 2] > px[i] + 25 && px[i + 2] > 110) {
					cols[x]++;
					rows[y]++;
					blueTotal++;
				}
			}
		}

		var full = {
			x: 0,
			y: 0,
			w: W,
			h: H
		};
		if (blueTotal < c.width * c.height * 0.15) return full; // bukan KTP biru, pakai gambar utuh

		var x0 = cols.findIndex(function(v) {
			return v > c.height * 0.3;
		});
		var x1 = c.width - 1 - cols.slice().reverse().findIndex(function(v) {
			return v > c.height * 0.3;
		});
		var y0 = rows.findIndex(function(v) {
			return v > c.width * 0.3;
		});
		var y1 = c.height - 1 - rows.slice().reverse().findIndex(function(v) {
			return v > c.width * 0.3;
		});
		if (x0 < 0 || y0 < 0 || (x1 - x0) * (y1 - y0) < c.width * c.height * 0.25) return full;

		return {
			x: Math.round(x0 / s),
			y: Math.round(y0 / s),
			w: Math.round((x1 - x0 + 1) / s),
			h: Math.round((y1 - y0 + 1) / s)
		};
	}

	function preprocess(img) {
		var card = findCardBox(img);
		var srcW = Math.round(card.w * CROP_RATIO);
		var srcH = card.h;
		var scale = TARGET_WIDTH / srcW;
		var canvas = document.createElement('canvas');
		canvas.width = TARGET_WIDTH;
		canvas.height = Math.round(srcH * scale);

		var ctx = canvas.getContext('2d');
		ctx.imageSmoothingQuality = 'high';
		ctx.drawImage(img, card.x, card.y, srcW, srcH, 0, 0, canvas.width, canvas.height);

		var data = ctx.getImageData(0, 0, canvas.width, canvas.height);
		var px = data.data;
		var hist = new Array(256).fill(0);
		var i, g;

		for (i = 0; i < px.length; i += 4) {
			g = Math.round(0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]);
			px[i] = g;
			hist[g]++;
		}

		// contrast stretch persentil 2% - 98%
		var total = px.length / 4;
		var lo = percentile(hist, total, 0.02);
		var hi = percentile(hist, total, 0.98);
		var range = Math.max(hi - lo, 1);

		for (i = 0; i < px.length; i += 4) {
			g = Math.min(255, Math.max(0, (px[i] - lo) * 255 / range));
			px[i] = px[i + 1] = px[i + 2] = g;
		}
		ctx.putImageData(data, 0, 0);
		return canvas;
	}

	function percentile(hist, total, p) {
		var target = total * p,
			sum = 0;
		for (var v = 0; v < 256; v++) {
			sum += hist[v];
			if (sum >= target) return v;
		}
		return 255;
	}

	function cropCanvas(canvas, box, pad, scale) {
		var x = Math.max(0, box.x0 - pad);
		var y = Math.max(0, box.y0 - pad);
		var w = Math.min(canvas.width, box.x1 + pad) - x;
		var h = Math.min(canvas.height, box.y1 + pad) - y;
		var out = document.createElement('canvas');
		out.width = Math.round(w * scale);
		out.height = Math.round(h * scale);
		out.getContext('2d').drawImage(canvas, x, y, w, h, 0, 0, out.width, out.height);
		return out;
	}

	// ------------------------------------------------------------------
	// baca ulang angka (NIK, RT/RW)
	// font angka KTP sering salah terbaca model bahasa, jadi area angkanya
	// dipotong lalu dibaca ulang dengan model eng + whitelist angka

	// gabungan bbox kata-kata berisi angka pada baris yang cocok dengan lineRe
	function findDigitsBox(data, lineRe, wordRe) {
		var lines = data.lines || [];
		for (var i = 0; i < lines.length; i++) {
			if (!lineRe.test(lines[i].text)) continue;

			var box = null;
			(lines[i].words || []).forEach(function(w) {
				if (!wordRe.test(w.text)) return;
				box = box ? {
					x0: Math.min(box.x0, w.bbox.x0),
					y0: Math.min(box.y0, w.bbox.y0),
					x1: Math.max(box.x1, w.bbox.x1),
					y1: Math.max(box.y1, w.bbox.y1)
				} : Object.assign({}, w.bbox);
			});
			if (box) return box;
		}
		return null;
	}

	// accept(text) mengembalikan nilai final, atau null jika hasil belum valid
	async function readDigits(canvas, box, digitWorker, whitelist, accept) {
		if (!box) return null;

		var crop = cropCanvas(canvas, box, 20, 0.6);
		var modes = ['8', '13', '7'];
		for (var i = 0; i < modes.length; i++) {
			await digitWorker.setParameters({
				tessedit_pageseg_mode: modes[i],
				tessedit_char_whitelist: whitelist
			});
			var r = await digitWorker.recognize(crop);
			var value = accept(r.data.text);
			if (value) return value;
		}
		return null;
	}

	function readNik(canvas, data, digitWorker) {
		var box = findDigitsBox(data, /N\s?[I1l|]\s?K/, /[0-9?OoIlSBZGbD]{6,}/);
		return readDigits(canvas, box, digitWorker, '0123456789', function(text) {
			var digits = text.replace(/\D/g, '');
			return digits.length === 16 ? digits : null;
		});
	}

	function readRtRw(canvas, data, digitWorker) {
		var box = findDigitsBox(data, /^\W*RT\s?[\W1Il]{0,3}\s?RW/i, /\d{2,}/);
		return readDigits(canvas, box, digitWorker, '0123456789/', function(text) {
			var nums = text.match(/\d+/g) || [];
			// ':' di depan RT kadang ikut terbaca sebagai digit, ambil 3 digit terakhir
			if (nums.length !== 2 || nums[0].length < 3 || nums[1].length < 3) return null;
			return nums[0].slice(-3) + '/' + nums[1].slice(-3);
		});
	}

	// ------------------------------------------------------------------
	// parsing

	function levenshtein(a, b) {
		var m = a.length,
			n = b.length,
			d = [],
			i, j;
		for (i = 0; i <= m; i++) d[i] = [i];
		for (j = 0; j <= n; j++) d[0][j] = j;
		for (i = 1; i <= m; i++) {
			for (j = 1; j <= n; j++) {
				d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
			}
		}
		return d[m][n];
	}

	// cocokkan ke nilai baku terdekat, jika cukup mirip
	function fromDict(value, options) {
		var v = value.toUpperCase().replace(/[^A-Z\- ]/g, '').trim();
		if (!v) return value;
		var best = null,
			bestDist = Infinity;
		options.forEach(function(opt) {
			// bandingkan dengan awal teks saja, buang noise di belakang
			var dist = levenshtein(v.substr(0, opt.length + 1).trim(), opt);
			if (dist < bestDist) {
				best = opt;
				bestDist = dist;
			}
		});
		return bestDist <= Math.max(1, Math.floor(best.length * 0.4)) ? best : value;
	}

	function digitsFix(s) {
		return s.replace(/[Oo]/g, '0').replace(/[Il|!]/g, '1').replace(/\?/g, '7')
			.replace(/S/g, '5').replace(/B/g, '8').replace(/Z/g, '2').replace(/G/g, '6');
	}

	function findDate(s) {
		var m = digitsFix(s).match(/(\d{2})\s?[-.\/]\s?(\d{2})\s?[-.\/]\s?(\d{4})/);
		return m ? m[1] + '-' + m[2] + '-' + m[3] : '';
	}

	// buang pemisah ':' (sering terbaca '1', 'i', ';', '=') dan noise di ujung
	function cleanValue(s) {
		return s
			.replace(/^[\s:;=.,|!'"#\-—–~]+/, '')
			.replace(/^[1iIl](?=[\s:;])[\s:;]*/, '')
			.replace(/^[:;]\s*/, '')
			.replace(/[\s\-—–=_~|:;.,'"“”]+$/, '')
			.replace(/\s{2,}/g, ' ')
			.trim();
	}

	function parse(text) {
		var f = {};
		FIELDS.forEach(function(x) {
			f[x[0]] = '';
		});

		var lines = text.split('\n').map(function(l) {
			return l.trim();
		}).filter(Boolean);

		lines.forEach(function(line, idx) {
			if (/PROVINSI/i.test(line) && !f.provinsi) {
				f.provinsi = 'PROVINSI ' + cleanValue(line.replace(/^.*PROVINSI/i, '').replace(/[|]/g, ''));
				// baris berikutnya = kabupaten/kota (jika bukan baris NIK)
				var next = lines[idx + 1] || '';
				if (next && !/N\s?[I1l]\s?K/.test(next)) f.kota = cleanValue(next.replace(/[^A-Za-z\s.]/g, ''));
				return;
			}
			if (/^\W*N\s?[I1l|]\s?K\b/.test(line) && !f.nik) {
				var nik = digitsFix(line.replace(/^\W*N\s?[I1l|]\s?K/, '')).replace(/\D/g, '');
				f.nik = nik.length > 16 ? nik.substr(nik.length - 16) : nik;
				return;
			}

			for (var i = 0; i < LABELS.length; i++) {
				var key = LABELS[i][0],
					m = line.match(LABELS[i][1]);
				if (!m) continue;
				var value = cleanValue(line.substr(m[0].length));

				if (key === 'ttl') {
					var date = findDate(value);
					f.tgl_lahir = date;
					f.tempat_lahir = cleanValue(value.split(/[,.]?\s*[0-9OoIl?]{2}\s?[-.\/]/)[0]);
				} else if (key === 'jenis_kelamin') {
					var gol = value.match(/g[o0]l\W{0,2}\s*darah\W*(AB|A|B|O|0|-)?/i);
					if (gol) {
						f.gol_darah = (gol[1] || '-').replace('0', 'O').toUpperCase();
						value = value.substr(0, gol.index);
					}
					f.jenis_kelamin = cleanValue(value);
				} else if (key === 'rt_rw') {
					var nums = digitsFix(value).match(/\d+/g) || [];
					if (nums.length >= 2) {
						// ':' sering terbaca sebagai digit di depan, ambil 3 digit terakhir
						f.rt_rw = nums[0].slice(-3) + '/' + nums[nums.length - 1].slice(-3);
					} else {
						f.rt_rw = value;
					}
				} else if (key === 'berlaku_hingga') {
					f.berlaku_hingga = /seumur/i.test(value) ? 'SEUMUR HIDUP' : (findDate(value) || value);
				} else {
					f[key] = value;
				}
				break;
			}
		});

		Object.keys(DICT).forEach(function(key) {
			if (f[key]) f[key] = fromDict(f[key], DICT[key]);
		});

		return f;
	}

	// cek kesesuaian NIK dengan tanggal lahir & jenis kelamin
	// format NIK: PPKKCC DDMMYY NNNN, DD + 40 untuk perempuan
	function validateNik(f) {
		if (!/^\d{16}$/.test(f.nik)) {
			return {
				valid: false,
				message: 'NIK harus 16 digit (terbaca ' + f.nik.length + ' digit).'
			};
		}
		var m = f.tgl_lahir.match(/^(\d{2})-(\d{2})-\d{2}(\d{2})$/);
		if (!m) return {
			valid: null,
			message: ''
		};

		var day = parseInt(m[1], 10) + (f.jenis_kelamin === 'PEREMPUAN' ? 40 : 0);
		var expected = ('0' + day).slice(-2) + m[2] + m[3];
		if (f.nik.substr(6, 6) !== expected) {
			return {
				valid: false,
				message: 'Digit 7-12 NIK (' + f.nik.substr(6, 6) + ') tidak cocok dengan tanggal lahir/jenis kelamin (' + expected + '). Periksa kembali.'
			};
		}
		return {
			valid: true,
			message: 'NIK sesuai dengan tanggal lahir & jenis kelamin.'
		};
	}

	function format(f) {
		return FIELDS.map(function(x) {
			return x[1] + ' : ' + (f[x[0]] || '-');
		}).join('\n');
	}

	async function recognize(file, onProgress) {
		var logger = function(m) {
			if (onProgress && m.progress !== undefined) onProgress(m);
		};
		var img = await OcrSmart.loadImage(file);
		var quality = OcrSmart.checkQuality(img);
		var canvas = preprocess(img);

		var worker = await Tesseract.createWorker('ind', 1, {
			logger: logger
		});
		var digitWorker = await Tesseract.createWorker('eng', 1);
		try {
			await worker.setParameters({
				tessedit_pageseg_mode: '6'
			});
			// rotateAuto meluruskan foto yang sedikit miring
			var r = await worker.recognize(canvas, {
				rotateAuto: true
			}, {
				text: true,
				blocks: true,
				imageGrey: true
			});
			var fields = parse(r.data.text);

			// koordinat kata mengacu ke gambar yang sudah diluruskan tesseract
			var source = r.data.rotateRadians && r.data.imageGrey ? await OcrSmart.loadDataUrl(r.data.imageGrey) : canvas;

			var nik = await readNik(source, r.data, digitWorker);
			if (nik) fields.nik = nik;

			var rtRw = await readRtRw(source, r.data, digitWorker);
			if (rtRw) fields.rt_rw = rtRw;

			return {
				fields: fields,
				text: format(fields),
				raw: r.data.text,
				confidence: r.data.confidence,
				nik_check: validateNik(fields),
				warnings: quality.warnings
			};
		} finally {
			await worker.terminate();
			await digitWorker.terminate();
		}
	}

	return {
		recognize: recognize,
		parse: parse,
		format: format,
		validateNik: validateNik
	};
})();
