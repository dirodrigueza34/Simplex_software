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
            // Consulta de lectura unificada para tu tabla clientes
            $stmt = $pdo->prepare("SELECT id_cliente, nombre, documento, telefono, direccion, email, fecha_registro FROM clientes ORDER BY id_cliente DESC");
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
        if (!$data || empty($data['documento']) || empty($data['nombre']) || empty($data['telefono']) || empty($data['direccion']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Todos los campos (documento, nombre, telefono, direccion, email) son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $documento = trim($data['documento']);
        $nombre = trim($data['nombre']);
        $telefono = trim($data['telefono']);
        $direccion = trim($data['direccion']);
        $email = trim($data['email']);
        
        try {
            // Control perimetral para evitar documentos duplicados en la base de datos
            $check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE documento = ?");
            $check->execute([$documento]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(["success" => false, "error" => "El documento de este cliente ya se encuentra registrado."], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // Inserción limpia mediante parámetros posicionales seguros contra inyecciones SQL
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, documento, telefono, direccion, email) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $documento, $telefono, $direccion, $email])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Cliente registrado de forma exitosa en Simplex Software."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>
