<?php
require "includes/proteger.php";
permitir(["alumno"]);

// El curso solo se puede ver si el alumno está inscrito
$cursoId = (int) ($_GET["id"] ?? 0);
$curso   = consulta(
    "SELECT c.*, u.nombre AS docente, cat.nombre AS categoria
     FROM cursos c
     JOIN usuarios u          ON c.docente_id = u.id
     JOIN inscripciones i     ON i.curso_id = c.id AND i.alumno_id = ?
     LEFT JOIN categorias cat ON c.categoria_id = cat.id
     WHERE c.id = ?",
    [$miId, $cursoId]
)->fetch_assoc();

if (!$curso) {
    irA("catalogo.php", "Primero inscríbete a ese curso.", "error");
}

$aqui    = "curso.php?id=" . $cursoId;
$errores = [];

// ---------- Guardar o borrar mi reseña ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["accion"] === "resena") {
        $estrellas  = (int) ($_POST["estrellas"] ?? 0);
        $comentario = trim($_POST["comentario"]);

        if ($estrellas < 1 || $estrellas > 5) {
            $errores["estrellas"] = "Elige de 1 a 5 estrellas.";
        }
        if (mb_strlen($comentario) > 1000) {
            $errores["comentario"] = "El comentario puede tener máximo 1000 caracteres.";
        }
        if (count($errores) === 0) {
            // REPLACE: si ya tenía reseña la reemplaza; si no, la crea
            consulta("REPLACE INTO resenas (alumno_id, curso_id, estrellas, comentario) VALUES (?, ?, ?, ?)",
                [$miId, $cursoId, $estrellas, $comentario]);
            notificar($curso["docente_id"], $_SESSION["nombre"] . " calificó " . $curso["titulo"] . " con $estrellas estrella(s).",
                "reporte.php?id=" . $cursoId);
            irA($aqui . "#resenas", "¡Gracias por tu reseña!");
        }
    }

    if ($_POST["accion"] === "borrar_resena") {
        consulta("DELETE FROM resenas WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId]);
        irA($aqui . "#resenas", "Reseña eliminada.");
    }
}

// Lista de ids de los temas que ya completó este alumno
$hechos = array_column(
    consulta("SELECT tema_id FROM temas_completados WHERE alumno_id = ?", [$miId])->fetch_all(MYSQLI_ASSOC),
    "tema_id"
);

// El siguiente tema por hacer = el primero que no esté en $hechos
$todos     = temasDelCurso($cursoId);
$siguiente = null;
foreach ($todos as $t) {
    if (!in_array($t["id"], $hechos)) {
        $siguiente = $t;
        break;
    }
}

$completados = temasCompletados($miId, $cursoId);
$porcentaje  = avance($miId, $cursoId);
$bloques     = consulta("SELECT * FROM bloques WHERE curso_id = ? ORDER BY orden, id", [$cursoId])
    ->fetch_all(MYSQLI_ASSOC);

// Cuántas preguntas tiene cada tema y mi último resultado: [tema_id => [...]]
$cuestionarios = [];
foreach (consulta(
    "SELECT p.tema_id, COUNT(*) AS preguntas, it.aciertos, it.total
     FROM preguntas p
     JOIN temas t   ON p.tema_id = t.id
     JOIN bloques b ON t.bloque_id = b.id
     LEFT JOIN intentos it ON it.tema_id = p.tema_id AND it.alumno_id = ?
     WHERE b.curso_id = ?
     GROUP BY p.tema_id, it.aciertos, it.total",
    [$miId, $cursoId]
) as $fila) {
    $cuestionarios[$fila["tema_id"]] = $fila;
}

// Último aviso del docente y número de tareas pendientes
$ultimoAviso = consulta("SELECT * FROM avisos WHERE curso_id = ? ORDER BY fecha DESC LIMIT 1", [$cursoId])->fetch_assoc();
$tareasPendientes = consulta(
    "SELECT COUNT(*) AS total FROM tareas t
     LEFT JOIN entregas e ON e.tarea_id = t.id AND e.alumno_id = ?
     WHERE t.curso_id = ? AND e.id IS NULL",
    [$miId, $cursoId]
)->fetch_assoc()["total"];

