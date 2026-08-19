<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:30px 0">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden">
    {{-- En-tête --}}
    <tr><td style="background:#1F4E79;padding:20px 30px;text-align:center">
        <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:.5px">SOUMAFE PILOT</span>
    </td></tr>
    {{-- Corps --}}
    <tr><td style="padding:28px 30px;color:#334155;font-size:14px;line-height:1.7">
        <p style="margin:0 0 12px">Bonjour <strong>{{ $nom }}</strong>,</p>
        <p style="margin:0 0 16px">{!! nl2br(e($contenu)) !!}</p>
        @if($lien)
        <p style="margin:16px 0 0;text-align:center">
            <a href="{{ $lien }}" style="display:inline-block;background:#2E74B5;color:#ffffff;text-decoration:none;padding:10px 28px;border-radius:8px;font-weight:600;font-size:14px">Voir dans l'application</a>
        </p>
        @endif
    </td></tr>
    {{-- Pied --}}
    <tr><td style="background:#f8fafc;padding:16px 30px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8;text-align:center">
        Cet e-mail a été envoyé automatiquement par SOUMAFE PILOT.<br>
        Merci de ne pas y répondre directement.
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
