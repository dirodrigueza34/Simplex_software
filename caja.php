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
            // Selección utilizando los nombres de columnas reales de movimientos_contables
            $stmt = $pdo->prepare("SELECT id_movimiento, fecha, descripcion, total FROM movimientos_contables ORDER BY id_movimiento DESC");
            $stmt->execute();
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cálculo aritmético automatizado del flujo de caja
            $total_stmt = $pdo->prepare("SELECT SUM(total) as saldo_acumulado FROM movimientos_contables");
            $total_stmt->execute();
            $total_resultado = $total_stmt->fetch(PDO::FETCH_ASSOC);
            $saldo_actual = $total_resultado['saldo_acumulado'] ?? 0;

            http_response_code(200);
            echo json_encode([
                "success" => true, 
                "saldo_actual" => floatval($saldo_actual), 
                "data" => $movimientos
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error en servidor: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['fecha']) || empty($data['descripcion']) || !isset($data['total'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Todos los campos de la transacción (fecha, descripcion, total) son obligatorios."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $fecha = trim($data['fecha']);
        $descripcion = trim($data['descripcion']);
        $total = floatval($data['total']);

        try {
            // Registro seguro de la transacción financiera mapeando tu columna real 'fecha'
            $stmt = $pdo->prepare("INSERT INTO movimientos_contables (fecha, descripcion, total) VALUES (?, ?, ?)");
            if ($stmt->execute([$fecha, $descripcion, $total])) {
                http_response_code(201);
                echo json_encode(["success" => true, "message" => "Movimiento contable registrado satisfactoriamente."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
}
?>
