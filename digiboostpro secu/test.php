<?php
require_once 'config.php';

echo "<h2>Test de Configuration</h2>";

// Test 1 : Config chargé
echo "✅ Config chargé<br>";

// Test 2 : PDO
try {
    $pdo = getPdo();
    echo "✅ Connexion PDO : OK<br>";
} catch (Exception $e) {
    echo "❌ Connexion PDO : ERREUR - " . $e->getMessage() . "<br>";
}

// Test 3 : Fonction e()
$test = e("<script>alert('test')</script>");
echo "✅ Fonction e() : " . $test . "<br>";

// Test 4 : CSRF Token
$token = generateCsrfToken();
echo "✅ CSRF Token : " . substr($token, 0, 20) . "...<br>";

// Test 5 : Session
echo "✅ Session démarrée : " . (session_status() === PHP_SESSION_ACTIVE ? "OUI" : "NON") . "<br>";

// Test 6 : isLogged
echo "✅ Utilisateur connecté : " . (isLogged() ? "OUI" : "NON") . "<br>";

echo "<hr>";
echo "<a href='login.php'>Aller à la page de connexion</a>";
?>