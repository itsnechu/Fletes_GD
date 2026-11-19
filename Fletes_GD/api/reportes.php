<?php
header('Content-Type: application/json');

$db = new SQLite3('../database.sqlite');

$accion = $_GET['accion'] ?? 'historial';

switch ($accion) {

    case 'historial':
        $res = $db->query("SELECT
        f.id, f.origen, f.destino, f.estatus, f.tarifa,
        c.razon_social as cliente, ch.nombre as chofer, u.placa as unidad
        FROM fletes f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN choferes ch ON f.chofer_id = ch.id
        LEFT JOIN unidades u ON f.unidad_id = u.id
        ORDER BY f.id DESC");

        $fletes = array();

        while ($fila = $res->fetchArray(SQLITE3_ASSOC)) {
            $fletes[] = $fila;
        }

        echo json_encode($fletes);
        break;

    case 'ingresos':
        $res = $db->query("SELECT COUNT(*) as cantidad_fletes,
        SUM(tarifa) as total_cobrado
        FROM fletes
        WHERE estatus = 'pagado'");

        $ingresos = $res->fetchArray(SQLITE3_ASSOC);

        if ($ingresos['total_cobrado'] === null) {
            $ingresos['total_cobrado'] = 0;
        }

        echo json_encode(array(
            'cantidad_fletes' => $ingresos['cantidad_fletes'],
            'total_cobrado' => $ingresos['total_cobrado']
        ));
        break;

    case 'cuentas':
        // Nota: Los fletes entregados también se consideran pendientes de cobro.
        $res = $db->query("SELECT
        c.id as cliente_id, c.razon_social as cliente,
        COUNT(f.id) as cantidad_fletes, SUM(f.tarifa) as total_pendiente
        FROM fletes f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        WHERE f.estatus = 'entregado'
        OR f.estatus = 'pendiente de pago'
        GROUP BY c.id, c.razon_social
        ORDER BY total_pendiente DESC");

        $cuentas = array();

        while ($fila = $res->fetchArray(SQLITE3_ASSOC)) {

            if ($fila['cliente'] === null) {
                $fila['cliente'] = 'Sin cliente asignado';
            }

            $cuentas[] = $fila;
        }

        echo json_encode($cuentas);
        break;

    case 'resumen':
        $res1 = $db->query("SELECT COUNT(*) as total_fletes FROM fletes");
        $resumen = $res1->fetchArray(SQLITE3_ASSOC);

        $res2 = $db->query("SELECT SUM(tarifa) as total_cobrado
        FROM fletes WHERE estatus = 'pagado'");
        $cobrado = $res2->fetchArray(SQLITE3_ASSOC);

        if ($cobrado['total_cobrado'] === null) {
            $cobrado['total_cobrado'] = 0;
        }

        $res3 = $db->query("SELECT SUM(tarifa) as total_por_cobrar
        FROM fletes
        WHERE estatus = 'entregado'
        OR estatus = 'pendiente de pago'");
        $por_cobrar = $res3->fetchArray(SQLITE3_ASSOC);

        if ($por_cobrar['total_por_cobrar'] === null) {
            $por_cobrar['total_por_cobrar'] = 0;
        }

        $resumen['total_cobrado'] = $cobrado['total_cobrado'];
        $resumen['total_por_cobrar'] = $por_cobrar['total_por_cobrar'];

        $res4 = $db->query("SELECT estatus, COUNT(*) as cantidad
        FROM fletes
        GROUP BY estatus");

        $estatus = array();

        while ($fila = $res4->fetchArray(SQLITE3_ASSOC)) {
            $estatus[] = $fila;
        }

        echo json_encode(array(
            'resumen' => $resumen,
            'estatus' => $estatus
        ));
        break;

    default:
        echo json_encode(array("error" => "accion no encontrada"));
        break;
}

$db->close();
?>