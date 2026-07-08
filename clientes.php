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
            // Lectura de la tabla clientes de tu base de datos sistem_invent
            $stmt = $pdo->prepare("SELECT * FROM clientes ORDER BY id_cliente DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar clientes: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['cedula']) || empty($data['nombre']) || empty($data['telefono']) || empty($data['direccion'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Todos los campos del cliente (cedula, nombre, telefono, direccion) son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $cedula = trim($data['cedula']);
        $nombre = trim($data['nombre']);
        $telefono = trim($data['telefono']);
        $direccion = trim($data['direccion']);
        
        try {
            // Control perimetral para evitar cédulas duplicadas en la base de datos
            $check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula = ?");
            $check->execute([$cedula]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["success" => false, "error" => "La cédula del cliente ya se encuentra registrada."], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // Inserción limpia mediante parámetros posicionales seguros contra inyecciones SQL
            $stmt = $pdo->prepare("INSERT INTO clientes (cedula, nombre, telefono, direccion) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$cedula, $nombre, $telefono, $direccion])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Cliente registrado satisfactoriamente en Simplex Software."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>
