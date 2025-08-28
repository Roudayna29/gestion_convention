<?php
session_start();

// Vérifier si connecté
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?redirect=afficher.php");
    exit();
}
include 'menu.php'; 
include 'session_check.php';   // Vérification et expiration

// Récupérer le rôle
$role = $_SESSION['role'] ?? '';

// Autorisation selon rôle
if (!in_array($role, ['utilisateur','admin','gestionnaire'])) {
    header("Location: login.php?redirect=afficher.php");
    exit();
}

// Connexion à la base
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des conventions
try {
    $stmt = $pdo->query("
        SELECT c.*, t.libelle AS type_libelle 
        FROM convention c 
        LEFT JOIN type_convention t ON c.type_id = t.id
    ");
    $conventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer tous les types distincts pour le filtre
    $stmtTypes = $pdo->query("SELECT id, libelle FROM type_convention ORDER BY libelle");
    $types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
$aujourdhui = date('Y-m-d');

// Filtrer si user connecté : masquer les expirées
if ($role === 'utilisateur') {
    $conventions = array_filter($conventions, function($c) use ($aujourdhui) {
        return $c['date_fin'] >= $aujourdhui;
    });
}


// Vérifier si un filtre est appliqué
$filtreType = $_GET['type_id'] ?? '';
if ($filtreType !== '') {
    $conventions = array_filter($conventions, function($c) use ($filtreType) {
        return $c['type_id'] == $filtreType;
    });
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Liste des conventions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Inter', sans-serif;
            color: #222;
            padding: 100px 15px 40px 15px;
        }
        nav.navbar {
            background-color: #1c3a6e;
        }
        nav.navbar .container a.navbar-brand,
        nav.navbar .navbar-nav .nav-link {
            color: white;
            font-weight: 600;
        }
        nav.navbar .navbar-nav .nav-link:hover {
            text-decoration: underline;
            color: #a9b9d9;
        }
        h2 {
            text-align: center;
            color: #1c3a6e;
            font-weight: 700;
            margin-bottom: 30px;
        }
        .filter-form {
            max-width: 400px;
            margin: 0 auto 40px auto;
            display: flex;
            gap: 10px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(28, 58, 110, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card img {
            object-fit: cover;
            height: 280px;
            width: 100%;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 1.5rem;
            color: #1c3a6e;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .card-subtitle {
            font-size: 1rem;
            color: #5a5a5a;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .card-text {
            color: #555;
        }
        .info-label {
            font-weight: 600;
            color: #1c3a6e;
        }
        .empty {
            text-align: center;
            font-size: 1.1rem;
            color: #555;
            margin-top: 40px;
        }
    </style>
</head>
<body>





<h2>Liste des conventions</h2>

<!-- Formulaire de filtre -->
<form class="filter-form" method="get">
    <select class="form-select" name="type_id" onchange="this.form.submit()">
        <option value="">-- Filtrer par type --</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($filtreType == $t['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['libelle']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($filtreType !== ''): ?>
        <a href="afficher.php" class="btn btn-secondary">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if (empty($conventions)): ?>
    <div class="empty">Aucune convention enregistrée pour le moment.</div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($conventions as $c): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= htmlspecialchars($c['titre']) ?>
                            <?php if ($c['date_fin'] < $aujourdhui): ?>
                                <span class="badge bg-danger ms-2" title="Convention expirée">Expirée</span>
                            <?php endif; ?>
                        </h5>
                        <h6 class="card-subtitle"><?= htmlspecialchars($c['type_libelle']) ?></h6>
                    </div>
                    <?php if (!empty($c['image'])):
                        $images = explode(';', $c['image']);
                        if (count($images) > 1): ?>
                            <div id="carousel-<?= $c['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach ($images as $index => $img):
                                        $img = trim($img);
                                        if ($img === '') continue;
                                    ?>
                                    <div class="carousel-item <?= ($index === 0) ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Image convention" style="height:280px; object-fit:cover; border-radius:12px;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $c['id'] ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Précédent</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $c['id'] ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Suivant</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars(trim($images[0])) ?>" alt="Image convention" style="height:280px; width:100%; object-fit:cover; border-radius:12px;">
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    




                    <?php if (!empty($c['nom'])): ?>
    <p class="card-text"><span class="info-label">Nom : </span><?= htmlspecialchars($c['nom']) ?></p>
<?php endif; ?>

<?php if (!empty($c['date_debut']) && !empty($c['date_fin'])): ?>
    <p class="card-text"><span class="info-label">Dates : </span><?= htmlspecialchars($c['date_debut']) ?> → <?= htmlspecialchars($c['date_fin']) ?></p>
<?php endif; ?>

<?php if (!empty($c['responsable'])): ?>
    <p class="card-text"><span class="info-label">Responsable : </span><?= htmlspecialchars($c['responsable']) ?></p>
<?php endif; ?>

<?php if (!empty($c['tel'])): ?>
    <p class="card-text"><span class="info-label">Téléphone : </span><?= htmlspecialchars($c['tel']) ?></p>
<?php endif; ?>

<?php if (!empty($c['adresse'])): ?>
    <p class="card-text"><span class="info-label">Adresse: </span><?= htmlspecialchars($c['adresse']) ?></p>
<?php endif; ?>

<?php if (!empty($c['email'])): ?>
    <p class="card-text"><span class="info-label">Email: </span><?= htmlspecialchars($c['email']) ?></p>
<?php endif; ?>

<?php if (!empty($c['infos_sup'])): ?>
    <p class="card-text"><span class="info-label">Infos sup : </span><?= htmlspecialchars($c['infos_sup']) ?></p>
<?php endif; ?>
<?php if (!empty($c['piece_jointe'])): ?>
    <div class="mt-3">
        <h6>Pièces jointes :</h6>
        <ul class="list-unstyled">
            <?php 
            $pieces = explode(';', $c['piece_jointe']);
            foreach ($pieces as $pj): 
                $pj = trim($pj);
                if ($pj === '') continue;
                $ext = strtoupper(pathinfo($pj, PATHINFO_EXTENSION));
            ?>
                <li>
                    <a href="<?= htmlspecialchars($pj) ?>" target="_blank" class="text-decoration-none">
                        📎 <?= basename($pj) ?> (<?= $ext ?>)
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>


                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<script>
// Durée max de session en millisecondes
let sessionTimeout = 15 * 60 * 1000; // 15 minutes

// Redirection automatique vers login
setTimeout(function() {
    window.location.href = "login.php?expired=1";
}, sessionTimeout);
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
