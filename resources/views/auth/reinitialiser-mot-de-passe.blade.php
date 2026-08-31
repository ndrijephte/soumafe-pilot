<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUMAFE PILOT — Nouveau mot de passe</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Calibri,sans-serif}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#16385C 0%,#1F4E79 55%,#2E74B5 100%)}
        .carte{background:#fff;border-radius:16px;padding:38px 34px;width:400px;box-shadow:0 18px 50px rgba(0,0,0,.3)}
        .cercle{width:70px;height:70px;border-radius:50%;background:#1F4E79;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;border:3px solid #2E74B5}
        .cercle img{height:40px}
        h1{text-align:center;font-size:20px;color:#1F4E79}
        .sous{text-align:center;font-size:12px;color:#64748b;margin-bottom:22px}
        label{font-size:12px;color:#64748b;display:block;margin:12px 0 4px}
        input{width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px}
        input:focus{outline:2px solid #2E74B5}
        button{width:100%;margin-top:20px;padding:11px;background:#2E74B5;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer}
        button:hover{background:#1F4E79}
        .erreur{background:#fee2e2;color:#b91c1c;border-radius:8px;padding:8px 12px;font-size:12.5px;margin-top:12px}
        .aide{font-size:11px;color:#94a3b8;margin-top:4px}
        .retour{display:block;text-align:center;margin-top:16px;font-size:13px;color:#2E74B5;text-decoration:none}
        .retour:hover{text-decoration:underline}
    </style>
</head>
<body>
    <form class="carte" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <div class="cercle"><img src="{{ asset('images/white_logo_v.png') }}" alt="SOUMAFÊ"></div>
        <h1>Nouveau mot de passe</h1>
        <div class="sous">Définissez votre nouveau mot de passe</div>
        <label>Adresse e-mail</label>
        <input type="email" value="{{ $email }}" disabled style="background:#f8fafc;color:#64748b">
        <label>Nouveau mot de passe</label>
        <input type="password" name="password" required minlength="8" placeholder="8 caractères minimum">
        <div class="aide">Minimum 8 caractères</div>
        <label>Confirmer le mot de passe</label>
        <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirmez votre mot de passe">
        @if($errors->any())<div class="erreur">{{ $errors->first() }}</div>@endif
        <button>Réinitialiser</button>
        <a href="{{ route('login') }}" class="retour">&larr; Retour à la connexion</a>
    </form>
</body>
</html>
