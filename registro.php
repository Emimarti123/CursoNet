<?php
include_once "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim($_POST["nombre"]);
    $email    = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $rol      = $_POST["rol"];

    $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $password, $rol);

    try {
        $stmt->execute();
        $mensaje = "Registro completado. Ya puedes iniciar sesión.";
        
    } catch (mysqli_sql_exception $e) {
        // 1062 = el email ya existe
        $mensaje = $e->getCode() === 1062
            ? "Ese email ya está registrado."
            : "No se pudo completar el registro.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
</head>
<body>
    <h1>Crear cuenta</h1>

    <?php if ($mensaje !== "") { ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php } ?>

    <form method="POST">
        <label>Nombre</label>
        <input type="text" name="nombre" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <label>Tipo de cuenta</label>
        <select name="rol" required>
            <option value="">Selecciona</option>
            <option value="super_admin">Super admin</option>
            <option value="mini_admin">Mini admin</option>
            <option value="ponente">Ponente</option>
            <option value="alumno">Alumno</option>
        </select>

        <button type="submit">Registrarme</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
</body>
</html>
