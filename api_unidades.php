<?php
header('Content-Type: application/json');
$db = new SQLite3('database.sqlite');

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $resultado = $db->query("SELECT * FROM unidades");
    $unidades = [];
    while ($fila = $resultado->fetchArray(SQLITE3_ASSOC)) {
        $unidades[] = $fila;
    }
    echo json_encode($unidades);
    exit;
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (empty($datos['placa']) || empty($datos['marca']) || empty($datos['modelo']) || empty($datos['year'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos de la unidad son obligatorios']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO unidades (placa, marca, modelo, year) VALUES (:placa, :marca, :modelo, :year)");
    $stmt->bindValue(':placa', $datos['placa'], SQLITE3_TEXT);
    $stmt->bindValue(':marca', $datos['marca'], SQLITE3_TEXT);
    $stmt->bindValue(':modelo', $datos['modelo'], SQLITE3_TEXT);
    $stmt->bindValue(':year', (int)$datos['year'], SQLITE3_INTEGER);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Unidad guardada con éxito', 'id' => $db->lastInsertRowID()]);
    exit;
}
?>