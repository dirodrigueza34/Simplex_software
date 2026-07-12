<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "configuracion/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['usuario']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Usuario y contraseña requeridos."], JSON_UNESCAPED_UNICODE);
    exit();
}

$usuario = trim($data['usuario']);
$password = trim($data['password']);

try {
    // Consulta para la tabla de usuarios
    $stmt = $pdo->prepare("SELECT id_usuario, usuario, password FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "message" => "Acceso concedido. Bienvenido a Simplex Software."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false, 
            "message" => "Credenciales inválidas. Intente de nuevo."
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error en la base de datos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
