<?php
session_start();
include_once "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        $mensaje = "Usuario no encontrado.";
    } else {
        $usuario = $resultado->fetch_assoc();

        if (!password_verify($password, $usuario["password"])) {
            $mensaje = "Contraseña incorrecta.";
        } else {
            session_regenerate_id(true);
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"]     = $usuario["nombre"];
            $_SESSION["rol"]        = $usuario["rol"];
            header("Location: panel.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
</head>
<body>
    <h1>Iniciar sesión</h1>

    <?php if ($mensaje !== "") { ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php } ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="registro.php">Crear cuenta</a></p>
</body>
</html>
