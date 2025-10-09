<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $result = $stmt->fetch();
    $maintenanceMode = ($result && $result['setting_value'] == '1');
} catch (PDOException $e) {
    $maintenanceMode = false;
}

session_start();
if (!$maintenanceMode || (isLoggedIn() && $_SESSION['user_role'] === 'admin')) {
    header('Location: ' . url('index.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - DigiBoostPro</title>
    <link rel="stylesheet" href="<?php echo url('style.css'); ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        .maintenance-box {
            background: white;
            padding: 60px 50px;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            text-align: center;
            max-width: 550px;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }
        
        .maintenance-icon {
            font-size: 6rem;
            margin-bottom: 24px;
            animation: rotate 3s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .maintenance-box h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .maintenance-box p {
            color: var(--gray);
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1.05rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: var(--light);
            border-radius: 3px;
            margin: 30px 0;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            animation: loading 2s ease-in-out infinite;
        }
        
        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <div class="maintenance-icon">🔧</div>
        <h1>Maintenance en cours</h1>
        <p>Notre plateforme est actuellement en maintenance pour améliorer votre expérience.</p>
        <p>Nous serons de retour très bientôt. Merci de votre patience !</p>
        
        <div class="progress-bar">
            <div class="progress-bar-fill"></div>
        </div>
        
        <p style="font-size: 0.9rem; margin-top: 30px;">
            <strong>DigiBoostPro</strong><br>
            <a href="mailto:support@digiboostpro.com" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Contactez le support</a>
        </p>
    </div>
</body>
</html>