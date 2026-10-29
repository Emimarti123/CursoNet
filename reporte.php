<?php
require "includes/proteger.php";
permitir(["super_admin", "director", "docente"]);

$cursoId = (int) ($_GET["id"] ?? 0);
$curso   = consulta("SELECT * FROM cursos WHERE id = ?", [$cursoId])->fetch_assoc();

// El docente solo puede ver el reporte de sus propios cursos
if (!$curso || ($rol === "docente" && $curso["docente_id"] != $miId)) {
    irA("panel.php", "Ese curso no existe o no tienes acceso.", "error");
}

$totalTemas = count(temasDelCurso($cursoId));
$resumen    = resumenResenas($cursoId);
$alumnos    = consulta(
    "SELECT u.id, u.nombre, u.email, u.foto, i.fecha
     FROM inscripciones i
     JOIN usuarios u ON i.alumno_id = u.id
     WHERE i.curso_id = ?
     ORDER BY u.nombre",
    [$cursoId]
)->fetch_all(MYSQLI_ASSOC);

// Para el docente, el menú marca "Mis cursos" y "volver" lleva a su curso
if ($rol === "docente") {
    $menuActivo = "docente_cursos.php";
    $volver     = "editar_curso.php?id=" . $cursoId;
} else {
    $menuActivo = "cursos.php";
    $volver     = "cursos.php";
}

$titulo = "Reporte";
include "includes/arriba.php";
?>

<?php if ($rol === "docente") { ?>
    <?php $pestana = "alumnos"; include "includes/curso_docente.php"; ?>
    <div class="fila entre" style="margin-bottom: 24px">
        <p class="texto-gris" style="margin: 0">Avance, cuestionarios y calificaciones de tus alumnos.</p>
        <a href="exportar.php?tipo=reporte&amp;id=<?php echo $cursoId; ?>" class="boton boton-secundario">Descargar en Excel</a>
    </div>
<?php } else { ?>
    <div class="migas"><a href="<?php echo $volver; ?>">← Volver</a></div>
    <div class="encabezado">
        <div>
            <p class="mono texto-gris">Reporte del curso</p>
            <h1><?php e($curso["titulo"]); ?></h1>
            <p class="texto-gris">Avance, cuestionarios y calificaciones de los alumnos.</p>
        </div>
        <div class="fila">
            <a href="tareas.php?curso=<?php echo $cursoId; ?>" class="boton boton-secundario">Tareas</a>
            <a href="foro.php?id=<?php echo $cursoId; ?>" class="boton boton-secundario">Foro</a>
            <a href="exportar.php?tipo=reporte&amp;id=<?php echo $cursoId; ?>" class="boton">Descargar en Excel</a>
        </div>
    </div>
<?php } ?>

<div class="numeros">
    <div class="numero"><strong><?php echo count($alumnos); ?></strong><span>Alumnos inscritos</span></div>
    <div class="numero"><strong><?php echo $totalTemas; ?></strong><span>Lecciones en el curso</span></div>
    <div class="numero"><strong><?php echo avancePromedio($cursoId); ?>%</strong><span>Avance promedio</span></div>
    <div class="numero"><strong><?php echo $resumen["total"] > 0 ? number_format($resumen["promedio"], 1) . " ★" : "—"; ?></strong><span>Reseñas (<?php echo $resumen["total"]; ?>)</span></div>
</div>

