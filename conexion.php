<?php
$host      = "localhost";
$usuario   = "root";
$password  = "Sistemas";
$baseDatos = "cursos";

// Si MySQL está apagado o los datos están mal, mostramos un mensaje claro
try {
    $conexion = new mysqli($host, $usuario, $password, $baseDatos);
} catch (mysqli_sql_exception $e) {
    die("No se pudo conectar a la base de datos. Revisa que MySQL esté encendido.");
}
$conexion->set_charset("utf8mb4");
