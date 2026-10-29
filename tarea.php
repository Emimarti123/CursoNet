<?php
require "includes/proteger.php";

$tareaId = (int) ($_GET["id"] ?? 0);
$tarea   = consulta("SELECT * FROM tareas WHERE id = ?", [$tareaId])->fetch_assoc();
if (!$tarea) {
    irA("panel.php", "Esa tarea no existe.", "error");
}

// Revisa que la persona pueda entrar al curso de esta tarea
[$curso, $esDocente] = accesoCurso($tarea["curso_id"]);

$aqui    = "tarea.php?id=" . $tareaId;
$errores = [];

// Tipos de archivo que se pueden entregar (máximo 2 MB)
$permitidos = ["pdf", "doc", "docx", "ppt", "pptx", "xls", "xlsx", "txt", "jpg", "jpeg", "png", "zip"];

// ---------- El alumno entrega su tarea ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $rol === "alumno" && $_POST["accion"] === "entregar") {
    $anterior = consulta("SELECT * FROM entregas WHERE tarea_id = ? AND alumno_id = ?", [$tareaId, $miId])->fetch_assoc();

    if ($anterior && $anterior["calificacion"] !== null) {
        irA($aqui, "Esta tarea ya fue calificada; no se puede cambiar.", "error");
    }

    $texto  = trim($_POST["texto"]);
    $subida = subirArchivo("archivo", "entregas", $permitidos);

    if ($subida["error"] !== "") {
        $errores["archivo"] = $subida["error"];
    }
    // Debe escribir algo o adjuntar un archivo (o ya tener uno de antes)
    if ($texto === "" && $subida["ruta"] === "" && !($anterior["archivo"] ?? null)) {
        $errores["texto"] = "Escribe tu respuesta o adjunta un archivo.";
    }

    if (count($errores) === 0) {
        $archivo = $anterior["archivo"] ?? null;
        if ($subida["ruta"] !== "") {
            borrarArchivo($archivo);       // si subió uno nuevo, quitamos el viejo
            $archivo = $subida["ruta"];
        }
        // Si ya había entregado, se actualiza (ON DUPLICATE KEY UPDATE)
        consulta(
            "INSERT INTO entregas (tarea_id, alumno_id, texto, archivo) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE texto = VALUES(texto), archivo = VALUES(archivo), fecha = NOW()",
            [$tareaId, $miId, $texto, $archivo]
        );
        notificar($curso["docente_id"], $_SESSION["nombre"] . " entregó la tarea " . $tarea["titulo"] . ".", $aqui);
        irA($aqui, $anterior ? "Entrega actualizada." : "¡Tarea entregada!");
    } elseif ($subida["ruta"] !== "") {
        borrarArchivo($subida["ruta"]);    // hubo otro error: no dejamos el archivo tirado
    }
}

// ---------- El docente califica ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $esDocente && $_POST["accion"] === "calificar") {
    $entregaId    = (int) $_POST["entrega_id"];
    $calificacion = $_POST["calificacion"];
    $comentario   = trim($_POST["comentario"]);

    if (!is_numeric($calificacion) || $calificacion < 0 || $calificacion > 100) {
        irA($aqui . "#entrega" . $entregaId, "La calificación debe ser un número del 0 al 100.", "error");
    }

    $entrega = consulta("SELECT alumno_id FROM entregas WHERE id = ? AND tarea_id = ?", [$entregaId, $tareaId])->fetch_assoc();
    if ($entrega) {
        consulta("UPDATE entregas SET calificacion = ?, comentario = ? WHERE id = ?",
            [(int) round($calificacion), $comentario, $entregaId]);
        notificar($entrega["alumno_id"], "Tu tarea " . $tarea["titulo"] . " fue calificada: " . (int) round($calificacion) . ".", $aqui);
    }
    irA($aqui, "Calificación guardada.");
}

// ---------- Datos para mostrar ----------
$miEntrega = consulta("SELECT * FROM entregas WHERE tarea_id = ? AND alumno_id = ?", [$tareaId, $miId])->fetch_assoc();