<div class="tarjeta">
    <?php if (count($alumnos) === 0) { ?>
        <p class="vacio">Todavía no hay alumnos inscritos.</p>
    <?php } else { ?>
    <div class="tabla-contenedor">
        <table class="tabla">
            <tr>
                <th>Alumno</th>
                <th>Inscrito desde</th>
                <th>Lecciones completadas</th>
                <th>Avance</th>
                <th>Cuestionarios</th>
                <th>Promedio tareas</th>
            </tr>
            <?php foreach ($alumnos as $a) { ?>
                <?php $porcentaje = avance($a["id"], $cursoId); ?>
                <tr>
                    <td>
                        <div class="fila">
                            <?php avatar($a["nombre"], $a["foto"]); ?>
                            <div>
                                <strong><?php e($a["nombre"]); ?></strong><br>
                                <span class="texto-gris"><?php e($a["email"]); ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="texto-gris"><?php echo fecha($a["fecha"]); ?></td>
                    <td><?php echo temasCompletados($a["id"], $cursoId); ?> de <?php echo $totalTemas; ?></td>
                    <td style="min-width: 140px">
                        <div class="fila">
                            <div class="barra-avance <?php echo $porcentaje == 100 ? "completa" : ""; ?>" style="flex: 1"><div style="width: <?php echo $porcentaje; ?>%"></div></div>
                            <span class="mono"><?php echo $porcentaje; ?>%</span>
                        </div>
                    </td>
                    <td>
                        <?php $cu = resultadoCuestionarios($a["id"], $cursoId); ?>
                        <?php if ($cu["cuestionarios"] === 0) { ?>
                            <span class="texto-gris">—</span>
                        <?php } else { ?>
                            <?php echo $cu["contestados"]; ?> de <?php echo $cu["cuestionarios"]; ?>
                            <?php if ($cu["porcentaje"] !== null) { ?>
                                <br><span class="texto-gris"><?php echo $cu["porcentaje"]; ?>% de aciertos</span>
                            <?php } ?>
                        <?php } ?>
                    </td>
                    <td>
                        <?php $prom = promedioTareas($a["id"], $cursoId); ?>
                        <?php echo $prom === null ? '<span class="texto-gris">—</span>' : "<strong>$prom</strong>"; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php } ?>
</div>

<?php
// Resultados de cada cuestionario: cuántos lo contestaron y su promedio de aciertos
$porCuestionario = consulta(
    "SELECT t.id, t.titulo, b.titulo AS modulo,
            (SELECT COUNT(*) FROM preguntas p WHERE p.tema_id = t.id) AS preguntas,
            COUNT(it.alumno_id) AS contestaron,
            ROUND(AVG(it.aciertos * 100 / it.total)) AS promedio
     FROM temas t
     JOIN bloques b ON t.bloque_id = b.id
     LEFT JOIN intentos it ON it.tema_id = t.id
     WHERE b.curso_id = ? AND EXISTS (SELECT 1 FROM preguntas p WHERE p.tema_id = t.id)
     GROUP BY t.id
     ORDER BY b.orden, b.id, t.orden, t.id",
    [$cursoId]
)->fetch_all(MYSQLI_ASSOC);
?>

<?php if (count($porCuestionario) > 0) { ?>
    <h2 class="seccion">Resultados por cuestionario</h2>
    <p class="texto-gris">Si un cuestionario tiene un promedio bajo, quizá la lección necesita más explicación.</p>
    <div class="tarjeta">
        <ul class="grafica">
            <?php foreach ($porCuestionario as $q) { ?>
                <li title="<?php e($q["titulo"]); ?>: <?php echo $q["contestaron"]; ?> alumno(s)">
                    <span class="grafica-nombre">
                        <?php if ($rol === "docente") { ?>
                            <a href="editar_tema.php?id=<?php echo $q["id"]; ?>"><?php e($q["titulo"]); ?></a>
                        <?php } else { ?>
                            <?php e($q["titulo"]); ?>
                        <?php } ?>
                        <span class="texto-gris">· <?php echo $q["preguntas"]; ?> preg. · <?php echo $q["contestaron"]; ?> de <?php echo count($alumnos); ?> lo contestaron</span>
                    </span>
                    <span class="grafica-barra"><div style="width: <?php echo (int) $q["promedio"]; ?>%"></div></span>
                    <span class="grafica-valor"><?php echo $q["promedio"] === null ? "—" : $q["promedio"] . "%"; ?></span>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<?php include "includes/abajo.php"; ?>
