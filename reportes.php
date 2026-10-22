<?php
header('Content-Type: application/json');

$db = new SQLite3('database.sqlite');

$accion = $_GET['accion'] ?? 'historial';

switch ($accion) {
    
    case 'historial':
        $res = $db->query("SELECT f.id, f.origen, f.destino, f.estatus, f.tarifa, c.razon_social as cliente, 
        ch.nombre as chofer, u.placa as unidad FROM fletes f LEFT JOIN clientes c ON f.cliente_id = c.id LEFT JOIN choferes ch ON f.chofer_id = ch.id LEFT JOIN unidades u ON f.unidad_id = u.id ORDER BY f.id DESC");
        
        $fletes = array();
        while ($fila = $res->fetchArray(SQLITE3_ASSOC)) {
            $fletes[] = $fila;
        }
        echo json_encode($fletes);
        break;

    case 'ingresos':
        $res = $db->query("SELECT estatus, COUNT(*) as cantidad, SUM(tarifa) as total FROM fletes GROUP BY estatus");
        
        $datos = array();
        $total_general = 0;
        while ($fila = $res->fetchArray(SQLITE3_ASSOC)) {
            $datos[] = $fila;
            $total_general += $fila['total'];
        }
        
        echo json_encode(array(
            'total_general' => $total_general,
            'por_estatus' => $datos
        ));
        break;

    case 'resumen':
        $res1 = $db->query("SELECT COUNT(*) as total_fletes, SUM(tarifa) as total_tarifas FROM fletes");
        $resumen = $res1->fetchArray(SQLITE3_ASSOC);

        $res2 = $db->query("SELECT estatus, COUNT(*) as cantidad FROM fletes GROUP BY estatus");
        $estatus = array();
        while ($fila = $res2->fetchArray(SQLITE3_ASSOC)) {
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
