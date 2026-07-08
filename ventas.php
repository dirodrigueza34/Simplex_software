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
            $stmt = $pdo->prepare("SELECT id_venta, fecha, total, id_movimiento, id_cliente FROM ventas ORDER BY id_venta DESC");
            $stmt->execute();
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $ventas], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar ventas: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['fecha']) || !isset($data['total']) || empty($data['id_cliente'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Datos incompletos. Se requieren fecha, total e id_cliente."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $fecha = trim($data['fecha']);
        $total = floatval($data['total']);
        $id_cliente = intval($data['id_cliente']);
        $id_movimiento = isset($data['id_movimiento']) ? intval($data['id_movimiento']) : null;

        try {
            // Inserción directa y segura en tu tabla principal de ventas
            $stmtVenta = $pdo->prepare("INSERT INTO ventas (fecha, total, id_movimiento, id_cliente) VALUES (?, ?, ?, ?)");
            if ($stmtVenta->execute([$fecha, $total, $id_movimiento, $id_cliente])) {
                $id_nueva_venta = $pdo->lastInsertId();
                
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Venta registrada con éxito en el sistema transaccional.",
                    "id_venta" => $id_nueva_venta
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error en la base de datos de ventas: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>


