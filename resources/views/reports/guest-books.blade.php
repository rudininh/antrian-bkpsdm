<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.45;
        }

        .header {
            padding: 16px 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #7c3aed, #0f766e);
            color: #fff;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.9);
        }

        .meta {
            margin-top: 10px;
            font-size: 10px;
            letter-spacing: 0.04em;
        }

        .kpis {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .kpis td {
            width: 25%;
            padding: 8px;
            vertical-align: top;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            min-height: 78px;
            background: #fff;
        }

        .card .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .card .value {
            margin-top: 8px;
            font-size: 21px;
            font-weight: 700;
        }

        .card .note {
            margin-top: 5px;
            color: #64748b;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .section {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px;
            background: #fff;
        }

        .section h2 {
            margin: 0 0 8px;
            font-size: 14px;
        }

        .bar-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .bar-item {
            margin-bottom: 10px;
        }

        .bar-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }

        .bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }

        .bar span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #7c3aed, #d946ef);
        }

        .bar.teal span {
            background: linear-gradient(90deg, #0f766e, #34d399);
        }

        .bar.amber span {
            background: linear-gradient(90deg, #f59e0b, #fb7185);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        .table th,
        .table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .table th {
            background: #f8fafc;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .muted {
            color: #64748b;
        }

        .feedback {
            white-space: pre-wrap;
        }

        .footer-note {
            margin-top: 12px;
            color: #64748b;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @php
        $ratingMax = max(1, max(array_column($report['ratingBreakdown'], 'total') ?: [1]));
        $serviceMax = max(1, max(array_column($report['serviceBreakdown'], 'total') ?: [1]));
    @endphp

    <div class="header">
        <h1>Laporan Buku Tamu</h1>
        <p>Rekap lengkap tamu, rating, rekomendasi, dan feedback pada periode {{ $report['range']['startLabel'] }} sampai {{ $report['range']['endLabel'] }}</p>
        <div class="meta">
            Total buku tamu: {{ $report['summary']['guestBookTotal'] }} | Rata-rata rating: {{ number_format($report['summary']['averageRating'], 1) }} | Rekomendasi: {{ $report['summary']['recommendationRate'] }}%
        </div>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <div class="card">
                    <div class="label">Total Buku Tamu</div>
                    <div class="value">{{ $report['summary']['guestBookTotal'] }}</div>
                    <div class="note">Seluruh entri pada periode terpilih.</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Rata-rata Rating</div>
                    <div class="value">{{ number_format($report['summary']['averageRating'], 1) }}</div>
                    <div class="note">Skala 1 sampai 5.</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Rekomendasi</div>
                    <div class="value">{{ $report['summary']['recommendationRate'] }}%</div>
                    <div class="note">Responden yang menjawab ya.</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Feedback Terisi</div>
                    <div class="value">{{ $report['summary']['feedbackFilled'] }}</div>
                    <div class="note">Entri dengan catatan feedback.</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <div class="section">
                    <h2>Rating Buku Tamu</h2>
                    <ul class="bar-list">
                        @foreach ($report['ratingBreakdown'] as $item)
                            <li class="bar-item">
                                <div class="bar-head">
                                    <span>Rating {{ $item['rating'] }}</span>
                                    <span class="muted">{{ $item['total'] }}</span>
                                </div>
                                <div class="bar amber">
                                    <span style="width: {{ max(8, round(($item['total'] / $ratingMax) * 100)) }}%;"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>Distribusi Layanan</h2>
                    <ul class="bar-list">
                        @foreach ($report['serviceBreakdown'] as $item)
                            <li class="bar-item">
                                <div class="bar-head">
                                    <span>{{ $item['service'] }}</span>
                                    <span class="muted">{{ $item['total'] }}</span>
                                </div>
                                <div class="bar teal">
                                    <span style="width: {{ max(8, round(($item['total'] / $serviceMax) * 100)) }}%;"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </td>
        </tr>
    </table>

    <div class="section" style="margin-top: 14px;">
        <h2>Detail Buku Tamu</h2>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%;">Waktu</th>
                    <th style="width: 7%;">Nomor</th>
                    <th style="width: 10%;">Layanan</th>
                    <th style="width: 11%;">Nama</th>
                    <th style="width: 10%;">Instansi</th>
                    <th style="width: 8%;">Telepon</th>
                    <th style="width: 11%;">Tujuan</th>
                    <th style="width: 10%;">Konsultan</th>
                    <th style="width: 5%;">Rating</th>
                    <th style="width: 7%;">Rekomen</th>
                    <th style="width: 13%;">Feedback</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['guestBooks'] as $guestBook)
                    <tr>
                        <td>{{ $guestBook->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $guestBook->queue?->ticket_number ?? '-' }}</td>
                        <td>{{ $guestBook->queue?->service?->name ?? '-' }}</td>
                        <td>{{ $guestBook->guest_name }}</td>
                        <td>{{ $guestBook->institution ?: '-' }}</td>
                        <td>{{ $guestBook->phone_number ?: '-' }}</td>
                        <td>{{ $guestBook->visit_purpose ?: '-' }}</td>
                        <td>{{ $guestBook->consultant_name ?: '-' }}</td>
                        <td>{{ $guestBook->rating ?? '-' }}</td>
                        <td>{{ $guestBook->would_recommend === null ? '-' : ($guestBook->would_recommend ? 'Ya' : 'Tidak') }}</td>
                        <td class="feedback">{{ $guestBook->feedback ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="muted" style="text-align: center; padding: 12px 6px;">
                            Tidak ada buku tamu pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        Export ini khusus menampilkan data buku tamu dan feedback yang tersimpan pada periode terpilih.
    </div>
</body>
</html>
