<?php

require_once "../includes/conexion.php";

$sql = "INSERT INTO usuarios (nombre)
VALUES ('Dante')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";

try {
    $conexion->exec($sql);
    echo "Usuario Dante creado correctamente.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>