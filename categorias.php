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
            $stmt = $pdo->prepare("SELECT id_categoria, nombre FROM categorias ORDER BY id_categoria DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar categorías: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "El campo 'nombre' de la categoría es obligatorio."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $nombre = trim($data['nombre']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            if ($stmt->execute([$nombre])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Nueva categoría de inventario almacenada."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>

