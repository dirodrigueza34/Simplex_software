<?php
if (ob_get_length()) ob_clean();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
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
            $stmt = $pdo->prepare("SELECT id_producto, codigo, nombre, precio, stock, id_categoria FROM producto ORDER BY id_producto DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        // Leer el flujo de entrada asíncrono
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (!$data || empty($data['codigo']) || empty($data['nombre']) || !isset($data['precio']) || !isset($data['stock']) || empty($data['id_categoria'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Campos incompletos en la solicitud."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $codigo = trim($data['codigo']);
        $nombre = trim($data['nombre']);
        $precio = floatval($data['precio']);
        $stock = intval($data['stock']);
        $id_categoria = intval($data['id_categoria']);
        
        try {
            // Verificar duplicados por código de barras
            $check = $pdo->prepare("SELECT id_producto FROM producto WHERE codigo = ?");
            $check->execute([$codigo]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["success" => false, "error" => "El código de producto ya existe."], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // Inserción parametrizada en MySQL
            $stmt = $pdo->prepare("INSERT INTO producto (codigo, nombre, precio, stock, id_categoria) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$codigo, $nombre, $precio, $stock, $id_categoria])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Producto registrado correctamente en el inventario."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de inserción relacional: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>

