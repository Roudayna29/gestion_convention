<?php
$pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Création d’un gestionnaire ---
$username = 'gestionnaire1';
$password = 'password123';
$email = 'gestionnaire1@gmail.com';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password, role, email) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $hash, 'gestionnaire', $email]);

echo "Gestionnaire créé avec succès.<br>";

// --- Création d’un utilisateur ---
$username2 = 'user1';
$password2 = 'password456';
$email2 = 'user1@gmail.com';
$hash2 = password_hash($password2, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password, role, email) VALUES (?, ?, ?, ?)");
$stmt->execute([$username2, $hash2, 'utilisateur', $email2]);

echo "Utilisateur créé avec succès.";
?>
