<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SchoolCred Data Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f1f2e;
            --ink-soft: #3d5266;
            --paper: #f3f6f4;
            --accent: #e85d04;
            --accent-deep: #c44d00;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(232, 93, 4, 0.12), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 80%, rgba(15, 31, 46, 0.08), transparent 50%),
                linear-gradient(165deg, #f5eee8 0%, var(--paper) 45%, #ebe6e1 100%);
            -webkit-font-smoothing: antialiased;
        }

        .shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 1.5rem 3rem;
            max-width: 52rem;
            margin: 0 auto;
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            animation: rise 0.7s ease forwards;
        }

        .logo {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 0.85rem;
            flex-shrink: 0;
        }

        .brand {
            font-family: 'Source Serif 4', Georgia, serif;
            font-size: clamp(2.75rem, 8vw, 4.25rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.05;
            color: var(--ink);
        }

        .brand span {
            display: block;
            color: var(--accent);
        }

        .lede {
            margin-top: 1.25rem;
            max-width: 30rem;
            font-size: 1.15rem;
            line-height: 1.55;
            color: var(--ink-soft);
            opacity: 0;
            animation: rise 0.7s ease 0.12s forwards;
        }

        .site-link {
            display: inline-block;
            margin-top: 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--accent);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color 0.2s ease, border-color 0.2s ease;
            opacity: 0;
            animation: rise 0.7s ease 0.24s forwards;
        }

        .site-link:hover {
            color: var(--accent-deep);
            border-bottom-color: var(--accent-deep);
        }

        .footer {
            margin-top: 3rem;
            font-size: 0.8rem;
            color: var(--ink-soft);
            opacity: 0;
            animation: rise 0.7s ease 0.36s forwards;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand-row">
            <img
                class="logo"
                src="{{ asset('schoolcred_logo.png') }}"
                alt="SchoolCred"
                width="72"
                height="72"
            >
            <h1 class="brand">
                SchoolCred
                <span>Data Manager</span>
            </h1>
        </div>

        <p class="lede">
            This is the internal data manager for SchoolCred — used to import, reconcile, and maintain school records that power the platform.
        </p>

        <a class="site-link" href="https://schoolcred.ca/" target="_blank" rel="noopener noreferrer">
            Visit schoolcred.ca →
        </a>

        <p class="footer">Authorized use only · SchoolCred operations</p>
    </main>
</body>
</html>
