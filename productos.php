<?php
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
            // Consulta de lectura utilizando las columnas reales de tu tabla
            $stmt = $pdo->prepare("SELECT id_producto, codigo, nombre, precio, stock, id_categoria FROM producto ORDER BY id_producto DESC");
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $productos], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['codigo']) || empty($data['nombre']) || !isset($data['precio']) || !isset($data['stock']) || empty($data['id_categoria'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Todos los campos del producto (codigo, nombre, precio, stock, id_categoria) son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $codigo = trim($data['codigo']);
        $nombre = trim($data['nombre']);
        $precio = floatval($data['precio']);
        $stock = intval($data['stock']);
        $id_categoria = intval($data['id_categoria']);
        
        try {
            // Validación de control de duplicados mediante sentencias preparadas
            $check = $pdo->prepare("SELECT id_producto FROM producto WHERE codigo = ?");
            $check->execute([$codigo]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["success" => false, "error" => "El código de producto ya existe en el sistema."], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // Inserción segura mapeando las variables de tu tabla producto
            $stmt = $pdo->prepare("INSERT INTO producto (codigo, nombre, precio, stock, id_categoria) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$codigo, $nombre, $precio, $stock, $id_categoria])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Producto registrado correctamente en el inventario."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['id_producto'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Se requiere el 'id_producto' para poder eliminarlo."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $id_producto = intval($data['id_producto']);
        try {
            // Remoción de registro utilizando el identificador autoincremental real
            $stmt = $pdo->prepare("DELETE FROM producto WHERE id_producto = ?");
            $stmt->execute([$id_producto]);
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "Producto eliminado satisfactoriamente."], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "error" => "El producto con el id especificado no existe."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al eliminar: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>
