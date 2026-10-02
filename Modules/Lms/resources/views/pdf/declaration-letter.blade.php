<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan Komitmen Bekerja</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 20px;
            text-align: center;
        }

        .content {
            margin-bottom: 40px;
        }

        .row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
        }

        .colon {
            display: table-cell;
            width: 2%;
        }

        .value {
            display: table-cell;
            width: 68%;
        }

        .statement {
            margin-top: 30px;
            text-align: justify;
        }

        .signature-area {
            width: 100%;
            margin-top: 50px;
        }

        .signature-box {
            width: 40%;
            float: right;
            text-align: center;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 80px;
        }

        .footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2 style="margin:0;">BALAI PELATIAHN VOKASI DAN PRODUKTIVITAS PANGKAJENE DAN KEPULAUAN</h2>
        <p style="margin:5px 0 0 0; font-size: 14px;">Kementerian Ketenagakerjaan Republik Indonesia</p>
    </div>

    <div class="title">SURAT PERNYATAAN KOMITMEN BEKERJA</div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini:</p>

        @php
            $nik = $participant->nik ?? '-';
            if (strlen($nik) >= 16) {
                $censoredNik = substr($nik, 0, 4) . '••••••••' . substr($nik, -4);
            } else {
                $censoredNik = $nik;
            }
        @endphp

        <div class="row">
            <div class="label">Nama Lengkap</div>
            <div class="colon">:</div>
            <div class="value">{{ $participant->name }}</div>
        </div>
        <div class="row">
            <div class="label">NIK</div>
            <div class="colon">:</div>
            <div class="value">{{ $censoredNik }}</div>
        </div>
        <div class="row">
            <div class="label">Program Pelatihan</div>
            <div class="colon">:</div>
            <div class="value">{{ $course->title }}</div>
        </div>
        <div class="row">
            <div class="label">Tanggal Selesai</div>
            <div class="colon">:</div>
            <div class="value">{{ $date }}</div>
        </div>

        <div class="statement">
            <p>Dengan ini saya menyatakan dengan sesungguhnya bahwa saya bersedia dan berkomitmen penuh untuk bekerja
                atau berkontribusi secara aktif di bidang yang sesuai dengan pelatihan yang telah saya ikuti dan
                selesaikan. Pernyataan ini saya buat dengan penuh kesadaran dan tanpa paksaan dari pihak manapun.</p>
        </div>
    </div>

    <div class="signature-area clearfix">
        <div class="signature-box">
            <p>Pangkep, {{ $date }}<br>Yang membuat pernyataan,</p>
            <div class="signature-name">{{ $participant->name }}</div>
        </div>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara digital oleh LMS BPVP Pangkep pada {{ now()->format('d M Y H:i') }}
    </div>

</body>

</html>