if ($rol !== "alumno") {
    // Todos los alumnos del curso, con su entrega si existe (LEFT JOIN)
    $alumnos = consulta(
        "SELECT u.id, u.nombre, u.foto, e.id AS entrega_id, e.texto, e.archivo, e.calificacion, e.comentario, e.fecha
         FROM inscripciones i
         JOIN usuarios u      ON i.alumno_id = u.id
         LEFT JOIN entregas e ON e.tarea_id = ? AND e.alumno_id = u.id
         WHERE i.curso_id = ?
         ORDER BY e.id IS NULL, e.calificacion IS NOT NULL, u.nombre",
        [$tareaId, $tarea["curso_id"]]
    )->fetch_all(MYSQLI_ASSOC);
}

$vencida = $tarea["fecha_limite"] && $tarea["fecha_limite"] < date("Y-m-d");
[, $menuActivo] = volverAlCurso($tarea["curso_id"]);
if ($rol === "alumno") {
    $menuActivo = "tareas.php";
}

$titulo = $tarea["titulo"];
include "includes/arriba.php";
?>

<div class="migas">
    <a href="tareas.php?curso=<?php echo $tarea["curso_id"]; ?>">← Tareas de <?php e($curso["titulo"]); ?></a>
</div>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Tarea</p>
        <h1><?php e($tarea["titulo"]); ?></h1>
        <p class="texto-gris">
            <?php if ($tarea["fecha_limite"]) { ?>
                Fecha límite: <?php echo fecha($tarea["fecha_limite"]); ?>
                <?php if ($vencida) { ?><span class="etiqueta etiqueta-roja">Vencida</span><?php } ?>
            <?php } else { ?>
                Sin fecha límite
            <?php } ?>
        </p>
    </div>
</div>

<div class="tarjeta tarjeta-grande">
    <h2>Instrucciones</h2>
    <?php if (trim($tarea["instrucciones"] ?? "") === "") { ?>
        <p class="texto-gris">El docente no escribió instrucciones.</p>
    <?php } else { ?>
        <div class="lectura texto-docente"><?php e($tarea["instrucciones"]); ?></div>
    <?php } ?>
</div>

