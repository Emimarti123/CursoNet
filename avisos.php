<?php
require "includes/proteger.php";

$cursoId = (int) ($_GET["curso"] ?? 0);
[$curso, $esDocente] = accesoCurso($cursoId);

$aqui    = "avisos.php?curso=" . $cursoId;
$errores = [];
$nuevo   = ["titulo" => "", "mensaje" => ""];

// ---------- Solo el docente del curso publica y borra avisos ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $esDocente) {
    if ($_POST["accion"] === "publicar") {
        $nuevo = ["titulo" => trim($_POST["titulo"]), "mensaje" => trim($_POST["mensaje"])];

        if ($nuevo["titulo"] === "") {
            $errores["titulo"] = "Escribe un título.";
        } elseif (mb_strlen($nuevo["titulo"]) > 150) {
            $errores["titulo"] = "El título puede tener máximo 150 caracteres.";
        }
        if ($nuevo["mensaje"] === "") {
            $errores["mensaje"] = "Escribe el mensaje del aviso.";
        }

        if (count($errores) === 0) {
            consulta("INSERT INTO avisos (curso_id, titulo, mensaje) VALUES (?, ?, ?)",
                [$cursoId, $nuevo["titulo"], $nuevo["mensaje"]]);
            notificarAlumnos($cursoId, "Nuevo aviso en " . $curso["titulo"] . ": " . $nuevo["titulo"], $aqui);
            irA($aqui, "Aviso publicado. Tus alumnos recibirán una notificación.");
        }
    }

    if ($_POST["accion"] === "borrar") {
        consulta("DELETE FROM avisos WHERE id = ? AND curso_id = ?", [(int) $_POST["aviso_id"], $cursoId]);
        irA($aqui, "Aviso eliminado.");
    }
}

$avisos = consulta("SELECT * FROM avisos WHERE curso_id = ? ORDER BY fecha DESC", [$cursoId])->fetch_all(MYSQLI_ASSOC);

[$volver, $menuActivo] = volverAlCurso($cursoId);
$titulo = "Avisos";
include "includes/arriba.php";
?>

<?php if ($esDocente) { ?>
    <?php $pestana = "avisos"; include "includes/curso_docente.php"; ?>
    <p class="texto-gris">Publica anuncios para todos tus alumnos. Les llega una notificación a cada uno.</p>
<?php } else { ?>
    <div class="migas"><a href="<?php echo $volver; ?>">← <?php e($curso["titulo"]); ?></a></div>
    <div class="encabezado">
        <div>
            <p class="mono texto-gris">Tablero del curso</p>
            <h1>Avisos</h1>
            <p class="texto-gris">Anuncios importantes de tu docente.</p>
        </div>
    </div>
<?php } ?>

<?php if ($esDocente) { ?>
    <form method="POST" class="tarjeta validar" novalidate>
        <input type="hidden" name="accion" value="publicar">
        <div class="campo">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" value="<?php e($nuevo["titulo"]); ?>" maxlength="150" placeholder="Ej. Cambio de fecha de la tarea 2" required>
            <?php errorCampo($errores, "titulo"); ?>
        </div>
        <div class="campo">
            <label for="mensaje">Mensaje</label>
            <textarea id="mensaje" name="mensaje" rows="4" required><?php e($nuevo["mensaje"]); ?></textarea>
            <?php errorCampo($errores, "mensaje"); ?>
        </div>
        <button type="submit" class="boton">Publicar aviso</button>
    </form>
<?php } ?>

<?php if (count($avisos) === 0) { ?>
    <div class="tarjeta vacio">
        <?php dibujoVacio(); ?>
        <p>Todavía no hay avisos.</p>
    </div>
<?php } ?>

<?php foreach ($avisos as $a) { ?>
    <article class="tarjeta">
        <div class="fila entre">
            <span class="mono texto-gris"><?php echo date("d/m/Y H:i", strtotime($a["fecha"])); ?></span>
            <?php if ($esDocente) { ?>
                <form method="POST" data-confirmar="¿Eliminar este aviso?">
                    <input type="hidden" name="accion" value="borrar">
                    <input type="hidden" name="aviso_id" value="<?php echo $a["id"]; ?>">
                    <button type="submit" class="enlace-peligro">Eliminar</button>
                </form>
            <?php } ?>
        </div>
        <h2 style="margin: 8px 0"><?php e($a["titulo"]); ?></h2>
        <p style="margin: 0"><?php echo nl2br(htmlspecialchars($a["mensaje"])); ?></p>
    </article>
<?php } ?>

<?php include "includes/abajo.php"; ?>
