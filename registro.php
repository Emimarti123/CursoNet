<?php
session_start();
require_once "conexion.php";
require_once "includes/funciones.php";

if (isset($_SESSION["usuario_id"])) {
    irA("panel.php");
}

$errores = [];   // errores de cada campo: "campo" => "mensaje"
$error   = "";   // error general (no es de un campo en particular)
$nombre  = "";
$email   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre    = trim($_POST["nombre"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirmar = $_POST["confirmar"];

    // Revisamos cada campo; si algo está mal, guardamos el mensaje para ese campo
    if ($nombre === "") {
        $errores["nombre"] = "Escribe tu nombre.";
    } elseif (mb_strlen($nombre) > 100) {
        $errores["nombre"] = "El nombre puede tener máximo 100 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errores["email"] = "Escribe un correo válido (ej. nombre@correo.com).";
    }
    if (mb_strlen($password) < 6) {
        $errores["password"] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if ($password !== $confirmar) {
        $errores["confirmar"] = "Las contraseñas no coinciden.";
    }

    // Solo guardamos si no hubo ningún error
    if (count($errores) === 0) {
        // Quien se registra solo siempre es alumno.
        // Los demás roles los asigna el Super Admin.
        try {
            consulta(
                "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'alumno')",
                [$nombre, $email, password_hash($password, PASSWORD_DEFAULT)]
            );
            irA("login.php", "¡Cuenta creada! Ya puedes iniciar sesión.");
        } catch (mysqli_sql_exception $e) {
            // 1062 = el email ya existe
            if ($e->getCode() === 1062) {
                $errores["email"] = "Ese correo ya está registrado.";
            } else {
                $error = "No se pudo completar el registro.";
            }
        }
    }
}

$titulo = "Crear cuenta";
include "includes/arriba_publico.php";
?>

<h2>Crea tu cuenta</h2>
<p class="texto-gris">Es gratis y solo toma un minuto.</p>

<?php if ($error !== "") { ?>
    <div class="aviso aviso-error"><?php e($error); ?></div>
<?php } ?>

<!-- novalidate: apaga los globos del navegador; los errores se muestran debajo de cada campo -->
<form method="POST" class="validar" novalidate>
    <div class="campo">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" value="<?php e($nombre); ?>" maxlength="100" required>
        <?php errorCampo($errores, "nombre"); ?>
    </div>

    <div class="campo">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?php e($email); ?>" placeholder="tu@correo.com" maxlength="150" required>
        <?php errorCampo($errores, "email"); ?>
    </div>

    <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" minlength="6" required>
        <?php if (isset($errores["password"])) { errorCampo($errores, "password"); } else { ?>
            <small>Mínimo 6 caracteres.</small>
        <?php } ?>
    </div>

    <div class="campo">
        <label for="confirmar">Repite la contraseña</label>
        <input type="password" id="confirmar" name="confirmar" data-igual-a="password" required>
        <?php errorCampo($errores, "confirmar"); ?>
    </div>

    <button type="submit" class="boton boton-ancho">Crear cuenta</button>
</form>

<p class="texto-gris">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

<?php include "includes/abajo_publico.php"; ?>
