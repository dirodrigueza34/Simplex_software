<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "configuracion/db.php";
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Mapeo directo a tu tabla real en singular: proveedor
            $stmt = $pdo->prepare("SELECT id_proveedor, nombre, telefono FROM proveedor ORDER BY id_proveedor DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar proveedores: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['nombre']) || empty($data['telefono'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Los campos 'nombre' y 'telefono' son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $nombre = trim($data['nombre']);
        $telefono = trim($data['telefono']);
        
        try {
            // Validación de control perimetral para evitar nombres duplicados
            $check = $pdo->prepare("SELECT id_proveedor FROM proveedor WHERE nombre = ?");
            $check->execute([$nombre]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["success" => false, "error" => "El nombre de este proveedor ya está registrado."], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO proveedor (nombre, telefono) VALUES (?, ?)");
            if ($stmt->execute([$nombre, $telefono])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Proveedor registrado correctamente en la base de datos."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error en servidor relacional: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>
