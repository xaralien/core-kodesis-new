<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $this->session->userdata('icon') ?>" type="image/ico" />
    <title><?= $this->session->userdata('nama_singkat') ?> | Capture X-Ray</title>
    <link href="<?= base_url(); ?>src/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url(); ?>src/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #12161c;
            --panel: #1b2129;
            --line: #2c3540;
            --text: #e3e8ee;
            --muted: #8a96a3;
            --accent: #22a6b3;
            --ok: #27ae60;
            --warn: #f39c12;
            --bad: #e74c3c;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
        }

        a {
            color: var(--accent);
        }

        .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-bottom: 1px solid var(--line);
            background: var(--panel);
        }

        .topbar h1 {
            font-size: 17px;
            margin: 0;
            font-weight: 600;
            flex: 1;
        }

        .topbar .user {
            color: var(--muted);
        }

        .wrap {
            padding: 14px 16px;
        }

        .panel-x {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 14px;
        }

        .panel-x h4 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            margin: 0 0 10px;
        }

        .form-control {
            background: #10141a;
            border-color: var(--line);
            color: var(--text);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: none;
        }

        label {
            font-weight: 500;
            color: var(--muted);
            font-size: 12px;
        }

        /* Lebar video dibatasi supaya tingginya selalu muat di layar (tidak perlu scroll) */
        .stage {
            --ar: 1.7778;
            --reserve: 240px;
            position: relative;
            background: #000;
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
            aspect-ratio: var(--ar);
            width: min(100%, calc((100vh - var(--reserve)) * var(--ar)));
            margin: 0 auto;
        }

        .stage:fullscreen {
            width: 100vw;
            height: 100vh;
            aspect-ratio: auto;
            border: 0;
            border-radius: 0;
        }

        .stage video,
        .stage canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .stage #overlay {
            cursor: crosshair;
            touch-action: none;
        }

        .stage .empty {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            gap: 8px;
            text-align: center;
            padding: 20px;
        }

        .stage .empty i {
            font-size: 48px;
            opacity: .5;
        }

        .stage .badge-live {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--bad);
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            display: none;
        }

        .stage .info {
            position: absolute;
            bottom: 8px;
            right: 10px;
            color: #fff;
            background: rgba(0, 0, 0, .55);
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-family: monospace;
        }

        .flash {
            position: absolute;
            inset: 0;
            background: #fff;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
        }

        .flash.on {
            opacity: .7;
            transition: none;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-capture {
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            padding: 9px 22px;
        }

        .btn-capture:hover,
        .btn-capture:focus {
            color: #fff;
            filter: brightness(1.1);
        }

        .btn-dark {
            background: #263039;
            color: var(--text);
            border: 1px solid var(--line);
        }

        .btn-dark:hover,
        .btn-dark:focus {
            color: #fff;
            background: #2f3a45;
        }

        .btn-dark.active {
            background: var(--accent);
            color: #fff;
        }

        kbd {
            background: #000;
            font-size: 10px;
        }

        .quick-wrap {
            display: none;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .quick-wrap input {
            width: 190px;
            text-transform: uppercase;
        }

        .quick-badge {
            background: var(--warn);
            color: #000;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 3px;
        }

        body.panel-hidden .quick-wrap {
            display: flex;
        }

        body.panel-hidden #col-panel {
            display: none;
        }

        .slider-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .slider-row span:first-child {
            width: 72px;
            color: var(--muted);
        }

        .slider-row input {
            flex: 1;
        }

        .slider-row span:last-child {
            width: 38px;
            text-align: right;
            font-family: monospace;
        }

        .shots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 8px;
        }

        .shot {
            position: relative;
            border: 1px solid var(--line);
            border-radius: 4px;
            overflow: hidden;
            background: #000;
            aspect-ratio: 4/3;
        }

        .shot img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            cursor: zoom-in;
        }

        .shot .rm {
            position: absolute;
            top: 3px;
            right: 3px;
            background: rgba(0, 0, 0, .7);
            color: #fff;
            border: 0;
            border-radius: 3px;
            width: 22px;
            height: 22px;
            line-height: 20px;
            padding: 0;
        }

        .shot .no {
            position: absolute;
            bottom: 3px;
            left: 4px;
            font-size: 10px;
            background: rgba(0, 0, 0, .7);
            padding: 0 4px;
            border-radius: 2px;
        }

        .shots-empty {
            color: var(--muted);
            padding: 14px 0;
            text-align: center;
        }

        .hist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }

        .hist-item {
            background: #10141a;
            border: 1px solid var(--line);
            border-radius: 5px;
            overflow: hidden;
        }

        .hist-item .thumb {
            aspect-ratio: 4/3;
            background: #000;
            cursor: zoom-in;
        }

        .hist-item .thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .hist-item .meta {
            padding: 6px 8px;
            font-size: 11px;
        }

        .hist-item .meta b {
            font-size: 12px;
            display: block;
            color: var(--text);
        }

        .hist-item .meta .t {
            color: var(--muted);
        }

        .tag {
            display: inline-block;
            padding: 0 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
        }

        .tag-AMAN {
            background: var(--ok);
        }

        .tag-PERLU_CEK {
            background: var(--warn);
        }

        .tag-DITOLAK {
            background: var(--bad);
        }

        .modal-content {
            background: var(--panel);
            color: var(--text);
            border: 1px solid var(--line);
        }

        .modal-header,
        .modal-footer {
            border-color: var(--line);
        }

        .modal-header .close {
            color: #fff;
            opacity: .8;
        }

        #viewer-img {
            max-width: 100%;
            max-height: 75vh;
            display: block;
            margin: 0 auto;
        }

        .alert-sec {
            background: #3b2a12;
            border: 1px solid var(--warn);
            color: #fbd38d;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            display: none;
        }

        #toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 2000;
            min-width: 220px;
            padding: 10px 14px;
            border-radius: 4px;
            color: #fff;
            display: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .4);
        }

        @media (max-width: 991px) {
            .topbar h1 {
                font-size: 15px;
            }

            .wrap {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <a href="<?= base_url('home') ?>" class="btn btn-dark btn-sm"><i class="fa fa-arrow-left"></i></a>
        <h1></h1>
        <span class="user hidden-xs"><i class="fa fa-user"></i> <?= htmlspecialchars((string) $this->session->userdata('nama')) ?></span>
    </div>

    <div class="wrap">
        <div class="alert-sec" id="secure-warning">
            <i class="fa fa-warning"></i> Browser hanya mengizinkan akses kamera / capture card lewat <b>HTTPS</b> atau <b>localhost</b>.
            Buka halaman ini melalui HTTPS agar capture card dapat dipakai.
        </div>

        <div class="row">
            <!-- KIRI: live preview -->
            <div class="col-md-8" id="col-live">
                <div class="panel-x">
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label>Capture card / sumber video</label>
                            <select id="device" class="form-control input-sm"></select>
                        </div>
                        <div class="col-sm-3 col-xs-6 form-group">
                            <label>Resolusi</label>
                            <select id="resolution" class="form-control input-sm">
                                <option value="1920x1080">1920 × 1080 (FHD)</option>
                                <option value="1280x720">1280 × 720 (HD)</option>
                                <option value="2560x1440">2560 × 1440</option>
                                <option value="3840x2160">3840 × 2160 (4K)</option>
                                <option value="1024x768">1024 × 768 (4:3)</option>
                                <option value="1280x1024">1280 × 1024 (5:4)</option>
                            </select>
                        </div>
                        <div class="col-sm-3 col-xs-6 form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button id="btn-start" class="btn btn-dark btn-sm btn-block"><i class="fa fa-play"></i> Mulai</button>
                            </div>
                        </div>
                    </div>

                    <div class="stage" id="stage">
                        <video id="video" autoplay playsinline muted></video>
                        <canvas id="overlay"></canvas>
                        <div class="empty" id="empty">
                            <i class="fa fa-television"></i>
                            <div>Sambungkan HDMI dari mesin X-Ray ke capture card,<br>colok capture card ke USB / Type-C, lalu klik <b>Mulai</b>.</div>
                        </div>
                        <span class="badge-live" id="badge-live">● LIVE</span>
                        <span class="info" id="stream-info"></span>
                        <div class="flash" id="flash"></div>
                    </div>

                    <div class="toolbar">
                        <button id="btn-capture" class="btn btn-capture" disabled><i class="fa fa-camera"></i> Ambil Gambar <kbd>Spasi</kbd></button>
                        <button id="btn-crop" class="btn btn-dark" disabled title="Tarik kotak di video untuk memotong area gambar X-Ray"><i class="fa fa-crop"></i> Atur Area</button>
                        <button id="btn-crop-reset" class="btn btn-dark" disabled><i class="fa fa-expand"></i> Full Frame</button>
                        <button id="btn-fullscreen" class="btn btn-dark" disabled><i class="fa fa-arrows-alt"></i></button>
                        <button id="btn-stop" class="btn btn-dark" disabled><i class="fa fa-stop"></i> Stop</button>
                        <button id="btn-panel" class="btn btn-dark" title="Sembunyikan / tampilkan panel (H)"><i class="fa fa-columns"></i> <span>Sembunyikan Panel</span> <kbd>H</kbd></button>
                        <div class="quick-wrap">
                            <span class="quick-badge" title="Setiap Ambil Gambar langsung disimpan">SIMPAN LANGSUNG</span>
                            <input type="text" id="quick_ref" class="form-control input-sm" maxlength="50" placeholder="No. SMU (opsional)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- KANAN: pengaturan + form -->
            <div class="col-md-4" id="col-panel">
                <div class="panel-x">
                    <h4>Penyesuaian gambar</h4>
                    <div class="slider-row"><span>Brightness</span><input type="range" id="f-brightness" min="50" max="200" value="100"><span id="v-brightness">100</span></div>
                    <div class="slider-row"><span>Contrast</span><input type="range" id="f-contrast" min="50" max="250" value="100"><span id="v-contrast">100</span></div>
                    <div class="checkbox" style="margin:4px 0">
                        <label><input type="checkbox" id="f-gray"> Grayscale</label>
                        &nbsp;&nbsp;
                        <label><input type="checkbox" id="f-invert"> Invert (negatif)</label>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Format</label>
                            <select id="format" class="form-control input-sm">
                                <option value="image/jpeg">JPG (kecil)</option>
                                <option value="image/png">PNG (lossless)</option>
                            </select>
                        </div>
                        <div class="col-xs-6">
                            <label>Overlay info</label>
                            <select id="stamp" class="form-control input-sm">
                                <option value="1">Tampilkan</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>
                    <button id="btn-reset-filter" class="btn btn-dark btn-xs" style="margin-top:8px"><i class="fa fa-undo"></i> Reset penyesuaian</button>
                </div>

                <div class="panel-x">
                    <h4>Data barang</h4>
                    <form id="form-xray" autocomplete="off">
                        <div class="form-group">
                            <label>No. SMU / AWB / Referensi *</label>
                            <input type="text" name="no_ref" id="no_ref" class="form-control input-sm" required maxlength="50" style="text-transform:uppercase">
                        </div>
                        <div class="form-group">
                            <label>Agent / Pengirim</label>
                            <input type="text" name="nama_agent" class="form-control input-sm" maxlength="150">
                        </div>
                        <div class="row">
                            <div class="col-xs-6 form-group">
                                <label>Koli</label>
                                <input type="number" name="jumlah_koli" class="form-control input-sm" min="0">
                            </div>
                            <div class="col-xs-6 form-group">
                                <label>Berat (kg)</label>
                                <input type="number" name="berat" class="form-control input-sm" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Hasil pemeriksaan</label>
                            <select name="hasil" class="form-control input-sm">
                                <option value="AMAN">AMAN</option>
                                <option value="PERLU_CEK">PERLU CEK FISIK</option>
                                <option value="DITOLAK">DITOLAK</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control input-sm" rows="2"></textarea>
                        </div>
                    </form>

                    <h4 style="margin-top:14px">Gambar diambil (<span id="shot-count">0</span>)</h4>
                    <div class="shots" id="shots"></div>
                    <div class="shots-empty" id="shots-empty">Belum ada gambar. Tekan <kbd>Spasi</kbd> untuk mengambil.</div>

                    <button id="btn-save" class="btn btn-success btn-block" style="margin-top:10px" disabled>
                        <i class="fa fa-save"></i> Simpan Semua <kbd>Ctrl+S</kbd>
                    </button>
                </div>
            </div>
        </div>

        <!-- RIWAYAT -->
        <div class="panel-x">
            <h4>Riwayat capture</h4>
            <div class="row" style="margin-bottom:10px">
                <div class="col-sm-4 col-xs-12 form-group"><input type="text" id="h-q" class="form-control input-sm" placeholder="Cari no. SMU / agent / petugas"></div>
                <div class="col-sm-3 col-xs-6 form-group"><input type="date" id="h-dari" class="form-control input-sm"></div>
                <div class="col-sm-3 col-xs-6 form-group"><input type="date" id="h-sampai" class="form-control input-sm"></div>
                <div class="col-sm-2 col-xs-12 form-group"><button id="h-cari" class="btn btn-dark btn-sm btn-block"><i class="fa fa-search"></i> Cari</button></div>
            </div>
            <div class="hist-grid" id="hist"></div>
            <div class="text-center" style="margin-top:10px">
                <button id="h-more" class="btn btn-dark btn-sm" style="display:none">Muat lebih banyak</button>
                <div id="h-info" style="color:var(--muted);margin-top:6px"></div>
            </div>
        </div>
    </div>

    <!-- Modal viewer -->
    <div class="modal fade" id="viewer" tabindex="-1">
        <div class="modal-dialog modal-lg" style="width:auto;max-width:1200px;margin:20px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="viewer-title">Gambar X-Ray</h4>
                </div>
                <div class="modal-body" style="background:#000"><img id="viewer-img" src="" alt=""></div>
                <div class="modal-footer" id="viewer-foot"></div>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <script src="<?= base_url(); ?>src/vendors/jquery/dist/jquery.min.js"></script>
    <script src="<?= base_url(); ?>src/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script>
        (function() {
            'use strict';

            var URL_SAVE = '<?= site_url('xray/save') ?>';
            var URL_LIST = '<?= site_url('xray/list_data') ?>';
            var URL_DEL = '<?= site_url('xray/delete') ?>/';
            var URL_DL = '<?= site_url('xray/download') ?>/';
            var PETUGAS = <?= json_encode((string) $this->session->userdata('nama')) ?>;
            var PERUSAHAAN = <?= json_encode((string) $this->session->userdata('nama_singkat')) ?>;

            // Nama yang biasa dipakai capture card HDMI murah (Vention, UGREEN, MacroSilicon MS2109/MS2130, dll.)
            var CAPTURE_HINT = /vention|capture|hdmi|usb video|usb3\.0|macrosilicon|ms21\d\d|uvc|cam link|ugreen|video grabber/i;
            var STORE_KEY = 'xray_capture_settings';

            var $ = window.jQuery;
            var video = document.getElementById('video');
            var overlay = document.getElementById('overlay');
            var octx = overlay.getContext('2d');
            var stream = null;
            var shots = []; // {blob, url, w, h}
            var crop = null; // {x,y,w,h} normalisasi 0..1 terhadap frame video
            var cropMode = false;
            var drag = null;

            var settings = loadSettings();

            // ---------- util ----------
            function loadSettings() {
                try {
                    return JSON.parse(localStorage.getItem(STORE_KEY)) || {};
                } catch (e) {
                    return {};
                }
            }

            function saveSettings() {
                if ($('#device').val()) settings.device = $('#device').val();
                settings.resolution = $('#resolution').val();
                settings.format = $('#format').val();
                settings.stamp = $('#stamp').val();
                settings.filter = currentFilter();
                settings.crop = crop;
                try {
                    localStorage.setItem(STORE_KEY, JSON.stringify(settings));
                } catch (e) {}
            }

            function toast(msg, type) {
                var colors = {
                    ok: '#27ae60',
                    err: '#e74c3c',
                    info: '#2c3e50'
                };
                $('#toast').stop(true, true).css('background', colors[type || 'info']).text(msg).fadeIn(150).delay(2600).fadeOut(300);
            }

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [c];
                });
            }

            function pad(n) {
                return (n < 10 ? '0' : '') + n;
            }

            function nowText() {
                var d = new Date();
                return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
            }

            // ---------- device ----------
            if (!window.isSecureContext) $('#secure-warning').show();

            function listDevices() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                    toast('Browser tidak mendukung akses video. Gunakan Chrome / Edge terbaru.', 'err');
                    return Promise.resolve();
                }
                return navigator.mediaDevices.enumerateDevices().then(function(devs) {
                    var cams = devs.filter(function(d) {
                        return d.kind === 'videoinput';
                    });
                    var $sel = $('#device').empty();
                    if (!cams.length) {
                        $sel.append('<option value="">— Capture card tidak terdeteksi —</option>');
                        return;
                    }
                    var preferred = null;
                    cams.forEach(function(c, i) {
                        var label = c.label || ('Video input ' + (i + 1));
                        var isCap = CAPTURE_HINT.test(label);
                        if (isCap && !preferred) preferred = c.deviceId;
                        $sel.append($('<option>').val(c.deviceId).text((isCap ? '★ ' : '') + label));
                    });
                    var saved = settings.device && cams.some(function(c) {
                        return c.deviceId === settings.device;
                    }) ? settings.device : null;
                    $sel.val(saved || preferred || cams[0].deviceId);
                });
            }

            function startStream() {
                stopStream();
                var res = $('#resolution').val().split('x');
                var deviceId = $('#device').val();
                var constraints = {
                    audio: false,
                    video: {
                        width: {
                            ideal: +res[0]
                        },
                        height: {
                            ideal: +res[1]
                        },
                        frameRate: {
                            ideal: 30
                        }
                    }
                };
                if (deviceId) constraints.video.deviceId = {
                    exact: deviceId
                };

                return navigator.mediaDevices.getUserMedia(constraints).then(function(s) {
                    stream = s;
                    video.srcObject = s;
                    $('#empty').hide();
                    $('#badge-live').show();
                    $('#btn-capture,#btn-crop,#btn-crop-reset,#btn-stop,#btn-fullscreen').prop('disabled', false);
                    $('#btn-start').html('<i class="fa fa-refresh"></i> Ganti');

                    // Setelah izin diberikan, label device baru terbaca -> refresh daftar.
                    var activeId = s.getVideoTracks()[0].getSettings().deviceId;
                    listDevices().then(function() {
                        if (activeId) $('#device').val(activeId);
                        saveSettings();
                    });

                    s.getVideoTracks()[0].addEventListener('ended', function() {
                        toast('Capture card terputus. Periksa kabel USB / HDMI.', 'err');
                        stopStream();
                    });
                }).catch(function(err) {
                    var msg = err.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan akses kamera di browser.' :
                        err.name === 'NotFoundError' ? 'Capture card tidak ditemukan.' :
                        err.name === 'NotReadableError' ? 'Capture card sedang dipakai aplikasi lain (OBS, Zoom, dll.). Tutup aplikasi tersebut.' :
                        err.name === 'OverconstrainedError' ? 'Resolusi tidak didukung capture card. Pilih resolusi lain.' :
                        (err.message || err.name);
                    toast(msg, 'err');
                });
            }

            function stopStream() {
                if (stream) stream.getTracks().forEach(function(t) {
                    t.stop();
                });
                stream = null;
                video.srcObject = null;
                $('#empty').show();
                $('#badge-live').hide();
                $('#stream-info').text('');
                $('#btn-capture,#btn-crop,#btn-crop-reset,#btn-stop,#btn-fullscreen').prop('disabled', true);
                $('#btn-start').html('<i class="fa fa-play"></i> Mulai');
                setCropMode(false);
                drawOverlay();
            }

            video.addEventListener('loadedmetadata', function() {
                $('#stream-info').text(video.videoWidth + '×' + video.videoHeight);
                if (video.videoWidth && video.videoHeight) {
                    document.getElementById('stage').style.setProperty('--ar', (video.videoWidth / video.videoHeight).toFixed(4));
                }
                drawOverlay();
            });

            // ---------- filter ----------
            function currentFilter() {
                return {
                    brightness: +$('#f-brightness').val(),
                    contrast: +$('#f-contrast').val(),
                    gray: $('#f-gray').is(':checked'),
                    invert: $('#f-invert').is(':checked')
                };
            }

            function filterString(f) {
                return 'brightness(' + f.brightness + '%) contrast(' + f.contrast + '%)' +
                    (f.gray ? ' grayscale(100%)' : '') + (f.invert ? ' invert(100%)' : '');
            }

            function applyFilter() {
                var f = currentFilter();
                $('#v-brightness').text(f.brightness);
                $('#v-contrast').text(f.contrast);
                video.style.filter = filterString(f);
                saveSettings();
            }

            $('#f-brightness,#f-contrast').on('input', applyFilter);
            $('#f-gray,#f-invert').on('change', applyFilter);
            $('#format,#stamp').on('change', saveSettings);
            $('#btn-reset-filter').on('click', function() {
                $('#f-brightness,#f-contrast').val(100);
                $('#f-gray,#f-invert').prop('checked', false);
                applyFilter();
            });

            // ---------- area crop ----------
            // Rect area video di dalam elemen (object-fit: contain -> ada letterbox)
            function videoRect() {
                var cw = overlay.clientWidth,
                    ch = overlay.clientHeight;
                var vw = video.videoWidth || 16,
                    vh = video.videoHeight || 9;
                var s = Math.min(cw / vw, ch / vh);
                var w = vw * s,
                    h = vh * s;
                return {
                    x: (cw - w) / 2,
                    y: (ch - h) / 2,
                    w: w,
                    h: h
                };
            }

            function drawOverlay() {
                var dpr = window.devicePixelRatio || 1;
                overlay.width = overlay.clientWidth * dpr;
                overlay.height = overlay.clientHeight * dpr;
                octx.setTransform(dpr, 0, 0, dpr, 0, 0);
                octx.clearRect(0, 0, overlay.clientWidth, overlay.clientHeight);
                var c = drag ? drag.rect : crop;
                if (!c || !stream) return;
                var r = videoRect();
                var x = r.x + c.x * r.w,
                    y = r.y + c.y * r.h,
                    w = c.w * r.w,
                    h = c.h * r.h;
                octx.fillStyle = 'rgba(0,0,0,0.55)';
                octx.fillRect(r.x, r.y, r.w, y - r.y);
                octx.fillRect(r.x, y + h, r.w, r.y + r.h - y - h);
                octx.fillRect(r.x, y, x - r.x, h);
                octx.fillRect(x + w, y, r.x + r.w - x - w, h);
                octx.strokeStyle = '#22a6b3';
                octx.lineWidth = 2;
                octx.setLineDash(cropMode ? [6, 4] : []);
                octx.strokeRect(x, y, w, h);
            }

            function pointToNorm(e) {
                var b = overlay.getBoundingClientRect(),
                    r = videoRect();
                var px = (e.clientX - b.left - r.x) / r.w,
                    py = (e.clientY - b.top - r.y) / r.h;
                return {
                    x: Math.min(1, Math.max(0, px)),
                    y: Math.min(1, Math.max(0, py))
                };
            }

            function setCropMode(on) {
                cropMode = on;
                $('#btn-crop').toggleClass('active', on);
                overlay.style.pointerEvents = on ? 'auto' : 'none';
                drawOverlay();
            }

            overlay.style.pointerEvents = 'none';
            overlay.addEventListener('pointerdown', function(e) {
                if (!cropMode) return;
                overlay.setPointerCapture(e.pointerId);
                var p = pointToNorm(e);
                drag = {
                    start: p,
                    rect: {
                        x: p.x,
                        y: p.y,
                        w: 0,
                        h: 0
                    }
                };
            });
            overlay.addEventListener('pointermove', function(e) {
                if (!drag) return;
                var p = pointToNorm(e),
                    s = drag.start;
                drag.rect = {
                    x: Math.min(s.x, p.x),
                    y: Math.min(s.y, p.y),
                    w: Math.abs(p.x - s.x),
                    h: Math.abs(p.y - s.y)
                };
                drawOverlay();
            });
            overlay.addEventListener('pointerup', function() {
                if (!drag) return;
                if (drag.rect.w > 0.03 && drag.rect.h > 0.03) {
                    crop = drag.rect;
                    toast('Area gambar disimpan. Area ini dipakai untuk semua capture berikutnya.', 'ok');
                }
                drag = null;
                setCropMode(false);
                saveSettings();
            });

            $('#btn-crop').on('click', function() {
                setCropMode(!cropMode);
            });
            $('#btn-crop-reset').on('click', function() {
                crop = null;
                setCropMode(false);
                saveSettings();
            });
            window.addEventListener('resize', drawOverlay);

            $('#btn-fullscreen').on('click', function() {
                var el = document.getElementById('stage');
                if (document.fullscreenElement) document.exitFullscreen();
                else if (el.requestFullscreen) el.requestFullscreen();
            });
            document.addEventListener('fullscreenchange', function() {
                setTimeout(drawOverlay, 100);
            });

            // ---------- capture ----------
            function capture() {
                if (!stream || !video.videoWidth) return;
                var vw = video.videoWidth,
                    vh = video.videoHeight;
                var sx = 0,
                    sy = 0,
                    sw = vw,
                    sh = vh;
                if (crop) {
                    sx = Math.round(crop.x * vw);
                    sy = Math.round(crop.y * vh);
                    sw = Math.round(crop.w * vw);
                    sh = Math.round(crop.h * vh);
                }
                var c = document.createElement('canvas');
                c.width = sw;
                c.height = sh;
                var ctx = c.getContext('2d');
                ctx.filter = filterString(currentFilter());
                ctx.drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
                ctx.filter = 'none';

                if ($('#stamp').val() === '1') {
                    var ref = ($('#no_ref').val() || '').toUpperCase();
                    var text = [PERUSAHAAN, ref ? 'REF: ' + ref : '', nowText(), PETUGAS].filter(Boolean).join('  |  ');
                    var fs = Math.max(14, Math.round(sw / 70));
                    ctx.font = 'bold ' + fs + 'px monospace';
                    var tw = ctx.measureText(text).width;
                    ctx.fillStyle = 'rgba(0,0,0,0.6)';
                    ctx.fillRect(0, sh - fs * 1.8, Math.min(sw, tw + fs), fs * 1.8);
                    ctx.fillStyle = '#ffeb3b';
                    ctx.fillText(text, fs / 2, sh - fs * 0.55);
                }

                var type = $('#format').val();
                c.toBlob(function(blob) {
                    if (!blob) return toast('Gagal mengambil gambar', 'err');
                    var shot = {
                        blob: blob,
                        url: URL.createObjectURL(blob),
                        w: sw,
                        h: sh,
                        type: type
                    };
                    if (panelHidden) return quickSave(shot);
                    shots.push(shot);
                    renderShots();
                }, type, 0.92);

                var f = document.getElementById('flash');
                f.classList.add('on');
                setTimeout(function() {
                    f.classList.remove('on');
                }, 60);
            }

            function renderShots() {
                var $s = $('#shots').empty();
                shots.forEach(function(s, i) {
                    $s.append(
                        '<div class="shot"><img src="' + s.url + '" data-i="' + i + '">' +
                        '<button class="rm" data-i="' + i + '" title="Hapus">&times;</button>' +
                        '<span class="no">#' + (i + 1) + ' · ' + s.w + '×' + s.h + '</span></div>'
                    );
                });
                $('#shot-count').text(shots.length);
                $('#shots-empty').toggle(!shots.length);
                $('#btn-save').prop('disabled', !shots.length);
            }

            $('#shots').on('click', '.rm', function() {
                var i = +$(this).data('i');
                URL.revokeObjectURL(shots[i].url);
                shots.splice(i, 1);
                renderShots();
            }).on('click', 'img', function() {
                var s = shots[+$(this).data('i')];
                openViewer(s.url, 'Preview (belum disimpan)', '');
            });

            // ---------- simpan ----------
            var saving = false;

            function deviceName() {
                return $('#device option:selected').text().replace(/^★ /, '');
            }

            function uploadShot(s, meta) {
                var fd = new FormData();
                meta.forEach(function(m) {
                    fd.append(m.name, m.value);
                });
                fd.append('device_name', deviceName());
                fd.append('image', s.blob, 'xray.' + (s.type === 'image/png' ? 'png' : 'jpg'));
                return fetch(URL_SAVE, {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(res) {
                        if (!res.status) throw new Error(res.message || 'Gagal menyimpan');
                        return res;
                    });
            }

            // Mode panel tersembunyi: tiap capture langsung disimpan.
            // No. SMU diambil dari isian; kalau kosong dibuat otomatis AUTO-YYYYMMDD-HHMMSS.
            function quickSave(shot) {
                var noRef = $.trim($('#no_ref').val()).toUpperCase() ||
                    'AUTO-' + nowText().replace(/[-:]/g, '').replace(' ', '-');
                var meta = $('#form-xray').serializeArray().map(function(m) {
                    return m.name === 'no_ref' ? {
                        name: m.name,
                        value: noRef
                    } : m;
                });
                uploadShot(shot, meta).then(function() {
                    toast('Tersimpan: ' + noRef, 'ok');
                    loadHistory(true);
                }).catch(function(err) {
                    // Gagal -> jangan hilang, masukkan ke daftar supaya bisa disimpan ulang
                    shots.push(shot);
                    renderShots();
                    toast('Gagal simpan (' + err.message + '). Gambar dipindah ke daftar "Gambar diambil".', 'err');
                });
            }

            function saveAll() {
                if (saving || !shots.length) return;
                var $f = $('#form-xray');
                var noRef = $.trim($('#no_ref').val()).toUpperCase();
                if (!noRef) {
                    toast('Isi No. SMU / referensi dulu', 'err');
                    $('#no_ref').focus();
                    return;
                }
                $('#no_ref').val(noRef);
                saving = true;
                $('#btn-save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

                var meta = $f.serializeArray();
                var queue = shots.slice();
                var done = 0;

                function next() {
                    if (!queue.length) return Promise.resolve();
                    var s = queue[0];
                    return uploadShot(s, meta)
                        .then(function() {
                            queue.shift();
                            URL.revokeObjectURL(s.url);
                            shots.splice(shots.indexOf(s), 1);
                            done++;
                            return next();
                        });
                }

                next().then(function() {
                    toast(done + ' gambar X-Ray tersimpan untuk ' + noRef, 'ok');
                    $f[0].reset();
                    $('#quick_ref').val('');
                    loadHistory(true);
                    $('#no_ref').focus();
                }).catch(function(err) {
                    toast((done ? done + ' tersimpan, ' : '') + 'error: ' + err.message, 'err');
                }).then(function() {
                    saving = false;
                    renderShots();
                    $('#btn-save').html('<i class="fa fa-save"></i> Simpan Semua <kbd>Ctrl+S</kbd>');
                });
            }

            // ---------- riwayat ----------
            var hPage = 1,
                hItems = {};

            function loadHistory(reset) {
                if (reset) {
                    hPage = 1;
                    $('#hist').empty();
                }
                $.getJSON(URL_LIST, {
                    q: $('#h-q').val(),
                    dari: $('#h-dari').val(),
                    sampai: $('#h-sampai').val(),
                    page: hPage
                }).done(function(res) {
                    if (!res.status) return;
                    res.data.forEach(function(r) {
                        hItems[r.id] = r;
                        $('#hist').append(
                            '<div class="hist-item" id="h-' + r.id + '">' +
                            '<div class="thumb" data-id="' + r.id + '"><img loading="lazy" src="' + esc(r.url) + '"></div>' +
                            '<div class="meta"><b>' + esc(r.no_ref) + '</b>' +
                            '<span class="tag tag-' + esc(r.hasil) + '">' + esc(r.hasil.replace('_', ' ')) + '</span> ' +
                            '<span class="t">' + esc(r.created_at) + '</span><br>' +
                            '<span class="t">' + esc(r.nama_agent || '-') + ' · ' + esc(r.petugas || '') + '</span></div></div>'
                        );
                    });
                    var shown = $('#hist .hist-item').length;
                    $('#h-info').text(res.total ? shown + ' dari ' + res.total + ' gambar' : 'Belum ada data');
                    $('#h-more').toggle(shown < res.total);
                });
            }

            $('#h-cari').on('click', function() {
                loadHistory(true);
            });
            $('#h-q').on('keydown', function(e) {
                if (e.key === 'Enter') loadHistory(true);
            });
            $('#h-more').on('click', function() {
                hPage++;
                loadHistory(false);
            });

            $('#hist').on('click', '.thumb', function() {
                var r = hItems[$(this).data('id')];
                var info = r.no_ref + ' — ' + r.hasil.replace('_', ' ') + ' — ' + r.created_at;
                var foot =
                    '<span class="pull-left" style="color:var(--muted);text-align:left">' +
                    esc(r.nama_agent || '-') + (r.jumlah_koli ? ' · ' + esc(r.jumlah_koli) + ' koli' : '') + (r.berat ? ' · ' + esc(r.berat) + ' kg' : '') +
                    (r.keterangan ? '<br>' + esc(r.keterangan) : '') + '<br>' + esc(r.width + '×' + r.height) + ' · ' + esc(r.device_name || '') + '</span>' +
                    '<a class="btn btn-dark btn-sm" href="' + URL_DL + r.id + '"><i class="fa fa-download"></i> Download</a>' +
                    '<button class="btn btn-danger btn-sm" id="btn-del" data-id="' + r.id + '"><i class="fa fa-trash"></i> Hapus</button>';
                openViewer(r.url, info, foot);
            });

            $('#viewer').on('click', '#btn-del', function() {
                var id = $(this).data('id');
                if (!confirm('Hapus gambar X-Ray ini?')) return;
                $.post(URL_DEL + id).done(function(res) {
                    if (res.status) {
                        $('#h-' + id).remove();
                        $('#viewer').modal('hide');
                        toast('Data dihapus', 'ok');
                    } else toast(res.message, 'err');
                });
            });

            function openViewer(src, title, foot) {
                $('#viewer-img').attr('src', src);
                $('#viewer-title').text(title);
                $('#viewer-foot').html(foot);
                $('#viewer').modal('show');
            }

            // ---------- shortcut ----------
            document.addEventListener('keydown', function(e) {
                var tag = (e.target.tagName || '').toLowerCase();
                var typing = tag === 'input' || tag === 'textarea' || tag === 'select';
                if (e.code === 'Space' && !typing) {
                    e.preventDefault();
                    capture();
                } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    saveAll();
                } else if ((e.key === 'h' || e.key === 'H') && !typing && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    setPanel(!panelHidden);
                } else if (e.key === 'Escape' && cropMode) {
                    setCropMode(false);
                }
            });

            // ---------- panel ----------
            var panelHidden = false;

            function setPanel(hidden) {
                panelHidden = hidden;
                document.body.classList.toggle('panel-hidden', hidden);
                $('#col-live').toggleClass('col-md-8', !hidden).toggleClass('col-md-12', hidden);
                $('#btn-panel span').text(hidden ? 'Tampilkan Panel' : 'Sembunyikan Panel');
                $('#btn-panel').toggleClass('active', hidden);
                $('#quick_ref').val($('#no_ref').val());
                if (hidden && shots.length) toast(shots.length + ' gambar di daftar belum disimpan. Tampilkan panel untuk menyimpannya.', 'info');
                settings.panelHidden = hidden;
                saveSettings();
                setTimeout(drawOverlay, 50);
            }

            $('#btn-panel').on('click', function() {
                setPanel(!panelHidden);
            });
            $('#quick_ref').on('input', function() {
                $('#no_ref').val(this.value);
            });
            $('#quick_ref').on('keydown', function(e) {
                if (e.key === 'Enter') this.blur();
            });
            $('#no_ref').on('input', function() {
                $('#quick_ref').val(this.value);
            });

            // ---------- init ----------
            $('#btn-start').on('click', startStream);
            $('#btn-stop').on('click', stopStream);
            $('#btn-capture').on('click', capture);
            $('#btn-save').on('click', saveAll);
            $('#device,#resolution').on('change', function() {
                saveSettings();
                if (stream) startStream();
            });

            if (settings.resolution) $('#resolution').val(settings.resolution);
            if (settings.format) $('#format').val(settings.format);
            if (settings.stamp) $('#stamp').val(settings.stamp);
            if (settings.filter) {
                $('#f-brightness').val(settings.filter.brightness);
                $('#f-contrast').val(settings.filter.contrast);
                $('#f-gray').prop('checked', settings.filter.gray);
                $('#f-invert').prop('checked', settings.filter.invert);
            }
            crop = settings.crop || null;
            applyFilter();
            if (settings.panelHidden) setPanel(true);
            renderShots();
            loadHistory(true);

            if (navigator.mediaDevices) {
                navigator.mediaDevices.addEventListener && navigator.mediaDevices.addEventListener('devicechange', function() {
                    listDevices();
                    if (!stream) toast('Perangkat video berubah (capture card dicolok / dicabut)', 'info');
                });
                listDevices().then(function() {
                    // Jika izin sudah pernah diberikan, label terbaca -> langsung mulai otomatis
                    if ($('#device option').filter(function() {
                            return /★/.test($(this).text());
                        }).length) startStream();
                });
            }
        })();
    </script>
</body>

</html>