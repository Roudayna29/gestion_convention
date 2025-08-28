<?php
$pdo = new PDO('mysql:host=localhost;dbname=portail_pnc;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = 'mouhamed';;
$password = '1234567';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Vérifier si l'utilisateur existe déjà
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);
    echo "Admin créé avec succès.";
} else {
    echo "L'utilisateur '$username' existe déjà.";
}
