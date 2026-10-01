<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sertifikat Pelatihan - {{ $recipientName }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Playfair+Display:ital,wght@0,700;0,900;1,400&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap');

        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        @php
            $fontFamily = $config['font_family'] ?? "'Plus Jakarta Sans', Arial, sans-serif";
        @endphp

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: {!! $fontFamily !!};
            position: relative;
            background-color: #ffffff;
            overflow: hidden;
        }

        /* Default Certificate Frame Fallback if no background image */
        .default-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 8mm;
            box-sizing: border-box;
            background: linear-gradient(135deg, #fdfbf7 0%, #f4f6f9 100%);
        }

        .outer-border {
            width: 100%;
            height: 100%;
            border: 4px solid #0f2b48;
            padding: 4mm;
            box-sizing: border-box;
            position: relative;
        }

        .inner-border {
            width: 100%;
            height: 100%;
            border: 1.5px solid #d4af37;
            position: relative;
            text-align: center;
        }

        .corner-decor {
            position: absolute;
            width: 16mm;
            height: 16mm;
            border-color: #d4af37;
            border-style: solid;
        }

        .corner-tl {
            top: 2mm;
            left: 2mm;
            border-width: 3px 0 0 3px;
        }

        .corner-tr {
            top: 2mm;
            right: 2mm;
            border-width: 3px 3px 0 0;
        }

        .corner-bl {
            bottom: 2mm;
            left: 2mm;
            border-width: 0 0 3px 3px;
        }

        .corner-br {
            bottom: 2mm;
            right: 2mm;
            border-width: 0 3px 3px 0;
        }

        /* Background template image (covers 100% of A4 landscape) */
        .custom-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        /* Coordinate Overlay Items */
        .overlay-item {
            position: absolute;
            z-index: 20;
            white-space: nowrap;
            font-family: {!! $fontFamily !!};
        }

        /* Guidelines Grid for Certificate Builder */
        .grid-line-x {
            position: absolute;
            left: 0;
            width: 100%;
            border-top: 1px dashed rgba(239, 68, 68, 0.4);
            z-index: 50;
            font-size: 8pt;
            color: #ef4444;
            padding-left: 2mm;
        }

        .grid-line-y {
            position: absolute;
            top: 0;
            height: 100%;
            border-left: 1px dashed rgba(59, 130, 246, 0.4);
            z-index: 50;
            font-size: 8pt;
            color: #3b82f6;
            padding-top: 2mm;
        }
    </style>
</head>

