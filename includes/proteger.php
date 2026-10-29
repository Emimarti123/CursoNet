<?php
// Va al inicio de cada página privada.
session_start();
require_once "conexion.php";
require_once "includes/funciones.php";

// 1. ¿Inició sesión? Si no, al login.
if (!isset($_SESSION["usuario_id"])) {
    irA("login.php");
}

// 2. Volvemos a leer al usuario de la base de datos, por si el Super Admin
//    lo desactivó o le cambió el rol mientras estaba conectado.
$yo = consulta("SELECT nombre, rol, foto, activo FROM usuarios WHERE id = ?", [$_SESSION["usuario_id"]])
    ->fetch_assoc();

if (!$yo || $yo["activo"] == 0) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION["nombre"] = $yo["nombre"];
$_SESSION["rol"]    = $yo["rol"];

// Atajos que usan todas las páginas
$miId   = $_SESSION["usuario_id"];
$rol    = $_SESSION["rol"];
$miFoto = $yo["foto"];

// Cuántas notificaciones no ha leído (el numerito de la campanita)
$sinLeer = consulta("SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = ? AND leida = 0", [$miId])
    ->fetch_assoc()["total"];
