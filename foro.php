<?php
require "includes/proteger.php";

$cursoId = (int) ($_GET["id"] ?? 0);

// Revisa quién puede entrar (alumno inscrito, docente del curso, admin o director)
[$curso, $esDocenteDelCurso] = accesoCurso($cursoId);

// El docente del curso y el Super Admin pueden borrar cualquier mensaje
$puedeModerar = $esDocenteDelCurso || $rol === "super_admin";

$aqui = "foro.php?id=" . $cursoId;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["accion"] === "publicar" && ($rol === "alumno" || $esDocenteDelCurso)) {
        $mensaje = trim($_POST["mensaje"]);
        if ($mensaje !== "") {
            consulta("INSERT INTO comentarios (curso_id, usuario_id, mensaje) VALUES (?, ?, ?)",
                [$cursoId, $miId, $mensaje]);
            // Si escribe un alumno, le avisamos al docente
            if ($rol === "alumno") {
                notificar($curso["docente_id"], $_SESSION["nombre"] . " escribió en el foro de " . $curso["titulo"] . ".", $aqui);
            }
        }
        irA($aqui);
    }

    if ($_POST["accion"] === "borrar") {
        // Se borra solo si el mensaje es mío, o si puedo moderar
        consulta(
            "DELETE FROM comentarios WHERE id = ? AND curso_id = ? AND (usuario_id = ? OR ? = 1)",
            [(int) $_POST["comentario_id"], $cursoId, $miId, $puedeModerar ? 1 : 0]
        );
        irA($aqui, "Mensaje eliminado.");
    }
}

// Paginación: 15 mensajes por página
$total  = consulta("SELECT COUNT(*) AS total FROM comentarios WHERE curso_id = ?", [$cursoId])->fetch_assoc()["total"];
$pagina = paginar($total, 15);

// LIMIT y OFFSET son números que calculamos nosotros (no vienen del usuario tal cual)
$mensajes = consulta(
    "SELECT m.*, u.nombre, u.rol, u.foto
     FROM comentarios m
     JOIN usuarios u ON m.usuario_id = u.id
     WHERE m.curso_id = ?
     ORDER BY m.fecha DESC
     LIMIT {$pagina["por"]} OFFSET {$pagina["desde"]}",
    [$cursoId]
)->fetch_all(MYSQLI_ASSOC);

[$volver, $menuActivo] = volverAlCurso($cursoId);

$titulo = "Foro";
include "includes/arriba.php";
?>

<?php if ($esDocenteDelCurso) { ?>
    <?php $pestana = "foro"; include "includes/curso_docente.php"; ?>
    <p class="texto-gris"><?php echo $total; ?> mensaje(s). Responde dudas y modera los mensajes de tus alumnos.</p>
<?php } else { ?>
    <div class="migas"><a href="<?php echo $volver; ?>">← <?php e($curso["titulo"]); ?></a></div>
    <div class="encabezado">
        <div>
            <p class="mono texto-gris"><?php echo $total; ?> mensaje(s)</p>
            <h1>Foro del curso</h1>
            <p class="texto-gris">Haz preguntas y comparte dudas con tu docente y compañeros.</p>
        </div>
    </div>
<?php } ?>

<?php if ($rol === "alumno" || $esDocenteDelCurso) { ?>
    <form method="POST" class="tarjeta">
        <input type="hidden" name="accion" value="publicar">
        <div class="campo">
            <textarea name="mensaje" rows="3" placeholder="Escribe tu mensaje..." required></textarea>
        </div>
        <button type="submit" class="boton">Publicar mensaje</button>
    </form>
<?php } ?>

<div class="tarjeta">
    <?php if (count($mensajes) === 0) { ?>
        <p class="vacio">Todavía no hay mensajes. ¡Sé el primero en escribir!</p>
    <?php } ?>

    <?php foreach ($mensajes as $m) { ?>
        <div class="mensaje">
            <?php avatar($m["nombre"], $m["foto"]); ?>
            <div style="flex: 1">
                <div class="fila">
                    <strong><?php e($m["nombre"]); ?></strong>
                    <?php if ($m["rol"] !== "alumno") { ?>
                        <span class="etiqueta"><?php echo $nombresRoles[$m["rol"]]; ?></span>
                    <?php } ?>
                    <span class="texto-gris"><?php echo date("d/m/Y H:i", strtotime($m["fecha"])); ?></span>
                </div>
                <p><?php echo nl2br(htmlspecialchars($m["mensaje"])); ?></p>
            </div>
            <?php if ($m["usuario_id"] == $miId || $puedeModerar) { ?>
                <form method="POST" data-confirmar="¿Eliminar este mensaje?">
                    <input type="hidden" name="accion" value="borrar">
                    <input type="hidden" name="comentario_id" value="<?php echo $m["id"]; ?>">
                    <button type="submit" class="enlace-peligro">Eliminar</button>
                </form>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<?php enlacesPaginas($pagina); ?>

<?php include "includes/abajo.php"; ?>
