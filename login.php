<?php
session_start();
require_once "conexion.php";
require_once "includes/funciones.php";

// Si ya inició sesión, no tiene caso mostrar el login
if (isset($_SESSION["usuario_id"])) {
    irA("panel.php");
}

$errores = [];   // errores de cada campo
$error   = "";   // error general
$email   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email === "") {
        $errores["email"] = "Escribe tu correo.";
    }
    if ($password === "") {
        $errores["password"] = "Escribe tu contraseña.";
    }

    $usuario = consulta("SELECT * FROM usuarios WHERE email = ?", [$email])->fetch_assoc();

    // Mismo mensaje si falla el correo o la contraseña: así nadie
    // puede averiguar qué correos están registrados.
    if (count($errores) > 0) {
        // ya hay errores en los campos; no seguimos
    } elseif (!$usuario || !password_verify($password, $usuario["password"])) {
        $error = "Correo o contraseña incorrectos.";
    } elseif ($usuario["activo"] == 0) {
        $error = "Tu cuenta está desactivada. Contacta al administrador.";
    } else {
        session_regenerate_id(true);
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["nombre"]     = $usuario["nombre"];
        $_SESSION["rol"]        = $usuario["rol"];
        irA("panel.php");
    }
}

$titulo = "Iniciar sesión";
include "includes/arriba_publico.php";
?>

<h2>Bienvenido(a)</h2>
<p class="texto-gris">Inicia sesión para continuar.</p>

<?php if ($error !== "") { ?>
    <div class="aviso aviso-error"><?php e($error); ?></div>
<?php } ?>

<form method="POST" class="validar" novalidate>
    <div class="campo">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?php e($email); ?>" placeholder="tu@correo.com" required>
        <?php errorCampo($errores, "email"); ?>
    </div>

    <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <?php errorCampo($errores, "password"); ?>
    </div>

    <button type="submit" class="boton boton-ancho">Iniciar sesión</button>
</form>

<p class="texto-gris">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>

<?php include "includes/abajo_publico.php"; ?>
