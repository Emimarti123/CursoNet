<?php
require "includes/proteger.php";

// Borrar todas las que ya leí
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["accion"] === "limpiar") {
    consulta("DELETE FROM notificaciones WHERE usuario_id = ? AND leida = 1", [$miId]);
    irA("notificaciones.php", "Notificaciones leídas eliminadas.");
}

// Abrir una notificación: la marca como leída y lleva a su enlace
if (isset($_GET["abrir"])) {
    $n = consulta("SELECT * FROM notificaciones WHERE id = ? AND usuario_id = ?", [(int) $_GET["abrir"], $miId])->fetch_assoc();
    if ($n) {
        consulta("UPDATE notificaciones SET leida = 1 WHERE id = ?", [$n["id"]]);
        // Solo seguimos enlaces internos (ej. "tarea.php?id=3"), nunca a otros sitios
        if ($n["enlace"] && preg_match('#^[a-z_]+\.php#', $n["enlace"])) {
            irA($n["enlace"]);
        }
    }
    irA("notificaciones.php");
}

// Marcar todas como leídas
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["accion"] === "leer_todas") {
    consulta("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?", [$miId]);
    irA("notificaciones.php");
}

$total  = consulta("SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = ?", [$miId])->fetch_assoc()["total"];
$pagina = paginar($total, 20);
$notificaciones = consulta(
    "SELECT * FROM notificaciones WHERE usuario_id = ?
     ORDER BY fecha DESC, id DESC
     LIMIT {$pagina["por"]} OFFSET {$pagina["desde"]}",
    [$miId]
)->fetch_all(MYSQLI_ASSOC);

$titulo = "Notificaciones";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris"><?php echo $sinLeer; ?> sin leer</p>
        <h1>Notificaciones</h1>
        <p class="texto-gris">Tareas, calificaciones, avisos y mensajes de tus cursos.</p>
    </div>
    <div class="fila">
        <?php if ($sinLeer > 0) { ?>
            <form method="POST">
                <input type="hidden" name="accion" value="leer_todas">
                <button type="submit" class="boton boton-secundario">Marcar todas como leídas</button>
            </form>
        <?php } ?>
        <?php if ($total > $sinLeer) { ?>
            <form method="POST" data-confirmar="¿Borrar todas las notificaciones que ya leíste?">
                <input type="hidden" name="accion" value="limpiar">
                <button type="submit" class="enlace-peligro">Borrar leídas</button>
            </form>
        <?php } ?>
    </div>
</div>

<div class="tarjeta">
    <?php if (count($notificaciones) === 0) { ?>
        <div class="vacio">
            <?php dibujoVacio(); ?>
            <p>No tienes notificaciones.</p>
        </div>
    <?php } ?>

    <ul class="lista-temas">
        <?php foreach ($notificaciones as $n) { ?>
            <li style="<?php echo $n["leida"] ? "" : "font-weight: 700"; ?>">
                <span class="marca <?php echo $n["leida"] ? "" : "hecha"; ?>" aria-hidden="true"><?php echo $n["leida"] ? "" : "•"; ?></span>
                <a href="notificaciones.php?abrir=<?php echo $n["id"]; ?>">
                    <?php e($n["mensaje"]); ?>
                    <?php if (!$n["leida"]) { ?><span class="etiqueta etiqueta-alerta" style="margin-left: 8px">Nueva</span><?php } ?>
                </a>
                <span class="mono texto-gris" style="font-weight: 400"><?php echo date("d/m H:i", strtotime($n["fecha"])); ?></span>
            </li>
        <?php } ?>
    </ul>
    <?php enlacesPaginas($pagina); ?>
</div>

<?php include "includes/abajo.php"; ?>
