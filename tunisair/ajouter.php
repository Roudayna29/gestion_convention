<?php
session_start();

// Si pas connecté, rediriger vers login
// Vérification côté PHP dans ajouter.php
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?redirect=ajouter.php");
    exit();
}
include 'menu.php'; 
include 'session_check.php';   // Vérification et expiration

// Vérifier le rôle
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'gestionnaire')) {
    die("Accès refusé : seuls les administrateurs et gestionnaires peuvent ajouter des conventions.");
}



// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=portail_pnc', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$messageConvention = "";
$messageType = "";

$types = $pdo->query("SELECT * FROM type_convention")->fetchAll();

// Limites (en octets)
$MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 Mo
$MAX_PIECE_SIZE = 10 * 1024 * 1024; // 10 Mo

// Détection du formulaire soumis
$formulaire = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- FORM CONVENTION ---
    if (isset($_POST['form_type']) && $_POST['form_type'] === "convention") {
        $formulaire = "convention";

        // Récupération données convention
        $titre = trim($_POST['titre'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $description = $_POST['description'] ?? '';
        $responsable = trim($_POST['responsable'] ?? '');
        $tel = trim($_POST['tel'] ?? '');
        $type_id = $_POST['type_id'] ?? '';

        // Nouveaux champs
        $adresse = trim($_POST['adresse'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $infos_sup = trim($_POST['infos_sup'] ?? '');

        // Validation champs obligatoires et format email
        if (empty($titre)) {
            $messageConvention = "<div class='alert alert-danger'>Merci d'insérer le titre</div>";
        } elseif (empty($tel)) {
            $messageConvention = "<div class='alert alert-danger'>Merci d'insérer le numéro de téléphone</div>";
        } elseif (empty($responsable)) {
            $messageConvention = "<div class='alert alert-danger'>Merci d'entrer le responsable</div>";
        } elseif (empty($type_id)) {
            $messageConvention = "<div class='alert alert-danger'>Veuillez sélectionner un type de convention.</div>";
        } elseif (empty($email)) {
    $messageConvention = "<div class='alert alert-danger'>Merci d'entrer l'adresse email.</div>";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $messageConvention = "<div class='alert alert-danger'>L'adresse email n'est pas valide.</div>";
}
else {
            // Vérifier si titre déjà existant
            $sql = "SELECT COUNT(*) FROM convention WHERE titre = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titre]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $messageConvention = "<div class='alert alert-danger'>Erreur : une convention avec ce titre existe déjà. Veuillez choisir un titre différent.</div>";
            } elseif (!empty($date_debut) && !empty($date_fin) && (strtotime($date_fin) < strtotime($date_debut))) {
                $messageConvention = "<div class='alert alert-danger'>La date de fin doit être postérieure ou égale à la date de début.</div>";
            } elseif (
                !preg_match("/^[A-Za-zÀ-ÿ\s]+$/", $titre) ||
                !preg_match("/^[A-Za-zÀ-ÿ\s]+$/", $nom) ||
                !preg_match("/^[A-Za-zÀ-ÿ\s]+$/", $responsable) ||
                !preg_match("/^\d{8}$/", $tel)
            ) {
                $messageConvention = "<div class='alert alert-danger'>Veuillez respecter les règles de saisie pour les champs.</div>";
            } else {
                // Préparer dossiers
                if (!is_dir('images')) mkdir('images', 0777, true);
                if (!is_dir('pieces')) mkdir('pieces', 0777, true);

                // Gérer les images multiples (côté serveur — double sécurité)
                $image_paths = [];
                if (!empty($_FILES["image"]["name"][0])) {
                    $imageFiles = $_FILES["image"];
                    foreach ($imageFiles['tmp_name'] as $key => $tmpName) {
                        // s'il y a un fichier à cet index
                        if (isset($imageFiles['name'][$key]) && $imageFiles['name'][$key] !== '') {
                            if ($imageFiles['error'][$key] !== UPLOAD_ERR_OK) {
                                $messageConvention = "<div class='alert alert-danger'>Erreur lors de l'upload d'une image (erreur code: " . intval($imageFiles['error'][$key]) . ").</div>";
                                break;
                            }
                            if ($imageFiles['size'][$key] > $MAX_IMAGE_SIZE) {
                                $messageConvention = "<div class='alert alert-danger'>Une des images dépasse la taille maximale autorisée de 5 Mo.</div>";
                                break;
                            }
                            $image_type = mime_content_type($tmpName);
                            if (!str_starts_with($image_type, 'image/')) {
                                $messageConvention = "<div class='alert alert-danger'>Un des fichiers sélectionnés pour l'image n'est pas valide (doit être une image).</div>";
                                break;
                            }
                            $image_name = basename($imageFiles['name'][$key]);
                            $destPath = "images/" . uniqid() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $image_name);
                            if (!move_uploaded_file($tmpName, $destPath)) {
                                $messageConvention = "<div class='alert alert-danger'>Erreur lors du téléchargement d'une image.</div>";
                                break;
                            }
                            $image_paths[] = $destPath;
                        }
                    }
                }

                // Gérer les pièces jointes multiples
                $piece_paths = [];
                if (!empty($_FILES["piece"]["name"][0])) {
                    $pieceFiles = $_FILES["piece"];
                    foreach ($pieceFiles['tmp_name'] as $key => $tmpName) {
                        if (isset($pieceFiles['name'][$key]) && $pieceFiles['name'][$key] !== '') {
                            if ($pieceFiles['error'][$key] !== UPLOAD_ERR_OK) {
                                $messageConvention = "<div class='alert alert-danger'>Erreur lors de l'upload d'une pièce jointe (erreur code: " . intval($pieceFiles['error'][$key]) . ").</div>";
                                break;
                            }
                            if ($pieceFiles['size'][$key] > $MAX_PIECE_SIZE) {
                                $messageConvention = "<div class='alert alert-danger'>Une des pièces jointes dépasse la taille maximale autorisée de 10 Mo.</div>";
                                break;
                            }
                            $piece_name = basename($pieceFiles['name'][$key]);
                            $destPath = "pieces/" . uniqid() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $piece_name);
                            if (!move_uploaded_file($tmpName, $destPath)) {
                                $messageConvention = "<div class='alert alert-danger'>Erreur lors du téléchargement d'une pièce jointe.</div>";
                                break;
                            }
                            $piece_paths[] = $destPath;
                        }
                    }
                }

                // Si pas d'erreur durant upload
                if (empty($messageConvention)) {
                    try {
                        // Convertir les chemins en chaîne séparée par ;
                        $images_concat = implode(";", $image_paths);
                        $pieces_concat = implode(";", $piece_paths);

                        // Insertion dans la table convention
                        $stmt = $pdo->prepare("INSERT INTO convention 
                            (titre, nom, date_debut, date_fin, description, responsable, tel, adresse, email, infos_sup, image, piece_jointe, type_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $titre, $nom, $date_debut, $date_fin, $description,
                            $responsable, $tel, $adresse, $email, $infos_sup, $images_concat, $pieces_concat, $type_id
                        ]);

                        // Récupérer le libellé du type de convention
                        $stmtType = $pdo->prepare("SELECT libelle FROM type_convention WHERE id = ?");
                        $stmtType->execute([$type_id]);
                        $type_convention = $stmtType->fetchColumn();

                        // Insertion dans la table historique avec type_convention
                        $stmtHist = $pdo->prepare("INSERT INTO historique (action, titre_convention, type_convention, date_action) VALUES (?, ?, ?, NOW())");
                        $stmtHist->execute(["Ajout", $titre, $type_convention]);

                        $messageConvention = "<div class='alert alert-success'>La convention a été ajoutée avec succès et l'historique mis à jour.</div>";

                        // Optionnel : afficher mini galerie des fichiers uploadés (immédiat après insertion)
                        if (!empty($image_paths)) {
                            $messageConvention .= "<div class='mt-2'><strong>Images enregistrées :</strong><div style='margin-top:8px;'>";
                            foreach ($image_paths as $p) {
                                $messageConvention .= "<img src='" . htmlspecialchars($p) . "' style='max-width:120px;margin:6px;border:1px solid #ddd;padding:4px;' />";
                            }
                            $messageConvention .= "</div></div>";
                        }
                        if (!empty($piece_paths)) {
                            $messageConvention .= "<div class='mt-2'><strong>Pièces enregistrées :</strong><br/>";
                            foreach ($piece_paths as $p) {
                                $messageConvention .= "<a href='" . htmlspecialchars($p) . "' target='_blank'>" . htmlspecialchars(basename($p)) . "</a><br/>";
                            }
                            $messageConvention .= "</div>";
                        }

                        // reset POST values after success so form clears
                        $_POST = [];
                    } catch (PDOException $e) {
                        $messageConvention = "<div class='alert alert-danger'>Erreur lors de l'insertion : " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
            }
        }
    }
    // --- FORM TYPE ---
    elseif (isset($_POST['form_type']) && $_POST['form_type'] === "type") {
        $formulaire = "type";

        $libelle = trim($_POST['libelle_type'] ?? '');
        $code = trim($_POST['code_type'] ?? '');

        if (empty($libelle)) {
            $messageType = "<div class='alert alert-danger'>Merci d'insérer un libellé pour le type.</div>";
        } else {
            if (empty($code)) {
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM type_convention");
                $countTypes = $stmtCount->fetchColumn();
                $code = 'TYPE' . str_pad($countTypes + 1, 3, '0', STR_PAD_LEFT);
            }

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM type_convention WHERE libelle = ? OR code = ?");
            $stmtCheck->execute([$libelle, $code]);
            $exist = $stmtCheck->fetchColumn();

            if ($exist > 0) {
                $messageType = "<div class='alert alert-danger'>Erreur : ce libellé ou code existe déjà.</div>";
            } else {
                try {
                    $stmtInsert = $pdo->prepare("INSERT INTO type_convention (libelle, code, date_ajout) VALUES (?, ?, NOW())");
                    $stmtInsert->execute([$libelle, $code]);

                    $stmtHist = $pdo->prepare("INSERT INTO historique (action, titre_convention, type_convention, date_action) VALUES (?, ?, ?, NOW())");
                    $stmtHist->execute(["Ajout Type", $libelle, $code]);

                    $messageType = "<div class='alert alert-success'>Le type a été ajouté avec succès.</div>";

                    $types = $pdo->query("SELECT * FROM type_convention")->fetchAll();
                } catch (PDOException $e) {
                    $messageType = "<div class='alert alert-danger'>Erreur lors de l'insertion du type : " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
}
// --- Gestion affichage automatique des formulaires si message existe ---
$showFormConvention = false;
$showFormType = false;

if ($formulaire === "convention" || !empty($messageConvention)) {
    $showFormConvention = true;
}

if ($formulaire === "type" || !empty($messageType)) {
    $showFormType = true;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Ajouter une convention ou un type</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
/* === Corps et arrière-plan === */
body {
    background-color: #f7f9fc;
    font-family: 'Inter', sans-serif;
    color: #222;
    
    background-size: cover;
    padding: 30px;
}



nav.navbar { 
    background-color: #1c3a6e; 
}
nav.navbar .nav-link, nav.navbar .navbar-brand { 
    color:white; 
    font-weight:600; 
}

/* === Titres === */
h2 {
    color: #1c3a6e;
    text-align: center;
    margin-bottom: 30px;
}

/* === Cartes de choix (type convention / type) === */
.choice-card {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
    padding: 50px 30px;
    flex: 1; /* largeur fixe plus petite */
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
    user-select: none;
    position: relative;
    margin: 0 auto; /* centre horizontalement */
}

.choice-card:hover {
    transform: scale(1.05);
    box-shadow: 0 20px 45px rgba(0,0,0,0.35);
    background: rgba(255,255,255,0.35);
}
.choice-card input[type="radio"] {
    display: none;
}
.choice-card input[type="radio"]:checked + label {
    transform: scale(1.08);
    box-shadow: 0 22px 50px rgba(0,0,0,0.45);
    background: rgba(255,255,255,0.45);
}
.choice-card .card-title {
    font-weight: 700;
    font-size: 1.8rem;
    color: #1c3a6e;
    transition: color 0.3s ease;
}
.choice-card:hover .card-title,
.choice-card input[type="radio"]:checked + label {
    color: #004080;
}

/* === Cartes de formulaire === */
.card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;         /* moins d’espace intérieur */
    text-align: center;
    width: 10px;          /* plus petite largeur */
    cursor: pointer;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}.d-flex.justify-content-center.gap-4.mb-4 {
    justify-content: center; /* déjà centré, mais s'assure */
    gap: 20px; /* espace plus petit entre les cartes */
}


form {
    max-width: 700px; /* largeur maximale */
    margin: 0 auto 30px auto; /* centré horizontalement + marge en bas */
    padding: 20px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    backdrop-filter: blur(10px);
}


/* === Formulaires === */
.form-label {
    font-weight: 600;
    color: #1c3a6e;
}
.form-select, .form-control {
    border-radius: 6px;
    border: 1.5px solid #a9b9d9;
}

button {
    background-color: #1c3a6e;
    color: white;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    padding: 10px 24px; /* légèrement moins large */
    display: block;
    margin: 20px auto 0 auto; /* centré horizontalement */
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background-color: #004080;
}

/* === Alertes === */
.alert {
    max-width: 600px;
    margin: 0 auto 20px auto;
    font-weight: 600;
    border-radius: 8px;
}

/* === Images et pièces jointes === */
#imagesPreview img {
    max-width: 120px;
    margin: 6px;
    border: 1px solid #ddd;
    padding: 4px;
    border-radius: 6px;
}
.file-list .file-item {
    padding: 5px 8px;
    background: #f0f0f0;
    border-radius: 6px;
    margin-bottom: 5px;
}
.error-text {
    color: #a00;
    font-weight: 600;
    margin-top: 4px;
}
</style>



</head>
<body>
<div class="background"></div>


<h2>Ajouter une convention ou un type</h2>

<div class="d-flex justify-content-center gap-4 mb-4">
  <?php if ($_SESSION['role'] === 'admin'): ?>
<div class="choice-card">
    <input type="radio" id="radio_convention" name="form_type" value="convention" />
    <label for="radio_convention">
        <div class="card-title">Ajouter une convention</div>
    </label>
</div>
<?php endif; ?>

    <div class="choice-card">
        <input type="radio" id="radio_type" name="form_type" value="type" />
        <label for="radio_type">
            <div class="card-title">Ajouter un type de convention</div>
        </label>
    </div>
</div>


<!-- Formulaire Convention -->
<?php
// Déterminer si le formulaire convention doit être affiché
$showFormConvention = ($formulaire === "convention" || !empty($messageConvention));
?>
<form method="POST" enctype="multipart/form-data" id="formConvention" 
      class="<?= ($formulaire === "convention") ? "active" : "" ?>" 
      style="display: <?= $showFormConvention ? 'block' : 'none' ?>;" novalidate>
    <input type="hidden" name="form_type" value="convention" />
    <?php if (!empty($messageConvention)) echo $messageConvention; ?>

    <div class="mb-3">
        <label for="titre" class="form-label">Titre</label>
        <input type="text" name="titre" id="titre" class="form-control" required
               pattern="^[A-Za-zÀ-ÿ\s]+$"
               title="Uniquement des lettres sans chiffres ni caractères spéciaux."
               value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
               <div id="titreError" class="text-danger mt-1"></div>
        <div class="form-text">Uniquement des lettres (A-Z) et espaces, pas de chiffres ni caractères spéciaux.</div>
    </div>

    <div class="mb-3">
        <label for="nom" class="form-label">Nom</label>
        <input type="text" name="nom" id="nom" class="form-control"
               pattern="^[A-Za-zÀ-ÿ\s]+$"
               title="Uniquement des lettres sans chiffres ni caractères spéciaux."
               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
               <div id="nomError" class="text-danger mt-1"></div>
        <div class="form-text">Uniquement des lettres (A-Z) et espaces, pas de chiffres ni caractères spéciaux.</div>
    </div>

    <div class="mb-3">
        <label for="date_debut" class="form-label">Date début</label>
        <input type="date" name="date_debut" id="date_debut" class="form-control" required
               value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="date_fin" class="form-label">Date fin</label>
        <input type="date" name="date_fin" id="date_fin" class="form-control" required
               value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <div id="descriptionError" class="text-danger mt-1"></div>

    </div>

    <div class="mb-3">
        <label for="responsable" class="form-label">Responsable</label>
        <input type="text" name="responsable" id="responsable" class="form-control"
               pattern="^[A-Za-zÀ-ÿ\s]+$"
               title="Uniquement des lettres sans chiffres ni caractères spéciaux."
               value="<?= htmlspecialchars($_POST['responsable'] ?? '') ?>">
               <div id="responsableError" class="text-danger mt-1"></div>
        <div class="form-text">Uniquement des lettres (A-Z) et espaces, pas de chiffres ni caractères spéciaux.</div>
    </div>

    <div class="mb-3">
        <label for="tel" class="form-label">Téléphone</label>
        <input type="text" name="tel" id="tel" class="form-control" required
               pattern="^\d{8}$"
               title="Le numéro doit contenir exactement 8 chiffres."
               value="<?= htmlspecialchars($_POST['tel'] ?? '') ?>">
               <div id="telError" class="text-danger mt-1"></div>
        <div class="form-text">Doit contenir exactement 8 chiffres, pas d'espaces ni autres caractères.</div>
    </div>

    <!-- Nouveau champ Adresse -->
    <div class="mb-3">
        <label for="adresse" class="form-label">Adresse</label>
        <input type="text" name="adresse" id="adresse" class="form-control"
               value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
    </div>

    <!-- Nouveau champ Email -->
    <div class="mb-3">
        <label for="email" class="form-label">Adresse Email</label>
        <input type="email" name="email" id="email" class="form-control" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
               <div id="emailError" class="text-danger mt-1"></div>
         <div class="form-text">Doit  etre de la forme exemple@gmail.com.</div>
        
    </div>

    <!-- Nouveau champ Infos supplémentaires -->
    <div class="mb-3">
        <label for="infos_sup" class="form-label">Informations supplémentaires</label>
        <textarea name="infos_sup" id="infos_sup" class="form-control"><?= htmlspecialchars($_POST['infos_sup'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="type_id" class="form-label">Type de convention</label>
        <select name="type_id" id="type_id" class="form-select" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>" <?= (isset($_POST['type_id']) && $_POST['type_id'] == $type['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['libelle']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Multi-images -->
    <div class="mb-3">
        <label for="image" class="form-label">Images (plusieurs possibles)</label>
        <input type="file" name="image[]" id="image" class="form-control" accept="image/*" multiple>
        <div class="form-text">Vous pouvez sélectionner plusieurs images (max 5 Mo chacune).</div>
        <div id="imageError" class="error-text" aria-live="polite"></div>
        <div id="imagesPreview" class="d-flex flex-wrap" style="margin-top:8px;"></div>
    </div>

    <!-- Multi-pièces jointes -->
    <div class="mb-3">
        <label for="piece" class="form-label">Pièces jointes (plusieurs possibles)</label>
        <input type="file" name="piece[]" id="piece" class="form-control" multiple>
        <div class="form-text">Vous pouvez sélectionner plusieurs fichiers (max 10 Mo chacun).</div>
        <div id="pieceError" class="error-text" aria-live="polite"></div>
        <div id="piecesList" class="file-list"></div>
    </div>

    <button type="submit">Enregistrer la convention</button>
</form>

<!-- Formulaire Type reste inchangé -->

<form method="POST" id="formType" class="<?= ($formulaire === "type") ? "active" : "" ?>" novalidate>
    <input type="hidden" name="form_type" value="type" />
    <?php if (!empty($messageType)) echo $messageType; ?>

    <div class="mb-3">
        <label for="libelle_type" class="form-label">Libellé du type</label>
        <input type="text" name="libelle_type" id="libelle_type" class="form-control" required
               value="<?= htmlspecialchars($_POST['libelle_type'] ?? '') ?>">
        <div class="form-text">Nom du type (ex : "Type A")</div>
    </div>
    <div class="mb-3">
        <label for="code_type" class="form-label">Code du type (optionnel)</label>
        <input type="text" name="code_type" id="code_type" class="form-control"
               value="<?= htmlspecialchars($_POST['code_type'] ?? '') ?>">
        <div class="form-text">Code unique (ex : "TYPE001"). Si vide, généré automatiquement.</div>
    </div>
    <button type="submit">Enregistrer le type</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const formConvention = document.getElementById('formConvention');
    const formType = document.getElementById('formType');
    const radios = document.querySelectorAll('input[name="form_type"]');

    // Affichage initial selon PHP
    formConvention.style.display = <?= $showFormConvention ? "'block'" : "'none'" ?>;
    formType.style.display = <?= $showFormType ? "'block'" : "'none'" ?>;

    // Changement via les radios
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'convention') {
                formConvention.style.display = 'block';
                formType.style.display = 'none';
            } else if (this.value === 'type') {
                formType.style.display = 'block';
                formConvention.style.display = 'none';
            }
        });
    });
});



