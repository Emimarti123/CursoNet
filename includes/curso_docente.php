<?php
// Encabezado de un curso para su DOCENTE: título, estado, botón de publicar y pestañas.
// Lo usan editar_curso.php, tareas.php, avisos.php, reporte.php y foro.php.
// Antes de incluirlo, cada página define:
//   $curso   -> el curso (fila de la tabla cursos)
//   $pestana -> la pestaña marcada: "contenido", "tareas", "avisos", "alumnos", "foro" o "ajustes"

$idCurso = $curso["id"];

// Numeritos de las pestañas
$porCalificarCurso = consulta(
    "SELECT COUNT(*) AS total FROM entregas e JOIN tareas t ON e.tarea_id = t.id
     WHERE t.curso_id = ? AND e.calificacion IS NULL",
    [$idCurso]
)->fetch_assoc()["total"];
$alumnosCurso = consulta("SELECT COUNT(*) AS total FROM inscripciones WHERE curso_id = ?", [$idCurso])
    ->fetch_assoc()["total"];

$pestanas = [
    "contenido" => ["editar_curso.php?id=$idCurso", "Contenido", 0],
    "tareas"    => ["tareas.php?curso=$idCurso", "Tareas", $porCalificarCurso],
    "avisos"    => ["avisos.php?curso=$idCurso", "Avisos", 0],
    "alumnos"   => ["reporte.php?id=$idCurso", "Alumnos ($alumnosCurso)", 0],
    "foro"      => ["foro.php?id=$idCurso", "Foro", 0],
    "ajustes"   => ["editar_curso.php?id=$idCurso&vista=ajustes", "Ajustes", 0],
];
?>
<div class="migas"><a href="docente_cursos.php">Mis cursos</a> › <?php e($curso["titulo"]); ?></div>

<div class="encabezado" style="margin-bottom: 24px">
    <div>
        <p class="mono texto-gris">
            Curso ·
            <?php if ($curso["estado"] === "publicado") { ?>
                <span class="etiqueta">Publicado</span>
            <?php } else { ?>
                <span class="etiqueta etiqueta-gris">Borrador</span>
            <?php } ?>
        </p>
        <h1><?php e($curso["titulo"]); ?></h1>
    </div>
    <form method="POST" action="editar_curso.php?id=<?php echo $idCurso; ?>"
        <?php if ($curso["estado"] === "publicado") { ?>
            data-confirmar="¿Regresar el curso a borrador?"
            data-detalle="Ya no aparecerá en el catálogo. Los alumnos inscritos (<?php echo $alumnosCurso; ?>) podrán seguir entrando."
        <?php } ?>>
        <input type="hidden" name="accion" value="estado">
        <button type="submit" class="boton <?php echo $curso["estado"] === "publicado" ? "boton-secundario" : ""; ?>">
            <?php echo $curso["estado"] === "publicado" ? "Pasar a borrador" : "Publicar curso"; ?>
        </button>
    </form>
</div>

<nav class="pestanas" aria-label="Secciones del curso">
    <?php foreach ($pestanas as $clave => [$enlace, $texto, $numero]) { ?>
        <a href="<?php echo $enlace; ?>" class="<?php echo $clave === $pestana ? "activa" : ""; ?>"
           <?php echo $clave === $pestana ? 'aria-current="page"' : ""; ?>>
            <?php echo $texto; ?>
            <?php if ($numero > 0) { ?><span class="contador"><?php echo $numero; ?></span><?php } ?>
        </a>
    <?php } ?>
</nav>
