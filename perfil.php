<?php
require "includes/proteger.php";

$usuario = consulta("SELECT * FROM usuarios WHERE id = ?", [$miId])->fetch_assoc();
$errores = [];   // errores de cada campo: "campo" => "mensaje"

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Subir o cambiar la foto de perfil
    if ($_POST["accion"] === "foto") {
        $subida = subirArchivo("foto", "fotos", ["jpg", "jpeg", "png", "webp"]);

        // getimagesize revisa que de verdad sea una imagen (no solo que se llame .jpg)
        if ($subida["ruta"] !== "" && !getimagesize(__DIR__ . "/subidas/" . $subida["ruta"])) {
            borrarArchivo($subida["ruta"]);
            $subida = ["ruta" => "", "error" => "Ese archivo no es una imagen válida."];
        }
        if ($subida["error"] !== "") {
            $errores["foto"] = $subida["error"];
        } elseif ($subida["ruta"] === "") {
            $errores["foto"] = "Elige una imagen.";
        } else {
            borrarArchivo($usuario["foto"]);    // quitamos la foto anterior
            consulta("UPDATE usuarios SET foto = ? WHERE id = ?", [$subida["ruta"], $miId]);
            irA("perfil.php", "Foto actualizada.");
        }
    }

    if ($_POST["accion"] === "quitar_foto") {
        borrarArchivo($usuario["foto"]);
        consulta("UPDATE usuarios SET foto = NULL WHERE id = ?", [$miId]);
        irA("perfil.php", "Foto eliminada.");
    }

    // Cambiar el nombre
    if ($_POST["accion"] === "datos") {
        $usuario["nombre"] = trim($_POST["nombre"]);
        if ($usuario["nombre"] === "") {
            $errores["nombre"] = "El nombre no puede quedar vacío.";
        } elseif (mb_strlen($usuario["nombre"]) > 100) {
            $errores["nombre"] = "El nombre puede tener máximo 100 caracteres.";
        } else {
            consulta("UPDATE usuarios SET nombre = ? WHERE id = ?", [$usuario["nombre"], $miId]);
            irA("perfil.php", "Nombre actualizado.");
        }
    }

    // Cambiar la contraseña (pidiendo primero la actual)
    if ($_POST["accion"] === "password") {
        if (!password_verify($_POST["actual"], $usuario["password"])) {
            $errores["actual"] = "La contraseña actual no es correcta.";
        }
        if (mb_strlen($_POST["nueva"]) < 6) {
            $errores["nueva"] = "La contraseña debe tener al menos 6 caracteres.";
        }
        if ($_POST["nueva"] !== $_POST["confirmar"]) {
            $errores["confirmar"] = "Las contraseñas no coinciden.";
        }

        if (count($errores) === 0) {
            consulta("UPDATE usuarios SET password = ? WHERE id = ?",
                [password_hash($_POST["nueva"], PASSWORD_DEFAULT), $miId]);
            irA("perfil.php", "Contraseña cambiada.");
        }
    }
}

$titulo = "Mi perfil";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div class="fila" style="gap: 24px">
        <?php avatar($_SESSION["nombre"], $usuario["foto"], "avatar-grande"); ?>
        <div>
            <p class="mono texto-gris" style="margin: 0">Mi perfil</p>
            <h1 style="margin: 0"><?php e($_SESSION["nombre"]); ?></h1>
            <span class="texto-gris">
                <?php e($usuario["email"]); ?> · <?php echo $nombresRoles[$usuario["rol"]]; ?>
                · Miembro desde <?php echo fecha($usuario["created_at"]); ?>
            </span>
        </div>
    </div>
</div>

<div class="rejilla rejilla-2">
    <!-- enctype="multipart/form-data" es necesario para poder mandar archivos -->
    <form method="POST" enctype="multipart/form-data" class="tarjeta" novalidate>
        <h2>Foto de perfil</h2>
        <input type="hidden" name="accion" value="foto">
        <div class="campo">
            <label for="foto">Elige una imagen</label>
            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
            <small>JPG, PNG o WEBP. Máximo 2 MB. Se ve mejor si es cuadrada.</small>
            <?php errorCampo($errores, "foto"); ?>
        </div>
        <div class="fila entre">
            <button type="submit" class="boton">Subir foto</button>
            <?php if ($usuario["foto"]) { ?>
                <button type="submit" form="quitar-foto" class="enlace-peligro">Quitar foto</button>
            <?php } ?>
        </div>
    </form>
    <form method="POST" id="quitar-foto" data-confirmar="¿Quitar tu foto de perfil?">
        <input type="hidden" name="accion" value="quitar_foto">
    </form>

    <form method="POST" class="tarjeta validar" novalidate>
        <h2>Mis datos</h2>
        <input type="hidden" name="accion" value="datos">
        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" value="<?php e($usuario["nombre"]); ?>" maxlength="100" required>
            <?php errorCampo($errores, "nombre"); ?>
        </div>
        <button type="submit" class="boton">Guardar</button>
    </form>

    <form method="POST" class="tarjeta validar" novalidate>
        <h2>Cambiar contraseña</h2>
        <input type="hidden" name="accion" value="password">
        <div class="campo">
            <label for="actual">Contraseña actual</label>
            <input type="password" id="actual" name="actual" required>
            <?php errorCampo($errores, "actual"); ?>
        </div>
        <div class="campo">
            <label for="nueva">Nueva contraseña</label>
            <input type="password" id="nueva" name="nueva" minlength="6" required>
            <?php errorCampo($errores, "nueva"); ?>
        </div>
        <div class="campo">
            <label for="confirmar">Repite la nueva contraseña</label>
            <input type="password" id="confirmar" name="confirmar" data-igual-a="nueva" required>
            <?php errorCampo($errores, "confirmar"); ?>
        </div>
        <button type="submit" class="boton">Cambiar contraseña</button>
    </form>
</div>

<?php include "includes/abajo.php"; ?>
