<?php
session_start();

// Si no hay sesión, de regreso al login
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$roles = [
    "super_admin" => "super admin",
    "mini_admin"  => "mini admin",
    "ponente"     => "ponente",
    "alumno"      => "alumno",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi panel</title>
</head>
<body>
    <h1>Hola <?php echo htmlspecialchars($_SESSION["nombre"]); ?>, eres <?php echo $roles[$_SESSION["rol"]]; ?></h1>

    <a href="logout.php">Cerrar sesión</a>
</body>
</html>