<?php if ($rol === "alumno") { ?>
    <!-- ===================== Vista del ALUMNO ===================== -->
    <?php if ($miEntrega && $miEntrega["calificacion"] !== null) { ?>
        <div class="tarjeta tarjeta-grande" style="border-left: 6px solid var(--teal)">
            <div class="fila entre">
                <h2 style="margin: 0">Tu calificación</h2>
                <span class="nota"><?php echo $miEntrega["calificacion"]; ?><small>/ 100</small></span>
            </div>
            <?php if ($miEntrega["comentario"]) { ?>
                <p class="mono texto-gris" style="margin: 24px 0 4px">Comentario del docente</p>
                <p style="margin: 0"><?php echo nl2br(htmlspecialchars($miEntrega["comentario"])); ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($miEntrega) { ?>
        <div class="tarjeta">
            <h3>Lo que entregaste</h3>
            <p class="texto-gris">Entregado el <?php echo date("d/m/Y H:i", strtotime($miEntrega["fecha"])); ?></p>
            <?php if ($miEntrega["texto"]) { ?>
                <p><?php echo nl2br(htmlspecialchars($miEntrega["texto"])); ?></p>
            <?php } ?>
            <?php if ($miEntrega["archivo"]) { ?>
                <a href="subidas/<?php e($miEntrega["archivo"]); ?>" target="_blank" class="boton boton-secundario boton-chico">Ver mi archivo ↗</a>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!$miEntrega || $miEntrega["calificacion"] === null) { ?>
        <!-- enctype="multipart/form-data" es necesario para poder mandar archivos -->
        <form method="POST" enctype="multipart/form-data" class="tarjeta tarjeta-grande" novalidate>
            <input type="hidden" name="accion" value="entregar">
            <h2><?php echo $miEntrega ? "Cambiar mi entrega" : "Entregar tarea"; ?></h2>
            <?php if ($vencida) { ?>
                <div class="aviso aviso-error">La fecha límite ya pasó, pero todavía puedes entregar. Tu docente lo verá.</div>
            <?php } ?>
            <div class="campo">
                <label for="texto">Tu respuesta</label>
                <textarea id="texto" name="texto" rows="6" placeholder="Escribe aquí tu respuesta o un comentario para tu docente"><?php e($_POST["texto"] ?? $miEntrega["texto"] ?? ""); ?></textarea>
                <?php errorCampo($errores, "texto"); ?>
            </div>
            <div class="campo">
                <label for="archivo">Archivo (opcional)</label>
                <input type="file" id="archivo" name="archivo" accept=".<?php echo implode(",.", $permitidos); ?>">
                <small>PDF, Word, PowerPoint, Excel, imagen o ZIP. Máximo 2 MB.<?php echo ($miEntrega["archivo"] ?? null) ? " Si eliges otro, reemplaza al anterior." : ""; ?></small>
                <?php errorCampo($errores, "archivo"); ?>
            </div>
            <button type="submit" class="boton"><?php echo $miEntrega ? "Actualizar entrega" : "Entregar"; ?></button>
        </form>
    <?php } ?>

<?php } else { ?>
    <!-- ============ Vista del DOCENTE (y admin/director, solo lectura) ============ -->
    <h2 class="seccion">Entregas</h2>

    <?php if (count($alumnos) === 0) { ?>
        <div class="tarjeta vacio">Todavía no hay alumnos inscritos en este curso.</div>
    <?php } ?>

    <?php foreach ($alumnos as $a) { ?>
        <div class="tarjeta" id="entrega<?php echo $a["entrega_id"]; ?>">
            <div class="fila entre">
                <div class="fila">
                    <?php avatar($a["nombre"], $a["foto"]); ?>
                    <div>
                        <strong><?php e($a["nombre"]); ?></strong><br>
                        <span class="texto-gris">
                            <?php echo $a["entrega_id"] ? "Entregó el " . date("d/m/Y H:i", strtotime($a["fecha"])) : "Todavía no entrega"; ?>
                        </span>
                    </div>
                </div>
                <?php if (!$a["entrega_id"]) { ?>
                    <span class="etiqueta etiqueta-gris">Sin entregar</span>
                <?php } elseif ($a["calificacion"] === null) { ?>
                    <span class="etiqueta etiqueta-alerta">Por calificar</span>
                <?php } else { ?>
                    <span class="nota"><?php echo $a["calificacion"]; ?><small>/ 100</small></span>
                <?php } ?>
            </div>

            <?php if ($a["entrega_id"]) { ?>
                <div style="margin-top: 16px">
                    <?php if ($a["texto"]) { ?>
                        <p><?php echo nl2br(htmlspecialchars($a["texto"])); ?></p>
                    <?php } ?>
                    <?php if ($a["archivo"]) { ?>
                        <a href="subidas/<?php e($a["archivo"]); ?>" target="_blank" class="boton boton-secundario boton-chico">Ver archivo ↗</a>
                    <?php } ?>
                </div>

                <?php if ($esDocente) { ?>
                    <form method="POST" class="fila" style="margin-top: 16px; align-items: flex-end">
                        <input type="hidden" name="accion" value="calificar">
                        <input type="hidden" name="entrega_id" value="<?php echo $a["entrega_id"]; ?>">
                        <div>
                            <label class="mono texto-gris" for="cal<?php echo $a["entrega_id"]; ?>">Calificación</label><br>
                            <input type="number" id="cal<?php echo $a["entrega_id"]; ?>" name="calificacion" min="0" max="100" required
                                   value="<?php echo $a["calificacion"]; ?>" style="width: 110px">
                        </div>
                        <div style="flex: 1; min-width: 200px">
                            <label class="mono texto-gris" for="com<?php echo $a["entrega_id"]; ?>">Comentario</label><br>
                            <input type="text" id="com<?php echo $a["entrega_id"]; ?>" name="comentario" value="<?php e($a["comentario"]); ?>"
                                   placeholder="¡Buen trabajo! / Te faltó..." style="width: 100%">
                        </div>
                        <button type="submit" class="boton boton-chico"><?php echo $a["calificacion"] === null ? "Calificar" : "Actualizar"; ?></button>
                    </form>
                <?php } elseif ($a["comentario"]) { ?>
                    <p class="texto-gris">Comentario: <?php e($a["comentario"]); ?></p>
                <?php } ?>
            <?php } ?>
        </div>
    <?php } ?>
<?php } ?>

<?php include "includes/abajo.php"; ?>
