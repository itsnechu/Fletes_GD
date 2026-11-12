<?php
header('Content-Type: application/json');
$db = new SQLite3('database.sqlite');

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

// Estatus para flete 
$estatus_validos = ['en tránsito', 'entregado', 'pendiente de pago', 'pagado', 'cancelado'];

// Consulta base
$sql_fletes = "SELECT f.id, f.origen, f.destino, f.chofer_id, f.unidad_id, f.cliente_id,
                      f.estatus, f.tarifa,
                      c.razon_social AS cliente, ch.nombre AS chofer, u.placa AS unidad
               FROM fletes f
               LEFT JOIN clientes c  ON f.cliente_id = c.id
               LEFT JOIN choferes ch ON f.chofer_id  = ch.id
               LEFT JOIN unidades u  ON f.unidad_id  = u.id";

// Ejecuta un SELECT y devuelve todas las filas como arreglo
function consultar($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    foreach ($params as $nombre => $valor) {
        $stmt->bindValue($nombre, $valor);
    }
    $resultado = $stmt->execute();
    $filas = [];
    while ($fila = $resultado->fetchArray(SQLITE3_ASSOC)) {
        $filas[] = $fila;
    }
    return $filas;
}

if ($metodo === 'GET') {

    // listas del dashboard
    if ($accion === 'dashboard') {
        $kpis = consultar($db, "SELECT
                COUNT(*) AS total_fletes,
                SUM(CASE WHEN estatus = 'en tránsito' THEN 1 ELSE 0 END) AS en_transito,
                SUM(CASE WHEN estatus = 'pendiente de pago' THEN 1 ELSE 0 END) AS pendientes_pago,
                COALESCE(SUM(CASE WHEN estatus = 'pendiente de pago' THEN tarifa END), 0) AS por_cobrar,
                COALESCE(SUM(CASE WHEN estatus = 'pagado' THEN tarifa END), 0) AS cobrado,
                COALESCE(AVG(CASE WHEN estatus != 'cancelado' THEN tarifa END), 0) AS tarifa_promedio
            FROM fletes")[0];

        echo json_encode([
            'kpis'            => $kpis,
            'en_transito'     => consultar($db, $sql_fletes . " WHERE f.estatus = 'en tránsito' ORDER BY f.id DESC"),
            'pendientes_pago' => consultar($db, $sql_fletes . " WHERE f.estatus = 'pendiente de pago' ORDER BY f.id DESC")
        ]);
        exit;
    }

    //  fletes pendientes de pago
    if ($accion === 'por_pagar') {
        echo json_encode(consultar($db, $sql_fletes . " WHERE f.estatus = 'pendiente de pago' ORDER BY f.id DESC"));
        exit;
    }

    // datos del cliente + su historial de fletes
    if ($accion === 'cliente') {
        $cliente = consultar($db, "SELECT * FROM clientes WHERE id = :id", [':id' => $id]);
        if (!$cliente) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no encontrado']);
            exit;
        }
        $fletes = consultar($db, $sql_fletes . " WHERE f.cliente_id = :id ORDER BY f.id DESC", [':id' => $id]);

        $total_facturado = 0;
        $por_cobrar = 0;
        foreach ($fletes as $f) {
            if ($f['estatus'] !== 'cancelado') { $total_facturado += $f['tarifa']; }
            if ($f['estatus'] === 'pendiente de pago') { $por_cobrar += $f['tarifa']; }
        }

        echo json_encode([
            'cliente'         => $cliente[0],
            'fletes'          => $fletes,
            'total_facturado' => $total_facturado,
            'por_cobrar'      => $por_cobrar
        ]);
        exit;
    }

    if ($id > 0) {
        $flete = consultar($db, $sql_fletes . " WHERE f.id = :id", [':id' => $id]);
        if (!$flete) {
            http_response_code(404);
            echo json_encode(['error' => 'Flete no encontrado']);
            exit;
        }
        echo json_encode($flete[0]);
        exit;
    }

    echo json_encode(consultar($db, $sql_fletes . " ORDER BY f.id DESC"));
    exit;
}

// crear flete
if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (empty($datos['origen']) || empty($datos['destino']) || empty($datos['cliente_id']) || empty($datos['tarifa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Origen, destino, cliente y tarifa son obligatorios']);
        exit;
    }

    // Todo flete nuevo 
    $stmt = $db->prepare("INSERT INTO fletes (origen, destino, chofer_id, unidad_id, cliente_id, estatus, tarifa)
                          VALUES (:origen, :destino, :chofer_id, :unidad_id, :cliente_id, 'en tránsito', :tarifa)");
    $stmt->bindValue(':origen', $datos['origen'], SQLITE3_TEXT);
    $stmt->bindValue(':destino', $datos['destino'], SQLITE3_TEXT);
    $stmt->bindValue(':chofer_id', (int)($datos['chofer_id'] ?? 0), SQLITE3_INTEGER);
    $stmt->bindValue(':unidad_id', (int)($datos['unidad_id'] ?? 0), SQLITE3_INTEGER);
    $stmt->bindValue(':cliente_id', (int)$datos['cliente_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':tarifa', (float)$datos['tarifa'], SQLITE3_FLOAT);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Flete guardado con éxito', 'id' => $db->lastInsertRowID()]);
    exit;
}

//editar datos del flete o cambiar solo su estatus 
if ($metodo === 'PUT') {
    $datos = json_decode(file_get_contents('php://input'), true);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el id del flete']);
        exit;
    }

    // Cambio de estatus
    if ($accion === 'estatus') {
        if (!in_array($datos['estatus'] ?? '', $estatus_validos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estatus no válido', 'validos' => $estatus_validos]);
            exit;
        }
        $stmt = $db->prepare("UPDATE fletes SET estatus = :estatus WHERE id = :id");
        $stmt->bindValue(':estatus', $datos['estatus'], SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['mensaje' => 'Estatus actualizado', 'estatus' => $datos['estatus']]);
        exit;
    }

    if (empty($datos['origen']) || empty($datos['destino']) || empty($datos['cliente_id']) || empty($datos['tarifa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Origen, destino, cliente y tarifa son obligatorios']);
        exit;
    }

    $stmt = $db->prepare("UPDATE fletes SET origen = :origen, destino = :destino, chofer_id = :chofer_id,
                          unidad_id = :unidad_id, cliente_id = :cliente_id, tarifa = :tarifa WHERE id = :id");
    $stmt->bindValue(':origen', $datos['origen'], SQLITE3_TEXT);
    $stmt->bindValue(':destino', $datos['destino'], SQLITE3_TEXT);
    $stmt->bindValue(':chofer_id', (int)($datos['chofer_id'] ?? 0), SQLITE3_INTEGER);
    $stmt->bindValue(':unidad_id', (int)($datos['unidad_id'] ?? 0), SQLITE3_INTEGER);
    $stmt->bindValue(':cliente_id', (int)$datos['cliente_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':tarifa', (float)$datos['tarifa'], SQLITE3_FLOAT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Flete actualizado con éxito']);
    exit;
}
//para eliminar
if ($metodo === 'DELETE') {
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el id del flete']);
        exit;
    }
    $stmt = $db->prepare("DELETE FROM fletes WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Flete eliminado']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
?>