document.addEventListener('DOMContentLoaded', function () {
    const titre = document.getElementById('titre');
    const nom = document.getElementById('nom');
    const responsable = document.getElementById('responsable');
    const tel = document.getElementById('tel');
    const email = document.getElementById('email');
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    const dateError = document.createElement('div');
    
    // --- Validation lettres ---
    function validateText(input, errorId) {
        input.addEventListener('input', () => {
            const regex = /^[A-Za-zÀ-ÿ\s]+$/;
            document.getElementById(errorId).textContent = regex.test(input.value) ? '' : 'Uniquement lettres et espaces.';
        });
    }
    if(titre) validateText(titre, 'titreError');
    if(nom) validateText(nom, 'nomError');
    if(responsable) validateText(responsable, 'respError');

    // --- Validation téléphone ---
    if(tel) {
        tel.addEventListener('input', () => {
            const regex = /^[2-579]\d{7}$/;
            document.getElementById('telError').textContent = regex.test(tel.value) ? '' : '8 chiffres, pas de 0,1,6 au début.';
        });
    }

    // --- Validation email ---
    if(email) {
        email.addEventListener('input', () => {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            document.getElementById('emailError').textContent = regex.test(email.value) ? '' : 'Email invalide.';
        });
    }

    // --- Validation dates ---
    function validateDates() {
        if(dateDebut.value && dateFin.value) {
            dateFin.nextElementSibling.textContent = (new Date(dateFin.value) < new Date(dateDebut.value)) ? 
                'La date de fin doit être postérieure ou égale à la date de début.' : '';
        }
    }
    if(dateDebut && dateFin) {
        dateDebut.addEventListener('change', validateDates);
        dateFin.addEventListener('change', validateDates);
    }
});
</script>
<script>
// Durée max de session en millisecondes
let sessionTimeout = 15 * 60 * 1000; // 15 minutes

// Redirection automatique vers login
setTimeout(function() {
    window.location.href = "login.php?expired=1";
}, sessionTimeout);
</script>



</body>
</html>
