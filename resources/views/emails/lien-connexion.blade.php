<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                <tr><td style="background:#4f46e5;padding:20px 28px;color:#ffffff;font-size:18px;font-weight:700;">
                    ★ Note ta boîte
                </td></tr>
                <tr><td style="padding:28px;color:#0f172a;">
                    <h1 style="margin:0 0 12px;font-size:20px;">Votre connexion en un clic</h1>
                    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">
                        Cliquez sur le bouton ci-dessous pour vous connecter (ou créer votre compte). Ce lien est valable
                        <strong>30 minutes</strong> et ne fonctionne qu'une seule fois.
                    </p>
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                        <tr><td style="border-radius:10px;background:#4f46e5;">
                            <a href="{{ $url }}" style="display:inline-block;padding:13px 26px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px;">
                                Me connecter
                            </a>
                        </td></tr>
                    </table>
                    <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Si le bouton ne marche pas, copiez ce lien :</p>
                    <p style="margin:0;font-size:12px;word-break:break-all;"><a href="{{ $url }}" style="color:#4f46e5;">{{ $url }}</a></p>
                    <p style="margin:20px 0 0;font-size:12px;color:#94a3b8;">
                        Vous n'avez pas demandé ce lien ? Ignorez simplement cet email.
                    </p>
                </td></tr>
            </table>
            <p style="margin:16px 0 0;font-size:11px;color:#94a3b8;">notetaboite.com — les entreprises notées par ceux qui y travaillent</p>
        </td></tr>
    </table>
</body>
</html>
