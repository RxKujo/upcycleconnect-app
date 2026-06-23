<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page introuvable · UpcycleConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { --cherry:#A4243B; --wheat:#D8C99B; --coffee:#120309; --forest:#244F26; --teal:#18607D; --cream:#F5F0E1; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--cream); font-family: 'Outfit', sans-serif; color: var(--coffee); min-height: 100vh; display: flex; flex-direction: column; }
        nav { background: var(--coffee); padding: 16px 32px; border-bottom: 3px solid var(--coffee); display: flex; align-items: center; }
        nav a { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.12em; color: var(--wheat); text-decoration: none; }
        nav a span { color: var(--cream); }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
        .box {
            text-align: center;
            padding: 52px 48px;
            border: 3px solid var(--coffee);
            box-shadow: 8px 8px 0px var(--coffee);
            background: var(--cream);
            max-width: 500px;
            width: 100%;
        }
        .badge {
            display: inline-block;
            font-family: 'DM Mono', monospace;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: var(--teal);
            color: var(--cream);
            padding: 3px 12px;
            border: 2px solid var(--coffee);
            margin-bottom: 20px;
        }
        h1 { font-family: 'Bebas Neue', sans-serif; font-size: 7rem; margin: 0; color: var(--teal); line-height: 1; border-bottom: 3px solid var(--coffee); padding-bottom: 20px; margin-bottom: 20px; }
        h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 12px; }
        p { opacity: 0.75; margin-bottom: 32px; line-height: 1.6; font-size: 0.95rem; }
        .actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase;
            border: 3px solid var(--coffee); padding: 11px 26px; font-size: 1.1rem;
            text-decoration: none; box-shadow: 4px 4px 0 var(--coffee);
            transition: transform 0.08s, box-shadow 0.08s; cursor: pointer; background: none;
        }
        .btn:active { transform: translate(3px,3px); box-shadow: 1px 1px 0 var(--coffee); }
        .btn-primary { background: var(--cherry); color: var(--cream); }
        .btn-secondary { background: var(--cream); color: var(--coffee); }
    </style>
</head>
<body>
    <nav><a href="/">Upcycle<span>Connect</span></a></nav>
    <main>
        <div class="box">
            <span class="badge">Erreur 404</span>
            <h1>404</h1>
            <h2>Page introuvable</h2>
            <p>Cette page n'existe pas ou a été déplacée.<br>
               Vérifiez l'URL ou revenez à l'accueil pour continuer.</p>
            <div class="actions">
                <a href="javascript:history.back()" class="btn btn-secondary">← Retour</a>
                <a href="/" class="btn btn-primary">Accueil</a>
            </div>
        </div>
    </main>
</body>
</html>
