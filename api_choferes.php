<?php
header('Content-Type: application/json');
$db = new SQLite3('database.sqlite');

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $resultado = $db->query("SELECT * FROM choferes");
    $choferes = [];
    while ($fila = $resultado->fetchArray(SQLITE3_ASSOC)) {
        $choferes[] = $fila;
    }
    echo json_encode($choferes);
    exit;
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (empty($datos['nombre']) || empty($datos['licencia'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre y licencia son obligatorios']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO choferes (nombre, licencia) VALUES (:nombre, :licencia)");
    $stmt->bindValue(':nombre', $datos['nombre'], SQLITE3_TEXT);
    $stmt->bindValue(':licencia', $datos['licencia'], SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Chofer guardado con éxito', 'id' => $db->lastInsertRowID()]);
    exit;
}
?>