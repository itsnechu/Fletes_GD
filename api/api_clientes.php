<?php
header('Content-Type: application/json');

$db = new SQLite3('../database.sqlite');

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $resultado = $db->query("SELECT * FROM clientes");
    $clientes = [];
    while ($fila = $resultado->fetchArray(SQLITE3_ASSOC)) {
        $clientes[] = $fila;
    }
    echo json_encode($clientes);
    exit;
}

if ($metodo === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);

    if (empty($datos['razon_social']) || empty($datos['rfc']) || empty($datos['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Razón social, RFC y email son obligatorios']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO clientes (razon_social, rfc, email) VALUES (:razon_social, :rfc, :email)");
    $stmt->bindValue(':razon_social', $datos['razon_social'], SQLITE3_TEXT);
    $stmt->bindValue(':rfc', $datos['rfc'], SQLITE3_TEXT);
    $stmt->bindValue(':email', $datos['email'], SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(['mensaje' => 'Cliente guardado con éxito', 'id' => $db->lastInsertRowID()]);
    exit;
}
?>
