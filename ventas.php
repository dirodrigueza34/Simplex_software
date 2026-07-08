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
    
    // ==========================================
    // 1. MÉTODO GET: LISTAR HISTORIAL DE VENTAS
    // ==========================================
    case 'GET':
        try {
            // Consulta de lectura en tu tabla real de ventas
            $stmt = $pdo->prepare("SELECT id_venta, fecha, total, id_cliente FROM ventas ORDER BY id_venta DESC");
            $stmt->execute();
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $ventas], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error al consultar ventas: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ==========================================
    // 2. MÉTODO POST: PROCESAR NUEVA VENTA COMPLETA
    // ==========================================
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validaciones perimetrales de la cabecera de la venta
        if (!$data || empty($data['fecha']) || !isset($data['total']) || empty($data['id_cliente']) || empty($data['productos'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Datos de venta incompletos. Se requieren fecha, total, id_cliente y la lista de productos."], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $fecha = trim($data['fecha']);
        $total = floatval($data['total']);
        $id_cliente = intval($data['id_cliente']);
        $lista_productos = $data['productos']; // Arreglo con los productos vendidos

        try {
            // Iniciamos una TRANSACCIÓN SQL para asegurar que se guarde la venta y sus detalles juntos
            $pdo->beginTransaction();

            // 1. Insertar el registro principal en la tabla 'ventas'
            $stmtVenta = $pdo->prepare("INSERT INTO ventas (fecha, total, id_cliente) VALUES (?, ?, ?)");
            $stmtVenta->execute([$fecha, $total, $id_cliente]);
            
            // Recuperar el ID autoincremental de la venta que se acaba de crear
            $id_nueva_venta = $pdo->lastInsertId();

            // 2. Recorrer la lista de productos para insertarlos en 'detalle_venta' y actualizar el stock
            foreach ($lista_productos as $item) {
                $id_producto = intval($item['id_producto']);
                $cantidad = intval($item['cantidad']);
                $precio_unitario = floatval($item['precio_unitario']);

                // Insertar en la tabla de detalle usando los nombres de columnas estándar
                $stmtDetalle = $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmtDetalle->execute([$id_nueva_venta, $id_producto, $cantidad, $precio_unitario]);

                // 3. ACTUALIZACIÓN AUTOMÁTICA DEL STOCK: Restar la mercancía vendida en la tabla 'producto'
                $stmtStock = $pdo->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");
                $stmtStock->execute([$cantidad, $id_producto]);
            }

            // Si todo el bucle se ejecutó sin errores, confirmamos la operación en MySQL
            $pdo->commit();

            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Venta procesada con éxito y stock actualizado en Simplex Software.",
                "id_venta" => $id_nueva_venta
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            // Si algo falla (por ejemplo, id de producto inválido), cancelamos toda la operación en la BD
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Error transaccional al procesar la venta: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
        break;
}
?>
