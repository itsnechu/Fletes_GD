<?php
$archivo_db = 'database.sqlite';
$db = new SQLite3($archivo_db);

$db->exec("
    CREATE TABLE IF NOT EXISTS choferes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        licencia TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS unidades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        placa TEXT NOT NULL,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        year INTEGER NOT NULL
    );

    CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        razon_social TEXT NOT NULL,
        rfc TEXT NOT NULL,
        email TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS fletes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        origen TEXT NOT NULL,
        destino TEXT NOT NULL,
        chofer_id INTEGER,
        unidad_id INTEGER,
        cliente_id INTEGER,
        estatus TEXT NOT NULL,
        tarifa REAL NOT NULL,
        FOREIGN KEY(chofer_id) REFERENCES choferes(id),
        FOREIGN KEY(unidad_id) REFERENCES unidades(id),
        FOREIGN KEY(cliente_id) REFERENCES clientes(id)
    );
");
?>