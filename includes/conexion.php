<?php

/*
    CONEXIÓN AUTOMÁTICA
    - Local: XAMPP / localhost
    - Hosting: InfinityFree
*/

$servidorActual = $_SERVER["HTTP_HOST"] ?? "localhost";

$esLocal = (
    strpos($servidorActual, "localhost") !== false ||
    strpos($servidorActual, "127.0.0.1") !== false
);

if ($esLocal) {

    /*
        CONFIGURACIÓN LOCAL
        Ajustada a tu XAMPP actual.
    */
    $host = "localhost";
    $db   = "album_panini";
    $user = "root";
    $pass = "";
    $port = "3307";

} else {

    /*
        CONFIGURACIÓN HOSTING - INFINITYFREE
    */
    $host = "sql201.infinityfree.com";
    $db   = "if0_42404139_album_panini";
    $user = "if0_42404139";
    $pass = "Rito3964";
    $port = "3306";
}

try {

    $conexion = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    if ($esLocal) {
        die("Error de conexión local: " . $e->getMessage());
    }

    die("Error de conexión a la base de datos del hosting.");
}