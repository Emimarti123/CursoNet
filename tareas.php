<?php
require "includes/proteger.php";

// tareas.php?curso=5 -> las tareas de un curso
// tareas.php         -> (solo alumno) todas sus tareas de todos sus cursos
$cursoId = (int) ($_GET["curso"] ?? 0);

if ($cursoId === 0) {
    permitir(["alumno"]);
    $curso     = null;
    $esDocente = false;
} else {
    [$curso, $esDocente] = accesoCurso($cursoId);
}

$aqui    = "tareas.php?curso=" . $cursoId;
$errores = [];
$nueva   = ["titulo" => "", "instrucciones" => "", "fecha_limite" => ""];

// ---------- Acciones del docente ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $esDocente) {
    if ($_POST["accion"] === "crear") {
        $nueva = [
            "titulo"        => trim($_POST["titulo"]),
            "instrucciones" => trim($_POST["instrucciones"]),
            "fecha_limite"  => trim($_POST["fecha_limite"]),
        ];
        if ($nueva["titulo"] === "") {
            $errores["titulo"] = "Escribe el título de la tarea.";
        } elseif (mb_strlen($nueva["titulo"]) > 150) {
            $errores["titulo"] = "El título puede tener máximo 150 caracteres.";
        }
        // La fecha es opcional; si viene, debe ser una fecha real (AAAA-MM-DD)
        if ($nueva["fecha_limite"] !== "" && !strtotime($nueva["fecha_limite"])) {
            $errores["fecha_limite"] = "Elige una fecha válida.";
        }

        if (count($errores) === 0) {
            consulta(
                "INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES (?, ?, ?, ?)",
                [$cursoId, $nueva["titulo"], $nueva["instrucciones"], $nueva["fecha_limite"] ?: null]
            );
            $tareaId = $conexion->insert_id;
            notificarAlumnos($cursoId, "Nueva tarea en " . $curso["titulo"] . ": " . $nueva["titulo"], "tarea.php?id=" . $tareaId);
            irA($aqui, "Tarea creada. Ya les avisamos a tus alumnos.");
        }
    }

    if ($_POST["accion"] === "borrar") {
        $tareaId = (int) $_POST["tarea_id"];
        // Primero borramos los archivos que subieron los alumnos
        $archivos = consulta(
            "SELECT e.archivo FROM entregas e JOIN tareas t ON e.tarea_id = t.id WHERE t.id = ? AND t.curso_id = ?",
            [$tareaId, $cursoId]
        );
        foreach ($archivos as $a) {
            borrarArchivo($a["archivo"]);
        }
        consulta("DELETE FROM tareas WHERE id = ? AND curso_id = ?", [$tareaId, $cursoId]);
        irA($aqui, "Tarea eliminada.");
    }
}

// ---------- Lista de tareas ----------
if ($cursoId === 0) {
    // Todas las de mis cursos, con mi entrega (si existe)
    $tareas = consulta(
        "SELECT t.*, c.titulo AS curso, e.id AS entrega, e.calificacion
         FROM tareas t
         JOIN cursos c        ON t.curso_id = c.id
         JOIN inscripciones i ON i.curso_id = c.id AND i.alumno_id = ?
         LEFT JOIN entregas e ON e.tarea_id = t.id AND e.alumno_id = ?
         ORDER BY e.id IS NOT NULL, t.fecha_limite IS NULL, t.fecha_limite, t.created_at DESC",
        [$miId, $miId]
    )->fetch_all(MYSQLI_ASSOC);
} else {
    $tareas = consulta(
        "SELECT t.*,
                (SELECT COUNT(*) FROM entregas e WHERE e.tarea_id = t.id) AS entregas,
                (SELECT COUNT(*) FROM entregas e WHERE e.tarea_id = t.id AND e.calificacion IS NULL) AS sin_calificar,
                (SELECT id FROM entregas e WHERE e.tarea_id = t.id AND e.alumno_id = ?) AS entrega,
                (SELECT calificacion FROM entregas e WHERE e.tarea_id = t.id AND e.alumno_id = ?) AS calificacion
         FROM tareas t
         WHERE t.curso_id = ?
         ORDER BY t.created_at DESC",
        [$miId, $miId, $cursoId]
    )->fetch_all(MYSQLI_ASSOC);
    $alumnosCurso = consulta("SELECT COUNT(*) AS total FROM inscripciones WHERE curso_id = ?", [$cursoId])
        ->fetch_assoc()["total"];
}

if ($cursoId === 0) {
    $menuActivo = "tareas.php";
} else {
    [$volver, $menuActivo] = volverAlCurso($cursoId);
}

$titulo = "Tareas";
include "includes/arriba.php";
?>

