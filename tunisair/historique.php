<?php
session_start();

// Vérification du rôle (seulement admin et gestionnaire)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?redirect=historique.php");
    exit();
}

if ($_SESSION['role'] === 'utilisateur') {
    die("<div style='color:red; font-weight:bold; text-align:center; margin-top:50px;'>
        Accès interdit : cette page est réservée aux administrateurs et gestionnaires.
    </div>");
}


// Vérifier si la réauthentification a été faite
if (!isset($_SESSION['auth_historique']) || $_SESSION['auth_historique'] !== true) {
    header("Location: login.php?redirect=historique.php&reauth=1");
    exit();
}



include 'menu.php'; 
include 'session_check.php';   // Vérification et expiration


// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer tous les enregistrements de la table historique, ordonnés par date_action croissante
$stmt = $pdo->query("SELECT * FROM historique ORDER BY date_action ASC");
$historiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Historique des modifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
       
    body {
        background-color: #f7f9fc;
        font-family: 'Inter', sans-serif;
        color: #222;
        padding: 30px;
        position: relative;
        min-height: 100vh;
        overflow-x: hidden;
    }

    

    nav.navbar {
        background-color: rgba(28, 58, 110, 0.85);
        margin-bottom: 30px;
        border-radius: 8px;
        padding: 10px 20px;
    }

    nav.navbar .nav-link {
        color: white !important;
        font-weight: 600;
        margin-right: 20px;
    }

    nav.navbar .nav-link.active {
        color: #a9b9d9 !important;
        font-weight: 700;
        text-decoration: underline;
    }

    h2 {
        color: #1c3a6e;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
    }
/* Style global de l'accordion */
.accordion-item {
    background-color: rgba(255, 255, 255, 0.85); /* fond semi-transparent */
    border: 1px solid rgba(28, 58, 110, 0.3);    /* bordure légère */
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Bouton de l'accordion */
.accordion-button {
    background-color: rgba(28, 58, 110, 0.1);   /* léger fond bleu transparent */
    color: #1c3a6e;
    font-weight: 600;
    border-radius: 8px;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(28, 58, 110, 0.2);   /* plus marqué quand ouvert */
    color: #0b2040;
}

.accordion-button::after {
    filter: invert(30%); /* flèche visible sur fond clair */
}

/* Contenu de l'accordion */
.accordion-body {
    background-color: rgba(255, 255, 255, 0.8); /* léger fond blanc transparent */
    color: #222;
    border-radius: 0 0 8px 8px;
}

</style>

</head>
<body>



<div class="container">
    <h2>Historique des modifications</h2>

    <?php if (empty($historiques)): ?>
        <div class="alert alert-info text-center">Aucun historique trouvé.</div>
    <?php else: ?>
        <div class="accordion" id="historiqueAccordion">
            <?php foreach ($historiques as $i => $histo): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $i ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>" aria-expanded="false" aria-controls="collapse<?= $i ?>">
                            <?= htmlspecialchars($histo['action']) ?> - <?= htmlspecialchars($histo['type_convention']) ?> (<?= htmlspecialchars($histo['date_action']) ?>)
                        </button>
                    </h2>
                    <div id="collapse<?= $i ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $i ?>" data-bs-parent="#historiqueAccordion">
                        <div class="accordion-body">
                            <p><strong>Champ :</strong> <?= htmlspecialchars($histo['champ_modifie']) ?></p>
                            <p><strong>Ancienne valeur :</strong> <?= nl2br(htmlspecialchars($histo['ancien_valeur'])) ?></p>
                            <p><strong>Nouvelle valeur :</strong> <?= nl2br(htmlspecialchars($histo['nouvelle_valeur'])) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Durée max de session en millisecondes
let sessionTimeout = 15* 60 * 1000; // 15 minutes

// Redirection automatique vers login
setTimeout(function() {
    window.location.href = "login.php?expired=1";
}, sessionTimeout);
</script>

</body>
</html>
