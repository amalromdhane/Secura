<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Secura') ?></title>
    <base href="/Secura/public/">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cyberaware.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
            background: var(--cyber-dark);
        }
        .auth-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 25% 25%, rgba(100, 149, 237, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 75% 75%, rgba(138, 43, 226, 0.06) 0%, transparent 35%),
                radial-gradient(circle at 50% 10%, rgba(65, 105, 225, 0.04) 0%, transparent 40%),
                linear-gradient(135deg, rgba(10, 14, 23, 0.95), rgba(15, 23, 42, 0.98));
            pointer-events: none;
        }
        .floating-shapes { position: absolute; inset: 0; pointer-events: none; z-index: 1; }
        .shape {
            position: absolute;
            background: linear-gradient(135deg, rgba(100, 149, 237, 0.08), rgba(138, 43, 226, 0.06));
            border: 1px solid rgba(100, 149, 237, 0.15);
            border-radius: 50%;
            animation: float 12s ease-in-out infinite;
            backdrop-filter: blur(8px);
        }
        .shape:nth-child(1) { width: 60px; height: 60px; top: 15%; left: 8%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 45px; height: 45px; top: 65%; right: 12%; animation-delay: 3s; }
        .shape:nth-child(3) { width: 75px; height: 75px; bottom: 25%; left: 18%; animation-delay: 6s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); opacity: 0.4; }
            50% { transform: translateY(-15px) rotate(2deg) scale(1.05); opacity: 0.7; }
        }
        .auth-card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(100, 149, 237, 0.2);
            border-radius: 24px;
            padding: 3.5rem;
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(25px);
            box-shadow: 0 25px 80px rgba(0,0,0,.4), 0 0 60px rgba(100,149,237,.08);
        }
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(100,149,237,.6) 20%,rgba(65,105,225,.8) 50%, rgba(100,149,237,.6) 80%, transparent 100%);
            border-radius: 24px 24px 0 0;
        }
        .auth-header { text-align: center; margin-bottom: 2.5rem; }
        .auth-logo {
            display: inline-flex; align-items: center; justify-content: center;
            width: 90px; height: 90px; border-radius: 24px;
            background: linear-gradient(135deg,rgba(100,149,237,.15),rgba(65,105,225,.12),rgba(138,43,226,.08));
            border: 2px solid rgba(100,149,237,.3);
            margin-bottom: 2rem; position: relative;
            box-shadow: 0 8px 32px rgba(100,149,237,.2);
        }
        .auth-logo i { font-size: 2.8rem; color: #60a5fa; filter: drop-shadow(0 2px 8px rgba(96,165,250,.3)); }
        .auth-title {
            font-size: 2rem; font-weight: 600;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-bottom: .75rem;
        }
        .auth-subtitle { color: rgba(148,163,184,.8); font-size: 1rem; margin-bottom: .75rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: .5rem; font-weight: 500; font-size: .9rem; text-transform: uppercase; letter-spacing: .5px; }
        .form-input-wrapper { position: relative; }
        .form-input {
            width: 90%; padding: 1.125rem 1.25rem; padding-left: 3.25rem;
            background: rgba(15,23,42,.7); border: 1px solid rgba(71,85,105,.3);
            border-radius: 16px; color: #f1f5f9; font-size: 1rem; transition: all .4s ease;
        }
        .form-input:focus { outline: none; border-color: rgba(96,165,250,.6); box-shadow: 0 0 0 3px rgba(96,165,250,.15); background: rgba(15,23,42,.9); }
        .input-icon { position: absolute; left: 1.125rem; top: 50%; transform: translateY(-50%); color: rgba(100,149,237,.8); font-size: 1.125rem; }
        .btn-auth {
            width: 100%; padding: 1.125rem 2rem;
            background: linear-gradient(135deg, rgba(100,149,237,.9), rgba(65,105,225,.9), rgba(138,43,226,.8));
            color: #fff; border: none; border-radius: 16px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 1rem;
            box-shadow: 0 4px 20px rgba(100,149,237,.3); transition: all .4s ease; position: relative; overflow: hidden;
        }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(100,149,237,.4); }
        .error-message, .success-message {
            padding: 1.125rem 1.375rem; border-radius: 16px; margin-bottom: 1.75rem;
            display: flex; align-items: center; gap: .875rem;
        }
        .error-message  { background: rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#fca5a5; }
        .success-message { background: rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.25); color:#86efac; }
        .auth-footer { text-align:center; margin-top:2.5rem; padding-top:2rem; border-top:1px solid rgba(71,85,105,.2); }
        .auth-footer a { color:#60a5fa; text-decoration:none; font-weight:500; }
        .password-toggle {
            position:absolute; right:1rem; top:50%; transform:translateY(-50%);
            background:rgba(71,85,105,.2); border:1px solid rgba(71,85,105,.3);
            border-radius:8px; color:rgba(148,163,184,.7); cursor:pointer; font-size:.875rem; padding:.375rem;
        }
        @media (max-width:576px) {
            .auth-card { padding:2.5rem 1.75rem; max-width:400px; }
            .auth-title { font-size:1.75rem; }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="floating-shapes">
            <div class="shape"></div><div class="shape"></div><div class="shape"></div>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo"><i class="fas fa-shield-alt"></i></div>
                <h1 class="auth-title"><?= htmlspecialchars($pageTitle ?? 'Connexion') ?></h1>
                <p class="auth-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Accédez à votre espace sécurisé') ?></p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i>
                <span><?= $success ?></span></div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <form method="POST" action="">
                <?= $formBody ?? '' ?>
                <button type="submit" class="btn-auth"><?= htmlspecialchars($submitLabel ?? 'Se Connecter') ?></button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><?= htmlspecialchars($footerText ?? 'Pas encore de compte?') ?>
                    <a href="<?= htmlspecialchars($footerLink ?? 'register.php') ?>"><?= htmlspecialchars($footerLinkLabel ?? "S'inscrire") ?></a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(btn) {
            const wrapper = btn.parentElement;
            const input   = wrapper.querySelector('.form-input');
            const icon    = btn.querySelector('i');
            if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
            else                           { input.type = 'password'; icon.className = 'fas fa-eye'; }
        }
    </script>
</body>
</html>
