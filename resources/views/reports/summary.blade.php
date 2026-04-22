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
            font-size: 11px;
            line-height: 1.5;
        }

        .header {
            padding: 16px 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: #fff;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .header p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.88);
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
            width: 16.666%;
            padding: 8px;
            vertical-align: top;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            min-height: 82px;
        }

        .card .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .card .value {
            margin-top: 8px;
            font-size: 22px;
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
            background: linear-gradient(90deg, #0f766e, #34d399);
        }

        .bar.violet span {
            background: linear-gradient(90deg, #7c3aed, #d946ef);
        }

        .bar.amber span {
            background: linear-gradient(90deg, #f59e0b, #fb7185);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 6px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background: #f8fafc;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .muted {
            color: #64748b;
        }

        .small-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .small-grid td {
            width: 25%;
            padding: 6px;
            vertical-align: top;
        }

        .timeline {
            width: 100%;
            border-collapse: collapse;
        }

        .timeline td {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>
    @php
        $queueMax = max(array_column($report['queueStatus'], 'total') ?: [1]);
        $ratingMax = max(array_column($report['ratingBreakdown'], 'total') ?: [1]);
        $serviceMax = max(array_column($report['serviceBreakdown'], 'total') ?: [1]);
    @endphp

    <div class="header">
        <h1>Laporan Operasional Antrian dan Buku Tamu</h1>
        <p>Periode {{ $report['range']['startLabel'] }} sampai {{ $report['range']['endLabel'] }}</p>
        <div class="meta">
            Total antrian: {{ $report['summary']['queueTotal'] }} | Buku tamu: {{ $report['summary']['guestBookTotal'] }} | Top layanan: {{ $report['summary']['topService'] }}
        </div>
    </div>

    <table class="kpis">
        <tr>
            @foreach ($report['kpis'] as $item)
                <td>
                    <div class="card">
                        <div class="label">{{ $item['label'] }}</div>
                        <div class="value">{{ $item['value'] }}</div>
                        <div class="note">{{ $item['note'] }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <div class="section">
                    <h2>Distribusi Status Antrian</h2>
                    <ul class="bar-list">
                        @foreach ($report['queueStatus'] as $item)
                            <li class="bar-item">
                                <div class="bar-head">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="muted">{{ $item['total'] }}</span>
                                </div>
                                <div class="bar">
                                    <span style="width: {{ max(8, round(($item['total'] / $queueMax) * 100)) }}%;"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </td>
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
        </tr>
    </table>

    <table class="grid">
        <tr>
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
                                <div class="bar violet">
                                    <span style="width: {{ max(8, round(($item['total'] / $serviceMax) * 100)) }}%;"></span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>Timeline Harian</h2>
                    <table class="timeline">
                        @foreach ($report['timeline'] as $item)
                            <tr>
                                <td style="width: 26%;">{{ $item['date'] }}</td>
                                <td class="muted">Antrian {{ $item['queueTotal'] }}</td>
                                <td class="muted">Buku tamu {{ $item['guestBookTotal'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <div class="section">
                    <h2>10 Antrian Terbaru</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Layanan</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['recentQueues'] as $queue)
                                <tr>
                                    <td>{{ $queue['ticket'] }}</td>
                                    <td>{{ $queue['service'] }}</td>
                                    <td>{{ $queue['status'] }}</td>
                                    <td>{{ $queue['queuedAt'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>10 Feedback Terbaru</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Instansi</th>
                                <th>Rating</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['recentGuestBooks'] as $guest)
                                <tr>
                                    <td>{{ $guest['guestName'] }}</td>
                                    <td>{{ $guest['institution'] }}</td>
                                    <td>{{ $guest['rating'] }}</td>
                                    <td>{{ $guest['wouldRecommend'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
