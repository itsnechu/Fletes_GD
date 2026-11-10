<?php
require 'db.php';

$id   = $_GET['id'] ?? null;
$body = leerBody();

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare('SELECT * FROM clientes WHERE id = ?');
            $stmt->execute([$id]);
            $cliente = $stmt->fetch();
            if (!$cliente) responder(['error' => 'No existe'], 404);

            $stmt = $db->prepare(
                'SELECT f.*, c.nombre AS chofer, u.placa AS unidad
                 FROM fletes f
                 LEFT JOIN choferes c ON c.id = f.chofer_id
                 LEFT JOIN unidades u ON u.id = f.unidad_id
                 WHERE f.cliente_id = ?
                 ORDER BY f.id DESC'
            );
            $stmt->execute([$id]);
            $cliente['fletes'] = $stmt->fetchAll();

            responder($cliente);
        }
        responder($db->query('SELECT * FROM clientes ORDER BY id')->fetchAll());

    case 'POST':
        if (empty($body['razon_social'])) responder(['error' => 'La razón social es obligatoria'], 400);
        $stmt = $db->prepare('INSERT INTO clientes (razon_social, rfc, email) VALUES (?, ?, ?)');
        $stmt->execute([$body['razon_social'], $body['rfc'] ?? '', $body['email'] ?? '']);
        responder(['id' => $db->lastInsertId()], 201);

    case 'PUT':
        if (!$id) responder(['error' => 'Falta el id'], 400);
        $stmt = $db->prepare('UPDATE clientes SET razon_social = ?, rfc = ?, email = ? WHERE id = ?');
        $stmt->execute([$body['razon_social'] ?? '', $body['rfc'] ?? '', $body['email'] ?? '', $id]);
        responder(['ok' => true]);

    case 'DELETE':
        if (!$id) responder(['error' => 'Falta el id'], 400);
        $stmt = $db->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->execute([$id]);
        responder(['ok' => true]);

    default:
        responder(['error' => 'Método no permitido'], 405);
}