// Reseñas
$resumen  = resumenResenas($cursoId);
$miResena = consulta("SELECT * FROM resenas WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId])->fetch_assoc();
$resenas  = consulta(
    "SELECT r.*, u.nombre, u.foto FROM resenas r JOIN usuarios u ON r.alumno_id = u.id
     WHERE r.curso_id = ? ORDER BY r.fecha DESC",
    [$cursoId]
)->fetch_all(MYSQLI_ASSOC);

// Si hubo error al enviar la reseña, mostramos lo que escribió
$formResena = [
    "estrellas"  => (int) ($_POST["estrellas"] ?? $miResena["estrellas"] ?? 0),
    "comentario" => $_POST["comentario"] ?? $miResena["comentario"] ?? "",
];

$titulo     = $curso["titulo"];
$menuActivo = "mis_cursos.php";
include "includes/arriba.php";
?>

<section class="banda">
    <div class="migas"><a href="mis_cursos.php">Mis cursos</a> › <?php e($curso["titulo"]); ?></div>
    <?php if ($curso["categoria"]) { ?>
        <span class="etiqueta"><?php e($curso["categoria"]); ?></span>
    <?php } ?>
    <h1 style="margin-top: 16px"><?php e($curso["titulo"]); ?></h1>
    <p>Docente: <?php e($curso["docente"]); ?>
        <?php if ($resumen["total"] > 0) { ?>
            · <?php echo number_format($resumen["promedio"], 1); ?> ★ (<?php echo $resumen["total"]; ?> reseñas)
        <?php } ?>
    </p>

    <div style="max-width: 560px; margin: 40px 0 24px">
        <div class="fila entre">
            <span><?php echo $completados; ?> de <?php echo count($todos); ?> lecciones completadas</span>
            <strong><?php echo $porcentaje; ?>%</strong>
        </div>
        <div class="barra-avance" style="margin-top: 8px"><div style="width: <?php echo $porcentaje; ?>%"></div></div>
    </div>

    <div class="fila">
        <?php if ($siguiente) { ?>
            <a href="tema.php?id=<?php echo $siguiente["id"]; ?>" class="boton">
                <?php echo $completados === 0 ? "Empezar" : "Continuar"; ?>: <?php e(mb_strimwidth($siguiente["titulo"], 0, 40, "…")); ?>
            </a>
        <?php } elseif (count($todos) > 0) { ?>
            <a href="constancia.php?curso=<?php echo $cursoId; ?>" class="boton">🎓 Ver mi constancia</a>
        <?php } ?>
        <a href="tareas.php?curso=<?php echo $cursoId; ?>" class="boton boton-secundario">
            Tareas<?php echo $tareasPendientes > 0 ? " ($tareasPendientes)" : ""; ?>
        </a>
        <a href="avisos.php?curso=<?php echo $cursoId; ?>" class="boton boton-secundario">Avisos</a>
        <a href="foro.php?id=<?php echo $cursoId; ?>" class="boton boton-secundario">Foro</a>
    </div>

    <svg class="banda-dibujo" viewBox="0 0 220 160" aria-hidden="true">
        <rect x="40" y="20" width="120" height="120" fill="none" stroke="#fffcf7" stroke-width="1.5"/>
        <circle cx="100" cy="80" r="40" fill="#fae59b" stroke="#fffcf7" stroke-width="1.5"/>
        <line x1="10" y1="140" x2="210" y2="140" stroke="#fffcf7" stroke-width="1.5"/>
        <circle cx="190" cy="30" r="5" fill="#fae59b"/>
        <circle cx="20" cy="60" r="4" fill="#fffcf7"/>
    </svg>
</section>

<?php if ($curso["descripcion"]) { ?>
    <p class="lectura" style="margin-top: 0"><?php echo nl2br(htmlspecialchars($curso["descripcion"])); ?></p>
<?php } ?>

<?php if ($ultimoAviso) { ?>
    <div class="tarjeta" style="border-left: 6px solid var(--amarillo)">
        <div class="fila entre">
            <span class="mono texto-gris">Último aviso · <?php echo fecha($ultimoAviso["fecha"]); ?></span>
            <a href="avisos.php?curso=<?php echo $cursoId; ?>">Ver todos</a>
        </div>
        <h3 style="margin: 8px 0 4px"><?php e($ultimoAviso["titulo"]); ?></h3>
        <p style="margin: 0"><?php echo nl2br(htmlspecialchars($ultimoAviso["mensaje"])); ?></p>
    </div>
<?php } ?>

<h2 class="seccion">Contenido del curso</h2>

<?php if (count($bloques) === 0) { ?>
    <div class="tarjeta vacio">El docente todavía no agrega contenido.</div>
<?php } ?>

<?php foreach ($bloques as $numBloque => $bloque) { ?>
    <?php
    $temas = consulta("SELECT id, titulo FROM temas WHERE bloque_id = ? ORDER BY orden, id", [$bloque["id"]])
        ->fetch_all(MYSQLI_ASSOC);

    // Contamos cuántos temas de este bloque ya están hechos
    $hechosBloque = 0;
    foreach ($temas as $t) {
        if (in_array($t["id"], $hechos)) {
            $hechosBloque++;
        }
    }
    // Solo dejamos abierto el bloque donde está el siguiente tema
    $abierto = $siguiente && $siguiente["bloque_id"] == $bloque["id"];
    ?>
    <details class="tarjeta" <?php echo $abierto ? "open" : ""; ?>>
        <summary>
            Módulo <?php echo $numBloque + 1; ?>: <?php e($bloque["titulo"]); ?>
            <span class="mono texto-gris" style="font-weight: 400; margin-left: 8px"><?php echo $hechosBloque; ?>/<?php echo count($temas); ?></span>
        </summary>
        <ul class="lista-temas">
            <?php foreach ($temas as $numTema => $t) { ?>
                <?php $hecho = in_array($t["id"], $hechos); ?>
                <li>
                    <span class="marca <?php echo $hecho ? "hecha" : ""; ?>"><?php echo $hecho ? "✓" : ""; ?></span>
                    <a href="tema.php?id=<?php echo $t["id"]; ?>">
                        <?php echo ($numBloque + 1) . "." . ($numTema + 1); ?> <?php e($t["titulo"]); ?>
                    </a>
                    <?php if (isset($cuestionarios[$t["id"]])) { ?>
                        <?php $cu = $cuestionarios[$t["id"]]; ?>
                        <?php if ($cu["total"]) { ?>
                            <span class="etiqueta">Cuestionario <?php echo $cu["aciertos"]; ?>/<?php echo $cu["total"]; ?></span>
                        <?php } else { ?>
                            <span class="etiqueta etiqueta-alerta">Cuestionario</span>
                        <?php } ?>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    </details>
<?php } ?>

<!-- ===================== Reseñas ===================== -->
<h2 class="seccion" id="resenas">Reseñas</h2>

<div class="rejilla rejilla-2">
    <form method="POST" class="tarjeta" novalidate>
        <input type="hidden" name="accion" value="resena">
        <h3><?php echo $miResena ? "Tu reseña" : "¿Qué te pareció este curso?"; ?></h3>
        <div class="campo">
            <!-- Las estrellas van al revés en el HTML para que el CSS pueda pintar "hasta aquí" -->
            <div class="elegir-estrellas" role="radiogroup" aria-label="Calificación">
                <?php for ($i = 5; $i >= 1; $i--) { ?>
                    <input type="radio" id="estrella<?php echo $i; ?>" name="estrellas" value="<?php echo $i; ?>" <?php echo $formResena["estrellas"] === $i ? "checked" : ""; ?>>
                    <label for="estrella<?php echo $i; ?>" title="<?php echo $i; ?> estrella(s)">★</label>
                <?php } ?>
            </div>
            <?php errorCampo($errores, "estrellas"); ?>
        </div>
        <div class="campo">
            <label for="comentario">Comentario (opcional)</label>
            <textarea id="comentario" name="comentario" rows="3" maxlength="1000" placeholder="Cuéntales a otros alumnos tu experiencia"><?php e($formResena["comentario"]); ?></textarea>
            <?php errorCampo($errores, "comentario"); ?>
        </div>
        <div class="fila entre">
            <button type="submit" class="boton"><?php echo $miResena ? "Actualizar reseña" : "Publicar reseña"; ?></button>
        </div>
    </form>

    <div class="tarjeta">
        <?php if ($resumen["total"] > 0) { ?>
            <div class="fila" style="margin-bottom: 16px">
                <span class="nota"><?php echo number_format($resumen["promedio"], 1); ?></span>
                <div>
                    <?php estrellas($resumen["promedio"]); ?><br>
                    <span class="texto-gris"><?php echo $resumen["total"]; ?> reseña(s)</span>
                </div>
            </div>
        <?php } else { ?>
            <p class="texto-gris">Todavía nadie deja reseña. ¡Sé el primero!</p>
        <?php } ?>

        <?php foreach ($resenas as $r) { ?>
            <div class="mensaje">
                <?php avatar($r["nombre"], $r["foto"]); ?>
                <div style="flex: 1">
                    <div class="fila entre">
                        <strong><?php e($r["nombre"]); ?></strong>
                        <?php estrellas($r["estrellas"]); ?>
                    </div>
                    <?php if ($r["comentario"]) { ?>
                        <p><?php echo nl2br(htmlspecialchars($r["comentario"])); ?></p>
                    <?php } ?>
                    <?php if ($r["alumno_id"] == $miId) { ?>
                        <form method="POST" data-confirmar="¿Eliminar tu reseña?" style="margin-top: 8px">
                            <input type="hidden" name="accion" value="borrar_resena">
                            <button type="submit" class="enlace-peligro">Eliminar mi reseña</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php include "includes/abajo.php"; ?>
