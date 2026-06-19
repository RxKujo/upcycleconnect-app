<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable — UpcycleConnect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --cherry:#A4243B; --wheat:#D8C99B; --coffee:#120309; --cream:#F5F0E1; --border:3px solid #120309; --shadow:5px 5px 0px #120309; }
        body { background:var(--cream); font-family:'Outfit',sans-serif; margin:0; color:var(--coffee); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .box { text-align:center; padding:60px 40px; border:var(--border); box-shadow:var(--shadow); background:white; max-width:480px; }
        h1 { font-family:'Bebas Neue',sans-serif; font-size:6rem; margin:0; color:var(--cherry); line-height:1; }
        h2 { font-family:'Bebas Neue',sans-serif; font-size:2rem; margin:8px 0 24px; letter-spacing:0.05em; }
        p { opacity:0.7; margin-bottom:32px; line-height:1.6; }
        .btn { display:inline-flex; align-items:center; font-family:'Bebas Neue',sans-serif; letter-spacing:0.1em; text-transform:uppercase; background:var(--cherry); color:var(--cream); border:var(--border); padding:12px 28px; font-size:1.2rem; text-decoration:none; box-shadow:var(--shadow); transition:all 0.2s; }
        .btn:hover { transform:translate(3px,3px); box-shadow:2px 2px 0 var(--coffee); }
    </style>
</head>
<body>
    <div class="box">
        <h1>404</h1>
        <h2>Page introuvable</h2>
        <p>Cette page n'existe pas ou a été déplacée.<br>Revenez à l'accueil pour continuer.</p>
        <a href="/" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html>
