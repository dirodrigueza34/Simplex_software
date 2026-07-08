<?php
// Configuración de conexión para el proyecto Simplex Software
$host = "localhost";
$dbname = "sistem_invent"; // Nombre exacto de tu base de datos relacional
$username = "root";
$password = "";

try {
    // Inicializar la conexión PDO con codificación UTF-8 nativa
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    // Activar el manejo de excepciones detalladas para el testing en Postman
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detener la ejecución si los servicios de XAMPP están apagados
    die("Error crítico de conexión al ecosistema de datos: " . $e->getMessage());
}
?>

