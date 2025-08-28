<?php
session_start();
$message = '';

// Bloquer les utilisateurs normaux
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] === 'utilisateur') {
    header("Location: login.php?redirect=supprimer.php&interdit=1");
    exit();
}


// Vérifier ré-authentification
if (!isset($_SESSION['auth_supprimer']) || $_SESSION['auth_supprimer'] !== true) {
    header("Location: login.php?redirect=supprimer.php&reauth=1");
    exit();
}

// --- Le reste du code pour affichage et suppression ---
include 'menu.php'; 
include 'session_check.php';

// Connexion
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer types et titres
$types = $pdo->query("SELECT * FROM type_convention")->fetchAll();
$mode = $_GET['mode'] ?? '';
$convention = null;
$type_id = $_GET['type_id'] ?? null;
$titre = $_GET['titre'] ?? null;
$titres = [];
// --- Traitement POST pour suppression ---
if (isset($_POST['confirm_delete'])) {
    $delete_mode = $_POST['delete_mode'] ?? '';

    if ($delete_mode === 'convention') {
        $id_to_delete = (int)($_POST['id_to_delete'] ?? 0);
        if ($id_to_delete > 0) {
            // Supprimer image et pièce jointe si elles existent
            $stmt = $pdo->prepare("SELECT image, piece_jointe FROM convention WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $conv = $stmt->fetch();
            if ($conv) {
                if (!empty($conv['image']) && file_exists($conv['image'])) unlink($conv['image']);
                if (!empty($conv['piece_jointe']) && file_exists($conv['piece_jointe'])) unlink($conv['piece_jointe']);
            }

            // Supprimer la convention
            $stmt = $pdo->prepare("DELETE FROM convention WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $message = "<div class='alert alert-success'>Convention supprimée avec succès.</div>";
            $convention = null;
        }
    } elseif ($delete_mode === 'type') {
        $type_id_to_delete = (int)($_POST['type_id_to_delete'] ?? 0);
        if ($type_id_to_delete > 0) {
            // Vérifier conventions associées
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM convention WHERE type_id = ?");
            $stmt->execute([$type_id_to_delete]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $message = "<div class='alert alert-warning'>Impossible de supprimer ce type, il est associé à une ou plusieurs conventions.</div>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM type_convention WHERE id = ?");
                $stmt->execute([$type_id_to_delete]);
                $message = "<div class='alert alert-success'>Type supprimé avec succès.</div>";
            }
        }
    }
}

if ($mode === 'convention' && !empty($type_id)) {
    $stmt = $pdo->prepare("SELECT DISTINCT titre FROM convention WHERE type_id = ?");
    $stmt->execute([(int)$type_id]);
    $titres = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if ($mode === 'convention' && !empty($type_id) && !empty($titre) && empty($_POST['confirm_delete'])) {
    $stmt = $pdo->prepare("SELECT c.*, t.libelle AS type_libelle 
                           FROM convention c 
                           LEFT JOIN type_convention t ON c.type_id = t.id 
                           WHERE c.type_id = ? AND c.titre = ?");
    $stmt->execute([(int)$type_id, $titre]);
    $convention = $stmt->fetch();
    if (!$convention) $message = "<div class='alert alert-warning'>Aucune convention trouvée avec ce type et ce titre.</div>";
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Supprimer une convention ou un type</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
body {
    background-color: #f7f9fc;
    padding: 40px 15px;
    font-family: 'Inter', sans-serif;
    color: #222;
}
 nav.navbar {
            background-color: #1c3a6e;
            font-family: 'Inter', sans-serif;
        }
        nav.navbar .container a.navbar-brand {
            color: white;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        nav.navbar .container a.navbar-brand:hover {
            color: #dcdcdc;
        }
        nav.navbar .navbar-nav .nav-link {
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            white-space: nowrap; /* empêche l'écartement */
        }
        nav.navbar .navbar-nav .nav-link:hover {
            text-decoration: underline;
            color: #a9b9d9;
        }
        nav.navbar .navbar-toggler {
            background-color: #f8f9fa;
        }
h2 {
    color: #1c3a6e;
    text-align: center;
    margin-bottom: 30px;
}
.form-label {
    font-weight: 600;
    color: #1c3a6e;
}
.form-select, .form-control {
    border-radius: 6px;
    border: 1.5px solid #a9b9d9;
}
button.btn-danger {
    background-color: #a00;
    border: none;
    padding: 10px 28px;
    color: white;
    font-weight: 600;
    border-radius: 8px;
    display: block;
    margin: 25px auto 0 auto;
}
button.btn-danger:hover {
    background-color: #d00;
}
.alert {
    max-width: 600px;
    margin: 0 auto 20px auto;
    font-weight: 600;
    border-radius: 8px;
}
.card {
    max-width: 600px;
    margin: 0 auto 30px;
    box-shadow: 0 4px 10px rgba(28,58,110,0.15);
    border-radius: 12px;
    border: none;
}
.card img {
    width: 100%;
    height: 280px;
    object-fit: cover;
}
.card-title {
    color: #1c3a6e;
    font-weight: 700;
    font-size: 1.5rem;
}
.card-subtitle {
    color: #5a5a5a;
    font-weight: 600;
}
.card-text {
    color: #555;
}
.info-label {
    font-weight: 600;
    color: #1c3a6e;
}
/* Image floue en arrière-plan */

.choice-card {
    background: rgba(255, 255, 255, 0.25); /* beaucoup plus transparent */
    backdrop-filter: blur(15px); /* blur plus prononcé */
    border-radius: 20px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.2); /* ombre douce mais profonde */
    padding: 50px 30px;
    flex: 1;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
    position: relative;
    user-select: none;
}

/* Hover */
.choice-card:hover {
    transform: scale(1.05);
    box-shadow: 0 20px 45px rgba(0,0,0,0.35);
    background: rgba(255,255,255,0.35);
}

/* Sélectionné */
.choice-card.selected {
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
.choice-card.selected .card-title {
    color: #004080;
}



</style>
</head>
<body>

<h2>Supprimer une convention ou un type</h2>

<?php if ($message) echo $message; ?>

<form id="choiceForm" class="mb-5 d-flex justify-content-center gap-4" style="max-width: 720px; margin: auto;">
  <label class="card choice-card p-5 flex-fill text-center" style="cursor: pointer; user-select:none;">
    <input type="radio" name="delete_mode" value="convention" <?= ($mode === 'convention') ? 'checked' : '' ?> style="display:none;" />
    <div class="card-body d-flex flex-column justify-content-center align-items-center">
      <h3 class="card-title">Supprimer une convention</h3>
    </div>
  </label>

  <label class="card choice-card p-5 flex-fill text-center" style="cursor: pointer; user-select:none;">
    <input type="radio" name="delete_mode" value="type" <?= ($mode === 'type') ? 'checked' : '' ?> style="display:none;" />
    <div class="card-body d-flex flex-column justify-content-center align-items-center">
      <h3 class="card-title">Supprimer un type</h3>
    </div>
  </label>
</form>


<script>
  const labels = document.querySelectorAll('#choiceForm label.choice-card');
  const radio= document.querySelectorAll('input[name="delete_mode"]');

  function updateCardSelection() {
    radios.forEach((radio, idx) => {
      if (radio.checked) {
        labels[idx].classList.add('selected');
      } else {
        labels[idx].classList.remove('selected');
      }
    });
  }

  radios.forEach(radio => {
    radio.addEventListener('change', updateCardSelection);
  });

  // Initialisation au chargement
  updateCardSelection();
</script>


<!-- Formulaire sélection convention -->
<form method="GET" id="formConvention" style="max-width:600px; margin:auto; display: <?= ($mode === 'convention') ? 'block' : 'none' ?>">
    <input type="hidden" name="mode" value="convention" />
    <div class="mb-3">
        <label for="type_id" class="form-label">Type de convention</label>
        <select name="type_id" id="type_id" class="form-select" required onchange="this.form.submit()">
            <option value="">Choisir un type</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>" <?= ($type['id'] == $type_id) ? 'selected' : '' ?>><?= htmlspecialchars($type['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="titre" class="form-label">Titre de la convention</label>
        <select name="titre" id="titre" class="form-select" required onchange="this.form.submit()">
            <option value="">Choisir un titre</option>
            <?php foreach ($titres as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= ($titre == $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>


<?php if ($convention): ?>
    <div class="card">
        <?php if (!empty($convention['image'])): ?>
            <img src="<?= htmlspecialchars($convention['image']) ?>" alt="Image convention" />
        <?php else: ?>
            <img src="default.jpg" alt="Image par défaut" />
        <?php endif; ?>
        <div class="card-body p-3">
            <h5 class="card-title"><?= htmlspecialchars($convention['titre']) ?></h5>
            <h6 class="card-subtitle mb-2"><?= htmlspecialchars($convention['type_libelle']) ?></h6>
            <p class="card-text"><?= nl2br(htmlspecialchars($convention['description'])) ?></p>
            <p><span class="info-label">Nom : </span><?= htmlspecialchars($convention['nom']) ?></p>
            <p><span class="info-label">Dates : </span><?= htmlspecialchars($convention['date_debut']) ?> → <?= htmlspecialchars($convention['date_fin']) ?></p>
            <p><span class="info-label">Responsable : </span><?= htmlspecialchars($convention['responsable']) ?></p>
            <p><span class="info-label">Téléphone : </span><?= htmlspecialchars($convention['tel']) ?></p>
            <?php if (!empty($convention['piece_jointe'])): ?>
                <p><a href="<?= htmlspecialchars($convention['piece_jointe']) ?>" target="_blank">Voir la pièce jointe</a></p>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirm('Confirmez-vous la suppression de cette convention ?');" class="mt-3">
                <input type="hidden" name="id_to_delete" value="<?= $convention['id'] ?>" />
                <input type="hidden" name="delete_mode" value="convention" />
                <button type="submit" name="confirm_delete" class="btn btn-danger w-100">Supprimer définitivement</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Formulaire suppression type -->
<form method="POST" id="formType" style="max-width:600px; margin:auto; display: <?= ($mode === 'type') ? 'block' : 'none' ?>" novalidate>
    <input type="hidden" name="delete_mode" value="type" />
    <div class="mb-3">
        <label for="type_id_to_delete" class="form-label">Type à supprimer</label>
        <select name="type_id_to_delete" id="type_id_to_delete" class="form-select" required>
            <option value="">Choisir un type</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="confirm_delete" class="btn btn-danger w-100">Supprimer le type </button>
</form>

<script>
    const radios = document.querySelectorAll('input[name="delete_mode"]');
    const formConvention = document.getElementById('formConvention');
    const formType = document.getElementById('formType');

    function toggleForms() {
        const selected = Array.from(radios).find(r => r.checked)?.value;
        if (selected === 'convention') {
            formConvention.style.display = 'block';
            formType.style.display = 'none';
        } else if (selected === 'type') {
            formType.style.display = 'block';
            formConvention.style.display = 'none';
        }
    }
    radios.forEach(r => r.addEventListener('change', toggleForms));
    toggleForms();
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
