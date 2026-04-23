<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Sedang Maintenance</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #ecfdf5;
            --panel: rgba(255, 255, 255, 0.95);
            --text: #052e2b;
            --muted: #3f635f;
            --line: rgba(5, 46, 43, 0.12);
            --accent: #0f766e;
            --accent-soft: rgba(15, 118, 110, 0.12);
            --info: #155e75;
            --info-soft: rgba(8, 145, 178, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                linear-gradient(135deg, rgba(20, 184, 166, 0.18), transparent 45%),
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.22), transparent 30%),
                var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .shell {
            width: min(760px, 100%);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 118, 110, 0.18);
        }

        .header {
            padding: 22px 28px;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: #fff;
        }

        .header small {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .body {
            padding: 30px 28px 32px;
        }

        h1 {
            margin: 0 0 14px;
            font-size: clamp(34px, 6vw, 50px);
            line-height: 1.04;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.75;
        }

        .grid {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 22px;
            align-items: center;
            margin-top: 18px;
        }

        .icon-wrap {
            width: 110px;
            height: 110px;
            border-radius: 28px;
            background: var(--accent-soft);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .panel {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-box {
            padding: 18px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid var(--line);
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--info);
        }

        .info-box p {
            font-size: 15px;
            line-height: 1.65;
        }

        .footer {
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 20px;
            background: var(--info-soft);
        }

        .footer p {
            font-size: 15px;
        }

        .actions {
            margin-top: 24px;
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
            background: #0f766e;
            color: #fff;
        }

        .button-secondary {
            background: #d1fae5;
            color: #065f46;
        }

        @media (max-width: 720px) {
            .header,
            .body {
                padding-left: 22px;
                padding-right: 22px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .icon-wrap {
                width: 92px;
                height: 92px;
            }

            .panel {
                grid-template-columns: 1fr;
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
    <main class="shell">
        <section class="header">
            <small>503 &bull; Maintenance Mode</small>
        </section>

        <section class="body">
            <div class="grid">
                <div class="icon-wrap" aria-hidden="true">
                    <svg width="62" height="62" viewBox="0 0 64 64" fill="none">
                        <circle cx="32" cy="32" r="30" fill="#CCFBF1"/>
                        <path d="M20 42L42 20M37 18.5C39.8 18.8 42.4 20 44.4 22C46.4 24 47.6 26.6 47.9 29.4L41.8 35.5L29 22.7L35.1 16.6C35.7 17.5 36.3 18.1 37 18.5Z" fill="#0F766E"/>
                        <path d="M18 35L29 46L22.5 52.5C20.8 54.2 18 54.2 16.3 52.5C14.6 50.8 14.6 48 16.3 46.3L18 44.6V35Z" fill="#14B8A6"/>
                    </svg>
                </div>

                <div>
                    <h1>Aplikasi sedang maintenance.</h1>
                    <p>
                        Tim sedang melakukan pembaruan atau layanan server masih dipersiapkan.
                        Halaman ini akan mencoba memuat ulang otomatis setiap <strong>15 detik</strong>
                        sampai aplikasi siap digunakan kembali.
                    </p>
                </div>
            </div>

            <div class="panel">
                <section class="info-box">
                    <strong>Apa yang terjadi?</strong>
                    <p>Proses update, restart Laragon, atau perbaikan layanan sedang berlangsung sehingga akses sementara ditahan.</p>
                </section>

                <section class="info-box">
                    <strong>Apa yang perlu dilakukan?</strong>
                    <p>Tidak perlu menutup browser. Biarkan halaman tetap terbuka, nanti aplikasi akan mencoba masuk lagi otomatis.</p>
                </section>
            </div>

            <section class="footer">
                <p>Jika halaman ini tidak berubah dalam beberapa menit, hubungi admin server untuk memastikan proses update sudah selesai dan maintenance mode telah dimatikan.</p>
            </section>

            <div class="actions">
                <button type="button" class="button button-primary" onclick="window.location.reload()">Coba Sekarang</button>
                <a href="/" class="button button-secondary">Ke Halaman Utama</a>
            </div>
        </section>
    </main>

    <script>
        window.setTimeout(function () {
            window.location.reload();
        }, 15000);
    </script>
</body>
</html>
