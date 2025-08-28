<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Durée max de session en secondes
$session_timeout = 900; // 15 minutes

// Vérifier expiration côté serveur
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1"); // Redirection immédiate
    exit();
}

// Vérifier si connecté
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Mettre à jour le timestamp de dernière activité
$_SESSION['last_activity'] = time();
?>
