<?php
// Empêcher le cache pour éviter affichage après logout
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

session_start();

// --- Initialisation des messages ---
$error = '';
$message = '';

// --- Gestion expiration de session (10 min = 600s) ---
$session_timeout = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    session_start();
    $error = "Votre session a expiré. Veuillez vous reconnecter.";
}

$_SESSION['last_activity'] = time();

// --- Gestion des raisons de redirection ---
if (isset($_GET['reauth']) && $_GET['reauth'] == 1) {
    $message = "Ré-authentification obligatoire pour accéder à cette page.";
}

if (isset($_GET['interdit']) && $_GET['interdit'] == 1) {
    $error = "Accès interdit : vous n’avez pas le droit d’accéder à cette page.";
}

if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "Votre session a expiré. Veuillez vous reconnecter.";
}

// --- Connexion PDO ---
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$redirectTo = $_GET['redirect'] ?? 'index.php';

// --- Traitement du formulaire de connexion ---
if (isset($_POST['login_submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Création de la session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['last_activity'] = time();

            /// login.php
if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['role'] = $admin['role'];
    $_SESSION['last_activity'] = time();

    // Ne plus bloquer ici
    header("Location: $redirectTo");
    exit();
}

        } else {
            $error = (isset($_GET['reauth']) && $_GET['reauth'] == 1)
                ? "Ré-authentification échouée. Veuillez saisir un compte valide."
                : "Identifiants invalides.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail PNC - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 60px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 8px;
        }
        .btn-primary {
            border-radius: 8px;
            background-color: darkblue;
        }
        .extra-links {
            margin-top: 15px;
            text-align: center;
        }
        .extra-links a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Adresse Email</label>
                <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="********" required>
            </div>
            <button type="submit" name="login_submit" class="btn btn-primary w-100">Se connecter</button>
        </form>

        <?php if (isset($_SESSION['admin_id'])): ?>
            <div class="extra-links">
                <a href="logout.php">Déconnexion</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
