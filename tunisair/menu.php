<?php
// Assure que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si l'utilisateur est connecté
$role = $_SESSION['role'] ?? null;
?>

<nav class="navbar navbar-expand-lg fixed-top" style="background-color: rgba(28, 58, 110, 0.85);">
    <div class="container">
        <a class="navbar-brand text-white fw-bold" href="index.php">Portail PNC</a>
        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">

                <li class="nav-item">
                    <a class="nav-link text-white fw-semibold" href="afficher.php">Afficher</a>
                </li>

                <?php if ($role === 'gestionnaire'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="modifier.php">Modifier</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="login.php?redirect=supprimer.php&reauth=1">Supprimer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="login.php?redirect=historique.php&reauth=1">Historique</a>
                    </li>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="ajouter.php">Ajouter</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="modifier.php">Modifier</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="login.php?redirect=supprimer.php&reauth=1">Supprimer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-semibold" href="login.php?redirect=historique.php&reauth=1">Historique</a>
                    </li>
                <?php endif; ?>

                <?php if ($role): ?>
                    <li class="nav-item ms-2">
                        <a href="logout.php" class="btn btn-outline-light">Déconnexion</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- Padding pour éviter que le contenu soit caché derrière la navbar -->
<div style="padding-top: 70px;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