<body>

    @if ($bgDataUri)
        <img src="{{ $bgDataUri }}" class="custom-bg" alt="Certificate Canvas" />
    @else
        <!-- Elegant Default Frame Fallback -->
        <div class="default-frame">
            <div class="outer-border">
                <div class="inner-border">
                    <div class="corner-decor corner-tl"></div>
                    <div class="corner-decor corner-tr"></div>
                    <div class="corner-decor corner-bl"></div>
                    <div class="corner-decor corner-br"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- 1. Official Kop Instansi (Configurable) --}}
    @php
        $cKop = $config['header_kop'] ?? [
            'show' => true,
            'x' => 50,
            'y' => 9,
            'line1' => 'KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA',
            'line2' => 'BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN',
            'font_size_line1' => 10.5,
            'font_size_line2' => 8.5,
            'color_line1' => '#0f2b48',
            'color_line2' => '#b38b25',
            'align' => 'center',
        ];
        $showKop = $cKop['show'] ?? true;
        $kopAlign = $cKop['align'] ?? 'center';
        $kopX = $cKop['x'] ?? 50;
        $kopY = $cKop['y'] ?? 9;
    @endphp
    @if ($showKop)
        <div class="overlay-item"
            style="
            top: {{ $kopY }}%;
            @if ($kopAlign === 'center') left: {{ $kopX - 50 }}%; width: 100%; text-align: center;
            @elseif($kopAlign === 'right') right: {{ 100 - $kopX }}%; text-align: right;
            @else left: {{ $kopX }}%; text-align: left; @endif
            line-height: 1.35;
        ">
            <div
                style="font-size: {{ $cKop['font_size_line1'] ?? 10.5 }}pt; font-weight: 800; letter-spacing: 2px; color: {{ $cKop['color_line1'] ?? '#0f2b48' }}; text-transform: uppercase;">
                {{ $cKop['line1'] ?? 'KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA' }}
            </div>
            <div
                style="font-size: {{ $cKop['font_size_line2'] ?? 8.5 }}pt; font-weight: 700; letter-spacing: 1.5px; color: {{ $cKop['color_line2'] ?? '#b38b25' }}; text-transform: uppercase; margin-top: 1.2mm;">
                {{ $cKop['line2'] ?? 'BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN' }}
            </div>
        </div>
    @endif

    {{-- 2. Judul Sertifikat "SERTIFIKAT PELATIHAN" (Configurable) --}}
    @php
        $cTitleMain = $config['certificate_title'] ?? [
            'show' => true,
            'x' => 50,
            'y' => 18,
            'text' => 'SERTIFIKAT PELATIHAN',
            'font_size' => 24,
            'color' => '#0f2b48',
            'align' => 'center',
        ];
        $showTitleMain = $cTitleMain['show'] ?? true;
        $titleMainAlign = $cTitleMain['align'] ?? 'center';
        $titleMainX = $cTitleMain['x'] ?? 50;
        $titleMainY = $cTitleMain['y'] ?? 18;
    @endphp
    @if ($showTitleMain)
        <div class="overlay-item"
            style="
            top: {{ $titleMainY }}%;
            @if ($titleMainAlign === 'center') left: {{ $titleMainX - 50 }}%; width: 100%; text-align: center;
            @elseif($titleMainAlign === 'right') right: {{ 100 - $titleMainX }}%; text-align: right;
            @else left: {{ $titleMainX }}%; text-align: left; @endif
            font-size: {{ $cTitleMain['font_size'] ?? 24 }}pt;
            font-weight: 800;
            letter-spacing: 4px;
            color: {{ $cTitleMain['color'] ?? '#0f2b48' }};
            text-transform: uppercase;
        ">
            {{ $cTitleMain['text'] ?? 'SERTIFIKAT PELATIHAN' }}
        </div>
    @endif

    {{-- 3. Nomor Sertifikat --}}
    @php
        $cNum = $config['certificate_number'] ?? [
            'x' => 50,
            'y' => 27,
            'font_size' => 12,
            'font_weight' => 'normal',
            'color' => '#475569',
            'align' => 'center',
        ];
        $numAlign = $cNum['align'] ?? 'center';
        $numX = $cNum['x'] ?? 50;
        $numY = $cNum['y'] ?? 27;
    @endphp
    <div class="overlay-item"
        style="
        top: {{ $numY }}%;
        @if ($numAlign === 'center') left: {{ $numX - 50 }}%; width: 100%; text-align: center;
        @elseif($numAlign === 'right') right: {{ 100 - $numX }}%; text-align: right;
        @else left: {{ $numX }}%; text-align: left; @endif
        font-size: {{ $cNum['font_size'] ?? 12 }}pt;
        font-weight: {{ $cNum['font_weight'] ?? 'normal' }};
        color: {{ $cNum['color'] ?? '#475569' }};
        font-family: 'Courier New', Courier, monospace;
    ">
        Nomor: {{ $certificateNumber }}
    </div>

    {{-- 4. Nama Peserta --}}
    @php
        $cName = $config['recipient_name'] ?? [
            'x' => 50,
            'y' => 37,
            'font_size' => 26,
            'font_weight' => 'bold',
            'color' => '#0f2b48',
            'align' => 'center',
        ];
        $nameAlign = $cName['align'] ?? 'center';
        $nameX = $cName['x'] ?? 50;
        $nameY = $cName['y'] ?? 37;
    @endphp
    <div class="overlay-item"
        style="
        top: {{ $nameY }}%;
        @if ($nameAlign === 'center') left: {{ $nameX - 50 }}%; width: 100%; text-align: center;
        @elseif($nameAlign === 'right') right: {{ 100 - $nameX }}%; text-align: right;
        @else left: {{ $nameX }}%; text-align: left; @endif
        font-size: {{ $cName['font_size'] ?? 26 }}pt;
        font-weight: {{ $cName['font_weight'] ?? '800' }};
        color: {{ $cName['color'] ?? '#0f2b48' }};
        letter-spacing: 0.5px;
    ">
        {{ $recipientName }}
    </div>

    {{-- 5. Judul Pelatihan & Durasi --}}
    @php
        $cTitle = $config['course_title'] ?? [
            'x' => 50,
            'y' => 49,
            'font_size' => 15,
            'font_weight' => 'bold',
            'color' => '#1e293b',
            'align' => 'center',
        ];
        $titleAlign = $cTitle['align'] ?? 'center';
        $titleX = $cTitle['x'] ?? 50;
        $titleY = $cTitle['y'] ?? 49;
        $durDays = $durationDays ?? 1;
    @endphp
    <div class="overlay-item"
        style="
        top: {{ $titleY }}%;
        @if ($titleAlign === 'center') left: {{ $titleX - 50 }}%; width: 100%; text-align: center;
        @elseif($titleAlign === 'right') right: {{ 100 - $titleX }}%; text-align: right;
        @else left: {{ $titleX }}%; text-align: left; @endif
        font-size: {{ $cTitle['font_size'] ?? 15 }}pt;
        font-weight: {{ $cTitle['font_weight'] ?? 'bold' }};
        color: {{ $cTitle['color'] ?? '#1e293b' }};
        line-height: 1.45;
    ">
        <div style="font-size: 0.9em; font-weight: normal; color: {{ $cTitle['color'] ?? '#334155' }};">
            Telah menyelesaikan pelatihan:
        </div>
        <div
            style="font-size: 1.15em; font-weight: 800; color: {{ $cTitle['color'] ?? '#0f2b48' }}; margin: 1mm 0;">
            "{{ $courseTitle }}"
        </div>
        <div style="font-size: 0.85em; font-weight: normal; color: {{ $cTitle['color'] ?? '#475569' }};">
            selama {{ $durDays }} hari
        </div>
    </div>

    {{-- 6. Tanggal Terbit --}}
    @php
        $cDate = $config['issue_date'] ?? [
            'x' => 50,
            'y' => 67,
            'font_size' => 12,
            'font_weight' => 'normal',
            'color' => '#475569',
            'align' => 'center',
        ];
        $dateAlign = $cDate['align'] ?? 'center';
        $dateX = $cDate['x'] ?? 50;
        $dateY = $cDate['y'] ?? 67;
    @endphp
    <div class="overlay-item"
        style="
        top: {{ $dateY }}%;
        @if ($dateAlign === 'center') left: {{ $dateX - 50 }}%; width: 100%; text-align: center;
        @elseif($dateAlign === 'right') right: {{ 100 - $dateX }}%; text-align: right;
        @else left: {{ $dateX }}%; text-align: left; @endif
        font-size: {{ $cDate['font_size'] ?? 12 }}pt;
        font-weight: {{ $cDate['font_weight'] ?? 'normal' }};
        color: {{ $cDate['color'] ?? '#475569' }};
    ">
        Pangkajene dan Kepulauan, {{ $issueDate }}
    </div>

    {{-- 7. QR Code TTE --}}
    @php
        $cQr = $config['qr_code'] ?? ['x' => 50, 'y' => 77, 'size' => 80, 'align' => 'center'];
        $qrAlign = $cQr['align'] ?? 'center';
        $qrX = $cQr['x'] ?? 50;
        $qrY = $cQr['y'] ?? 77;
        $qrPx = $cQr['size'] ?? 80;
    @endphp
    <div class="overlay-item"
        style="
        top: {{ $qrY }}%;
        @if ($qrAlign === 'center') left: {{ $qrX - 50 }}%; width: 100%; text-align: center;
        @elseif($qrAlign === 'right') right: {{ 100 - $qrX }}%; text-align: right;
        @else left: {{ $qrX }}%; text-align: left; @endif
    ">
        <div style="display: inline-block; text-align: center;">
            <img src="{{ $qrDataUri }}" width="{{ $qrPx }}" height="{{ $qrPx }}"
                style="border: 1px solid #cbd5e1; padding: 1mm; background: #ffffff;" alt="QR TTE Keabsahan" />
            <div style="font-size: 6.5pt; color: #64748b; margin-top: 1mm; font-weight: bold; letter-spacing: 0.5px;">
                VERIFIKASI TTE RESMI
            </div>
        </div>
    </div>

    {{-- Optional Guideline Grid for Builder Preview --}}
    @if (!empty($config['show_grid']) && !empty($isPreview))
        @for ($y = 10; $y < 100; $y += 10)
            <div class="grid-line-x" style="top: {{ $y }}%;">Y: {{ $y }}%</div>
        @endfor
        @for ($x = 10; $x < 100; $x += 10)
            <div class="grid-line-y" style="left: {{ $x }}%;">X: {{ $x }}%</div>
        @endfor
    @endif

</body>

</html>

