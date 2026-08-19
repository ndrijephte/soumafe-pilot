<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUMAFE PILOT — Connexion</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Calibri,sans-serif}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#16385C 0%,#1F4E79 55%,#2E74B5 100%)}
        .carte{background:#fff;border-radius:16px;padding:38px 34px;width:380px;box-shadow:0 18px 50px rgba(0,0,0,.3)}
        .cercle{width:70px;height:70px;border-radius:50%;background:#1F4E79;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;border:3px solid #2E74B5}
        .cercle img{height:40px}
        h1{text-align:center;font-size:20px;color:#1F4E79}
        .sous{text-align:center;font-size:11px;color:#64748b;margin-bottom:22px}
        label{font-size:12px;color:#64748b;display:block;margin:12px 0 4px}
        input{width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px}
        input:focus{outline:2px solid #2E74B5}
        button{width:100%;margin-top:20px;padding:11px;background:#2E74B5;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}
        button:hover{background:#1F4E79}
        .erreur{background:#fee2e2;color:#b91c1c;border-radius:8px;padding:8px 12px;font-size:12.5px;margin-top:12px}
        .pied{text-align:center;font-size:10.5px;color:#94a3b8;margin-top:18px}
    </style>
</head>
<body>
    <form class="carte" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="cercle"><img src="{{ asset('images/white_logo_v.png') }}" alt="SOUMAFÊ"></div>
        <h1>SOUMAFE PILOT</h1>
        <div class="sous">Suivi des activités, projets et opérations</div>
        <label>Adresse e-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label>Mot de passe</label>
        <input type="password" name="password" required>
        @error('email')<div class="erreur">{{ $message }}</div>@enderror
        <button>Se connecter</button>
        <div class="pied">SF SOUMAFÊ — accès réservé au personnel</div>
    </form>
</body>
</html>