<?php if ($esDocente) { ?>
    <?php $pestana = "tareas"; include "includes/curso_docente.php"; ?>
    <p class="texto-gris">Deja tareas a tus alumnos y califica lo que entreguen.</p>
<?php } else { ?>
    <?php if ($curso) { ?>
        <div class="migas"><a href="<?php echo $volver; ?>">← <?php e($curso["titulo"]); ?></a></div>
    <?php } ?>
    <div class="encabezado">
        <div>
            <p class="mono texto-gris"><?php echo $curso ? "Tareas del curso" : "Todas mis tareas"; ?></p>
            <h1>Tareas</h1>
            <p class="texto-gris">Entrega tus trabajos y revisa tus calificaciones.</p>
        </div>
    </div>
<?php } ?>

<?php if ($esDocente) { ?>
    <details class="tarjeta" <?php echo count($errores) > 0 || count($tareas) === 0 ? "open" : ""; ?>>
        <summary>+ Nueva tarea</summary>
        <form method="POST" class="validar" novalidate>
            <input type="hidden" name="accion" value="crear">
            <div class="campo">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?php e($nueva["titulo"]); ?>" maxlength="150" placeholder="Ej. Crea tu primera página web" required>
                <?php errorCampo($errores, "titulo"); ?>
            </div>
            <div class="campo">
                <label for="instrucciones">Instrucciones</label>
                <textarea id="instrucciones" name="instrucciones" rows="5" placeholder="Explica qué deben hacer y cómo entregarlo"><?php e($nueva["instrucciones"]); ?></textarea>
            </div>
            <div class="campo" style="max-width: 260px">
                <label for="fecha_limite">Fecha límite (opcional)</label>
                <input type="date" id="fecha_limite" name="fecha_limite" value="<?php e($nueva["fecha_limite"]); ?>">
                <?php errorCampo($errores, "fecha_limite"); ?>
            </div>
            <button type="submit" class="boton">Crear tarea</button>
        </form>
    </details>
<?php } ?>

<div class="tarjeta">
    <?php if (count($tareas) === 0) { ?>
        <div class="vacio">
            <?php dibujoVacio(); ?>
            <p><?php echo $esDocente ? "Todavía no dejas tareas en este curso." : "No hay tareas por ahora."; ?></p>
        </div>
    <?php } else { ?>
    <div class="tabla-contenedor">
        <table class="tabla">
            <tr>
                <th>Tarea</th>
                <th>Fecha límite</th>
                <?php if ($esDocente || $rol === "super_admin" || $rol === "director") { ?>
                    <th>Entregas</th>
                <?php } else { ?>
                    <th>Estado</th>
                <?php } ?>
                <th></th>
            </tr>
            <?php foreach ($tareas as $t) { ?>
                <tr>
                    <td>
                        <a href="tarea.php?id=<?php echo $t["id"]; ?>"><strong><?php e($t["titulo"]); ?></strong></a>
                        <?php if (isset($t["curso"])) { ?>
                            <br><span class="texto-gris"><?php e($t["curso"]); ?></span>
                        <?php } ?>
                    </td>
                    <td class="texto-gris">
                        <?php echo $t["fecha_limite"] ? fecha($t["fecha_limite"]) : "Sin fecha"; ?>
                    </td>
                    <td>
                        <?php if ($rol === "alumno") { ?>
                            <?php if ($t["calificacion"] !== null) { ?>
                                <span class="etiqueta">Calificada: <?php echo $t["calificacion"]; ?></span>
                            <?php } elseif ($t["entrega"]) { ?>
                                <span class="etiqueta etiqueta-gris">Entregada</span>
                            <?php } elseif ($t["fecha_limite"] && $t["fecha_limite"] < date("Y-m-d")) { ?>
                                <span class="etiqueta etiqueta-roja">Vencida</span>
                            <?php } else { ?>
                                <span class="etiqueta etiqueta-alerta">Pendiente</span>
                            <?php } ?>
                        <?php } else { ?>
                            <?php echo $t["entregas"]; ?> de <?php echo $alumnosCurso; ?>
                            <?php if ($t["sin_calificar"] > 0) { ?>
                                <span class="etiqueta etiqueta-alerta"><?php echo $t["sin_calificar"]; ?> por calificar</span>
                            <?php } ?>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="fila" style="justify-content: flex-end">
                            <a href="tarea.php?id=<?php echo $t["id"]; ?>" class="boton boton-secundario boton-chico">
                                <?php echo $esDocente ? "Calificar" : "Abrir"; ?>
                            </a>
                            <?php if ($esDocente) { ?>
                                <form method="POST" data-confirmar="¿Eliminar esta tarea y todas sus entregas?">
                                    <input type="hidden" name="accion" value="borrar">
                                    <input type="hidden" name="tarea_id" value="<?php echo $t["id"]; ?>">
                                    <button type="submit" class="enlace-peligro">Eliminar</button>
                                </form>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php } ?>
</div>

<?php include "includes/abajo.php"; ?>
