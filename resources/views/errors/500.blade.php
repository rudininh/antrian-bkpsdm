<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="2">
    <title>500 - Server Sedang Bermasalah</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: rgba(15, 23, 42, 0.08);
            --accent: #0f766e;
            --accent-soft: rgba(15, 118, 110, 0.12);
            --warn: #b45309;
            --warn-soft: rgba(245, 158, 11, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(13, 148, 136, 0.16), transparent 32%),
                radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.16), transparent 28%),
                var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: min(720px, 100%);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--warn-soft);
            color: var(--warn);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        h1 {
            margin: 22px 0 14px;
            font-size: clamp(32px, 6vw, 48px);
            line-height: 1.05;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.7;
        }

        .status-box {
            margin-top: 28px;
            padding: 18px 20px;
            border-radius: 20px;
            background: var(--accent-soft);
            border: 1px solid rgba(15, 118, 110, 0.12);
        }

        .status-box strong {
            display: block;
            margin-bottom: 6px;
            color: var(--accent);
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            appearance: none;
            border: none;
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .button-primary {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: #e2e8f0;
            color: #0f172a;
        }

        .hint {
            margin-top: 22px;
            font-size: 14px;
            color: #64748b;
        }

        .illustration {
            width: 92px;
            height: 92px;
            margin-bottom: 10px;
        }

        @media (max-width: 640px) {
            .card {
                padding: 24px;
                border-radius: 24px;
            }

            p {
                font-size: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">500 • Gangguan Sementara</div>

        <svg class="illustration" viewBox="0 0 120 120" fill="none" aria-hidden="true">
            <circle cx="60" cy="60" r="56" fill="#FEF3C7" />
            <path d="M60 28L87 78H33L60 28Z" fill="#F59E0B" />
            <circle cx="60" cy="67" r="5" fill="#fff" />
            <rect x="56" y="46" width="8" height="15" rx="4" fill="#fff" />
        </svg>

        <h1>Server sedang mengalami gangguan.</h1>
        <p>
            Aplikasi belum bisa merespons dengan normal. Halaman ini akan mencoba memuat ulang otomatis
            setiap <strong>2 detik</strong> sampai layanan kembali berjalan.
        </p>

        <section class="status-box">
            <strong>Status</strong>
            <p>Jika Laragon, web server, database, atau proses deploy baru saja dijalankan ulang, tunggu sebentar. Browser akan mencoba kembali otomatis.</p>
        </section>

        <div class="actions">
            <button type="button" class="button button-primary" onclick="window.location.reload()">Coba Sekarang</button>
            <a href="{{ url('/') }}" class="button button-secondary">Kembali ke Beranda</a>
        </div>

        <p class="hint">Tidak perlu refresh manual berulang kali. Biarkan halaman ini tetap terbuka.</p>
    </main>

    <script>
        window.setTimeout(function () {
            window.location.reload();
        }, 2000);
    </script>
</body>
</html>
