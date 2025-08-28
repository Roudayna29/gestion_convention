<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include 'session_check.php';   // Vérification et expiration

// Récupérer le rôle
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Portail PNC - Accueil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color:#f7f9fc; font-family:'Inter',sans-serif; color:#222; padding:30px; }
h1 { color:#1c3a6e; font-weight:700; text-align:center; margin-bottom:50px; }
.container-cards { max-width:500px; margin:0 auto; }
.row-cards { display:flex; flex-direction:column; gap:25px; }
.card-option { background-color:#1c3a6e; color:white; border-radius:16px; box-shadow:0 6px 20px rgba(28,58,110,0.4); cursor:pointer; transition:0.3s; text-align:left; padding:40px 30px; font-weight:700; font-size:1.4rem; display:flex; align-items:center; gap:15px; height:120px; text-decoration:none; }
.card-option:hover { background-color:#2a4a8f; transform:translateY(-6px); box-shadow:0 10px 30px rgba(28,58,110,0.5); }
.card-option i { font-size:2rem; color:#ffdd57; }
@media (max-width:600px) { .container-cards { width:90%; } .card-option { height:100px; font-size:1.3rem; padding:30px 20px; } .card-option i { font-size:1.8rem; } }
</style>
</head>
<body>

<h1>Bienvenue sur le Portail PNC</h1>

<div class="container-cards">
<div class="row-cards">

<?php if ($role === 'admin'): ?>
    <a href="ajouter.php" class="card-option"><i class="bi bi-plus-circle"></i> Ajouter une convention</a>
<?php endif; ?>

<?php if ($role === 'admin' || $role === 'gestionnaire'): ?>
    <a href="modifier.php" class="card-option"><i class="bi bi-pencil-square"></i> Modifier une convention</a>
    <a href="supprimer.php" class="card-option"><i class="bi bi-trash"></i> Supprimer une convention</a>
<?php endif; ?>

<a href="afficher.php" class="card-option"><i class="bi bi-eye"></i> Afficher les conventions</a>

<?php if ($role === 'admin'): ?>
    <a href="historique.php" class="card-option"><i class="bi bi-clock-history"></i> Historique</a>
<?php endif; ?>

<!-- Déconnexion pour tout le monde -->
<a href="logout.php" class="card-option"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>

</div>
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
