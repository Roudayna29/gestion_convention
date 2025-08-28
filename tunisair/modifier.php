<?php
include 'menu.php'; 
include 'session_check.php';   // Vérification et expiration

$pdo = new PDO('mysql:host=localhost;dbname=portail_pnc', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$types = $pdo->query("SELECT * FROM type_convention")->fetchAll();

// Récupérer tous les titres distincts (pour lister dans le select)
$titres = $pdo->query("SELECT DISTINCT titre FROM convention ORDER BY titre")->fetchAll(PDO::FETCH_COLUMN);

$convention = null;
$message = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['rechercher'])) {
        $type_id = $_POST['type_id'];
        $titre = trim($_POST['titre']);

        $stmt = $pdo->prepare("SELECT * FROM convention WHERE titre = ? AND type_id = ?");
        $stmt->execute([$titre, $type_id]);
        $convention = $stmt->fetch();

        if (!$convention) {
            $message = "<div class='alert alert-danger'>Aucune convention trouvée avec ce titre et ce type.</div>";
        }
    }

    if (isset($_POST['modifier'])) {
        $id = $_POST['id'];
        $titre = $_POST['titre'];
        $nom = $_POST['nom'];
        $date_debut = $_POST['date_debut'];
        $date_fin = $_POST['date_fin'];
        $description = $_POST['description'];
        $responsable = $_POST['responsable'];
        $tel = $_POST['tel'];
        $email = $_POST['email'];


        $pattern_text = "/^[A-Za-zÀ-ÿ\s]+$/";

        // Récupérer l'ancienne convention pour comparaison et type_id existant
        $stmt = $pdo->prepare("SELECT * FROM convention WHERE id = ?");
        $stmt->execute([$id]);
        $ancienneConvention = $stmt->fetch();

        if (!$ancienneConvention) {
            $message = "<div class='alert alert-danger'>Convention introuvable.</div>";
        } else {
            // On garde l'ancien type_id car on ne le modifie pas ici
            $type_id = $ancienneConvention['type_id'];

            // Validation des champs
            if (!preg_match($pattern_text, $titre)) {
                $errors[] = "Le titre doit contenir uniquement des lettres.";
            }
            if (!preg_match($pattern_text, $nom)) {
                $errors[] = "Le nom doit contenir uniquement des lettres.";
            }
            if (!preg_match($pattern_text, $responsable)) {
                $errors[] = "Le responsable doit contenir uniquement des lettres.";
            }
            if (!preg_match("/^\d{8}$/", $tel)) {
                $errors[] = "Le téléphone doit comporter exactement 8 chiffres.";
            }
            if ($date_fin < $date_debut) {
                $errors[] = "La date de fin doit être postérieure à la date de début.";
            }
            if (!empty($_FILES['image']['name'][0])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    foreach ($_FILES['image']['type'] as $type) {
        if (!in_array($type, $allowed_types)) {
            $errors[] = "Seules les images JPG, JPEG, PNG, GIF sont autorisées.";
            break;
        }
    }
}
if (!empty($_FILES['image']['name'][0])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    foreach ($_FILES['image']['type'] as $type) {
        if (!in_array($type, $allowed_types)) {
            $errors[] = "Seules les images JPG, JPEG, PNG, GIF sont autorisées.";
            break;
        }
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email saisie est invalide.";
}

            if (empty($errors)) {
                // Gestion image
$image_path = $ancienneConvention['image'];
if (!empty($_FILES["image"]["name"][0])) { // vérifier si au moins 1 fichier
    // Supprimer l'ancienne image si elle existe
    if ($image_path && file_exists($image_path)) {
        unlink($image_path);
    }

    // Prendre le premier fichier 
    $firstImageName = basename($_FILES["image"]["name"][0]);
    $image_path = "images/" . $firstImageName;
    move_uploaded_file($_FILES["image"]["tmp_name"][0], $image_path);
}


                // Gestion pièce jointe
                $piece_path = $ancienneConvention['piece_jointe'];

if (!empty($_FILES["piece"]["name"][0])) {
    // Supprimer les anciennes pièces (si elles existaient, séparées par ";")
    if ($piece_path) {
        $oldPieces = explode(";", $piece_path);
        foreach ($oldPieces as $old) {
            if (file_exists($old)) unlink($old);
        }
    }

    $piece_paths = [];
    foreach ($_FILES["piece"]["name"] as $index => $name) {
        if (!empty($name)) {
            $fileName = basename($name);
            $targetPath = "pieces/" . $fileName;
            if (move_uploaded_file($_FILES["piece"]["tmp_name"][$index], $targetPath)) {
                $piece_paths[] = $targetPath;
            }
        }
    }

    // Sauvegarde en base séparée par ";"
    $piece_path = implode(";", $piece_paths);
}



                // Mise à jour de la convention
                $stmt = $pdo->prepare("UPDATE convention SET
                    titre = ?, nom = ?, date_debut = ?, date_fin = ?, description = ?,
                    responsable = ?, tel = ?, image = ?, piece_jointe = ?, type_id = ?
                    WHERE id = ?");
                $stmt->execute([
                    $titre, $nom, $date_debut, $date_fin, $description,
                    $responsable, $tel, $image_path, $piece_path, $type_id, $id
                ]);

                // Récupérer le libellé type_convention (ancien)
                $stmtType = $pdo->prepare("SELECT libelle FROM type_convention WHERE id = ?");
                $stmtType->execute([$type_id]);
                $type_libelle = $stmtType->fetchColumn();

                // Préparer insertion dans historique
                $stmtHist = $pdo->prepare("INSERT INTO historique 
                    (action, champ_modifie, ancien_valeur, nouvelle_valeur, type_convention, date_action) 
                    VALUES (?, ?, ?, ?, ?, NOW())");

                // Liste des champs à comparer
                $image = $image_path;
                $piece_jointe = $piece_path;

                $champs = ['titre', 'nom', 'date_debut', 'date_fin', 'description', 'responsable', 'tel', 'image', 'piece_jointe'];
                foreach ($champs as $champ) {
                    if ($ancienneConvention[$champ] != $$champ) {
                        $stmtHist->execute([
                            "Modification",
                            $champ,
                            $ancienneConvention[$champ],
                            $$champ,
                            $type_libelle
                        ]);
                    }
                }

                $message = "<div class='alert alert-success'>Convention modifiée avec succès.</div>";

                // Recharger la convention modifiée pour afficher les nouvelles données
                $stmt = $pdo->prepare("SELECT * FROM convention WHERE id = ?");
                $stmt->execute([$id]);
                $convention = $stmt->fetch();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Modifier une convention</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Inter', sans-serif;
            color: #222;
            padding: 30px;
        }
        nav.navbar {
            background-color: #1c3a6e;
            font-family: 'Inter', sans-serif;
            margin-bottom: 30px;
            border-radius: 8px;
            padding: 10px 20px;
        }
        nav.navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            white-space: nowrap;
            text-decoration: none;
            font-size: 1.25rem;
        }
        nav.navbar .navbar-brand:hover {
            color: #dcdcdc !important;
        }
        nav.navbar .nav-link {
            color: white !important;
            font-weight: 600;
            white-space: nowrap;
            margin-right: 20px;
        }
        nav.navbar .nav-link:hover {
            text-decoration: underline;
            color: #a9b9d9 !important;
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
        .form-container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgb(28 58 110 / 0.3);
        }
        .form-label {
            font-weight: 600;
            color: #1c3a6e;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #a9b9d9;
            transition: border-color 0.3s ease;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1c3a6e;
            box-shadow: 0 0 8px rgba(28, 58, 110, 0.3);
            outline: none;
        }
        .btn-primary {
            background-color: #1c3a6e;
            border: none;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #2a4a8f;
        }
        .btn-success {
            background-color: #1c3a6e;
            border: none;
            font-weight: 600;
        }
        .btn-success:hover {
            background-color: #2a4a8f;
        }
        .btn-secondary {
            font-weight: 600;
        }
        .alert {
            max-width: 650px;
            margin: 0 auto 20px auto;
            border-radius: 8px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }
        img {
            max-width: 100px;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        



body {
    position: relative;
    z-index: 0;
}
.form-container {
    max-width: 500px;        
    margin: 50px auto;         /* centrer horizontalement et ajouter un peu d'espace vertical */
    background: rgba(255, 255, 255, 0.2); /* fond transparent */
    backdrop-filter: blur(10px); /* flou derrière la carte */
    padding: 25px 30px;        /* réduire un peu le padding */
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(28, 58, 110, 0.3);
    color: #004080;
}

.form-container .btn-primary, 
.form-container .btn-success {
    display: block;     
    margin: 0 auto;      /* centrer horizontalement */
    width: 50%;         
}




    </style>
</head>
<body>



<?php
if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "<div class='alert alert-danger'>$e</div>";
    }
}

if (!empty($message)) {
    echo $message;
}
?>

<div class="form-container">
    <h2>Rechercher une convention à modifier</h2>

    <?= $message ?>

    <form method="POST" class="mb-4" novalidate>
        <div class="mb-3">
            <label for="type_id" class="form-label">Type de convention</label>
            <select name="type_id" id="type_id" class="form-select" required>
                <option value="">Choisir un type</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= (isset($_POST['type_id']) && $_POST['type_id'] == $type['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="titre" class="form-label">Titre de la convention</label>
            <select name="titre" id="titre" class="form-select" required>
                <option value="">Choisir un titre</option>
                <?php foreach ($titres as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= (isset($_POST['titre']) && $_POST['titre'] == $t) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="rechercher" class="btn btn-primary">Rechercher</button>
    </form>

    <?php if ($convention): ?>
        <hr>
        <h3 class="mt-4">Modifier la convention</h3>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= $e ?></div>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="id" value="<?= $convention['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Titre</label>
                <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($convention['titre']) ?>" pattern="[A-Za-zÀ-ÿ\s]+" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nom</label>
                <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($convention['nom']) ?>" pattern="[A-Za-zÀ-ÿ\s]+" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Date début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= $convention['date_debut'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_fin" class="form-control" value="<?= $convention['date_fin'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"><?= htmlspecialchars($convention['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Responsable</label>
                <input type="text" name="responsable" class="form-control" value="<?= htmlspecialchars($convention['responsable']) ?>" pattern="[A-Za-zÀ-ÿ\s]+">
            </div>

            <div class="mb-3">
                <label for="tel" class="form-label">Téléphone</label>
                <input type="text" name="tel" id="tel" class="form-control" value="<?= htmlspecialchars($convention['tel']) ?>" required
                       pattern="^[2-579]\d{7}$"
                       title="Le numéro doit contenir 8 chiffres et ne pas commencer par 0, 1 ou 6.">
                <small class="text-muted">Entrez un numéro de 8 chiffres commençant par 2, 3, 4, 5, 7, 8 ou 9</small>
            </div>
            <!-- Nouveau champ Adresse -->
    <div class="mb-3">
        <label for="adresse" class="form-label">Adresse</label>
        <input type="text" name="adresse" id="adresse" class="form-control"
               value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
    </div>

    <!-- Nouveau champ Email -->
     <div class="mb-3">
     <label for="adresse_email" class="form-label">Adresse email</label>
    <input type="email" name="email" id="email" class="form-control" 
       required
       pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
       title="Veuillez saisir une adresse email valide (exemple@domaine.com)"
       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
       <small class="text-muted">doit etre de la forme exemple@domaine.com</small>
     </div>

    <!-- Nouveau champ Infos supplémentaires -->
    <div class="mb-3">
        <label for="infos_sup" class="form-label">Informations supplémentaires</label>
        <textarea name="infos_sup" id="infos_sup" class="form-control"><?= htmlspecialchars($_POST['infos_sup'] ?? '') ?></textarea>
    </div>
    

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
            <button type="submit" name="modifier" class="btn btn-success">Enregistrer les modifications</button>
            <a href="afficher.php" class="btn btn-secondary ms-2">Retour</a>
        </form>
    <?php endif; ?>
</div>
<script>
    
    // --- Validation email ---
    document.getElementById('email').addEventListener('input', function () {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(this.value)) {
            this.setCustomValidity("Veuillez saisir une adresse email valide (exemple@domaine.com)");
        } else {
            this.setCustomValidity("");
        }
    });

    // --- Validation fichiers images ---
 


    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="form_type"]');
        const formConvention = document.getElementById('formConvention');
        const formType = document.getElementById('formType');

        function toggleForms() {
            let selected = null;
            radios.forEach(radio => {
                if (radio.checked) selected = radio.value;
            });

            if (selected === 'convention') {
                formConvention.classList.add('active');
                formType.classList.remove('active');
            } else if (selected === 'type') {
                formType.classList.add('active');
                formConvention.classList.remove('active');
            } else {
                formConvention.classList.remove('active');
                formType.classList.remove('active');
            }
        }

        toggleForms();

        radios.forEach(radio => {
            radio.addEventListener('change', toggleForms);
        });

        // Client-side file validation + preview
        const maxImageSize = <?= $MAX_IMAGE_SIZE ?>; // 5MB
        const maxPieceSize = <?= $MAX_PIECE_SIZE ?>; // 10MB

        const imageInput = document.getElementById('image');
        const imagesPreview = document.getElementById('imagesPreview');
        const imageError = document.getElementById('imageError');

        const pieceInput = document.getElementById('piece');
        const piecesList = document.getElementById('piecesList');
        const pieceError = document.getElementById('pieceError');

        function humanFileSize(size) {
            if (size < 1024) return size + ' B';
            else if (size < 1024*1024) return (size/1024).toFixed(1) + ' KB';
            else return (size/(1024*1024)).toFixed(2) + ' MB';
        }

        imageInput?.addEventListener('change', function (e) {
            imagesPreview.innerHTML = '';
            imageError.textContent = '';
            const files = Array.from(e.target.files);

            if (files.length === 0) return;

            for (const file of files) {
                if (file.size > maxImageSize) {
                    imageError.textContent = `Le fichier "${file.name}" dépasse la taille maximale de 5 Mo. Sélection annulée.`;
                    imageInput.value = ''; // réinitialiser la sélection
                    imagesPreview.innerHTML = '';
                    return;
                }
                if (!file.type.startsWith('image/')) {
                    imageError.textContent = `Le fichier "${file.name}" n'est pas une image valide. Sélection annulée.`;
                    imageInput.value = '';
                    imagesPreview.innerHTML = '';
                    return;
                }
            }

            // Prévisualiser
            files.forEach(file => {
                const url = URL.createObjectURL(file);
                const img = document.createElement('img');
                img.src = url;
                img.className = 'preview-img';
                img.onload = () => URL.revokeObjectURL(url);
                imagesPreview.appendChild(img);
            });
        });

        pieceInput?.addEventListener('change', function (e) {
            piecesList.innerHTML = '';
            pieceError.textContent = '';
            const files = Array.from(e.target.files);

            if (files.length === 0) return;

            for (const file of files) {
                if (file.size > maxPieceSize) {
                    pieceError.textContent = `Le fichier "${file.name}" dépasse la taille maximale de 10 Mo. Sélection annulée.`;
                    pieceInput.value = '';
                    piecesList.innerHTML = '';
                    return;
                }
            }

            // Lister les pièces
            files.forEach(file => {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.textContent = `${file.name} (${humanFileSize(file.size)})`;
                piecesList.appendChild(div);
            });
        });

        // On submit, prevent submission if client-side errors visible
        const form = document.getElementById('formConvention');
        form?.addEventListener('submit', function (e) {
            if (imageError.textContent || pieceError.textContent) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs sur les fichiers avant de soumettre.');
            }
        });
    });
    
</script>
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
