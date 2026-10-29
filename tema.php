<?php
// Una LECCIÓN tal como la ve el alumno (en la base de datos, la tabla "temas").
// El docente del curso (y el admin o director) la pueden abrir en "vista previa":
// ven lo mismo, pero no pueden marcarla ni contestar el cuestionario.
require "includes/proteger.php";

$temaId = (int) ($_GET["id"] ?? 0);
$tema   = consulta(
    "SELECT t.*, b.titulo AS modulo, c.id AS curso_id, c.titulo AS curso, c.docente_id
     FROM temas t
     JOIN bloques b ON t.bloque_id = b.id
     JOIN cursos c  ON b.curso_id = c.id
     WHERE t.id = ?",
    [$temaId]
)->fetch_assoc();

// ¿Quién puede entrar?
$vistaPrevia = false;
if ($tema && $rol === "alumno") {
    $inscrito = consulta("SELECT 1 FROM inscripciones WHERE alumno_id = ? AND curso_id = ?", [$miId, $tema["curso_id"]])
        ->num_rows > 0;
    if (!$inscrito) {
        $tema = null;
    }
} elseif ($tema) {
    $vistaPrevia = true;
    if ($rol === "docente" && $tema["docente_id"] != $miId) {
        $tema = null;
    }
}

if (!$tema) {
    irA($rol === "alumno" ? "mis_cursos.php" : "panel.php", "No tienes acceso a esa lección.", "error");
}

$cursoId = $tema["curso_id"];

// Buscamos la lección anterior y la siguiente dentro del curso
$todos     = temasDelCurso($cursoId);
$anterior  = null;
$siguiente = null;
$numero    = 0;
foreach ($todos as $posicion => $t) {
    if ($t["id"] == $temaId) {
        $anterior  = $todos[$posicion - 1] ?? null;
        $siguiente = $todos[$posicion + 1] ?? null;
        $numero    = $posicion + 1;
    }
}

$secciones = consulta("SELECT * FROM secciones WHERE tema_id = ? ORDER BY orden, id", [$temaId])->fetch_all(MYSQLI_ASSOC);
$preguntas = consulta("SELECT * FROM preguntas WHERE tema_id = ? ORDER BY orden, id", [$temaId])->fetch_all(MYSQLI_ASSOC);
$elegidas  = null;   // se llena solo justo después de contestar

// ---------- Acciones del alumno ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$vistaPrevia) {
    if ($_POST["accion"] === "completar") {
        consulta("INSERT IGNORE INTO temas_completados (alumno_id, tema_id) VALUES (?, ?)", [$miId, $temaId]);

        if (avance($miId, $cursoId) == 100) {
            irA("curso.php?id=" . $cursoId, "🎉 ¡Felicidades! Terminaste el curso.");
        }
        if ($siguiente) {
            irA("tema.php?id=" . $siguiente["id"]);
        }
        irA("curso.php?id=" . $cursoId, "Lección completada.");
    }

    // Calificar el cuestionario: se compara cada respuesta con la correcta
    if ($_POST["accion"] === "cuestionario") {
        $elegidas = $_POST["respuesta"] ?? [];   // [id de pregunta => opción elegida]
        if (!is_array($elegidas)) {
            $elegidas = [];
        }
        $aciertos = 0;
        foreach ($preguntas as $p) {
            if ((int) ($elegidas[$p["id"]] ?? 0) === (int) $p["correcta"]) {
                $aciertos++;
            }
        }
        // REPLACE: guarda solo el último intento
        consulta("REPLACE INTO intentos (alumno_id, tema_id, aciertos, total) VALUES (?, ?, ?, ?)",
            [$miId, $temaId, $aciertos, count($preguntas)]);
        // No redirigimos: así podemos mostrar abajo cuáles estuvieron bien y cuáles mal
    }

    if ($_POST["accion"] === "desmarcar") {
        consulta("DELETE FROM temas_completados WHERE alumno_id = ? AND tema_id = ?", [$miId, $temaId]);
        irA("tema.php?id=" . $temaId);
    }
}

$intento = consulta("SELECT * FROM intentos WHERE alumno_id = ? AND tema_id = ?", [$miId, $temaId])->fetch_assoc();
$hecho   = consulta("SELECT 1 FROM temas_completados WHERE alumno_id = ? AND tema_id = ?", [$miId, $temaId])
    ->num_rows > 0;

$titulo     = $tema["titulo"];
$menuActivo = $rol === "docente" ? "docente_cursos.php" : ($rol === "alumno" ? "mis_cursos.php" : "cursos.php");
include "includes/arriba.php";
?>

<?php if ($vistaPrevia) { ?>
    <div class="aviso vista-previa fila entre">
        <span><strong>👁 Vista previa:</strong> así verá esta lección el alumno.</span>
        <?php if ($rol === "docente") { ?>
            <a href="editar_tema.php?id=<?php echo $temaId; ?>" class="boton boton-chico">✏️ Volver a editar</a>
        <?php } ?>
    </div>
<?php } else { ?>
    <div class="migas">
        <a href="mis_cursos.php">Mis cursos</a> ›
        <a href="curso.php?id=<?php echo $cursoId; ?>"><?php e($tema["curso"]); ?></a> ›
        <?php e($tema["modulo"]); ?>
    </div>
<?php } ?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Lección <?php echo $numero; ?> de <?php echo count($todos); ?> · <?php e($tema["modulo"]); ?></p>
        <h1><?php e($tema["titulo"]); ?></h1>
    </div>
    <?php if ($hecho) { ?>
        <span class="etiqueta">✓ Completada</span>
    <?php } ?>
</div>

<div class="tarjeta tarjeta-grande leccion">
    <?php if (count($secciones) === 0) { ?>
        <p class="texto-gris">El docente todavía no agrega contenido a esta lección.</p>
    <?php } ?>
    <?php foreach ($secciones as $s) { ?>
        <?php mostrarSeccion($s); ?>
    <?php } ?>
</div>

<?php if (count($preguntas) > 0) { ?>
    <form method="POST" class="tarjeta tarjeta-grande" id="cuestionario">
        <input type="hidden" name="accion" value="cuestionario">
        <div class="fila entre" style="margin-bottom: 24px">
            <div>
                <p class="mono texto-gris" style="margin: 0">Pon a prueba lo que aprendiste</p>
                <h2 style="margin: 0">Cuestionario final</h2>
            </div>
            <?php if ($intento) { ?>
                <span class="nota"><?php echo $intento["aciertos"]; ?><small>/ <?php echo $intento["total"]; ?> aciertos</small></span>
            <?php } ?>
        </div>

        <?php if ($elegidas !== null) { ?>
            <div class="aviso <?php echo $intento["aciertos"] === $intento["total"] ? "" : "aviso-error"; ?>">
                <?php if ($intento["aciertos"] === $intento["total"]) { ?>
                    🎉 ¡Perfecto! Contestaste todo bien.
                <?php } else { ?>
                    Tuviste <?php echo $intento["aciertos"]; ?> de <?php echo $intento["total"]; ?>. Revisa las respuestas marcadas y vuelve a intentarlo.
                <?php } ?>
            </div>
        <?php } ?>

        <?php foreach ($preguntas as $num => $p) { ?>
            <div class="pregunta">
                <fieldset <?php echo $vistaPrevia ? "disabled" : ""; ?>>
                    <legend><?php echo $num + 1; ?>. <?php e($p["pregunta"]); ?></legend>
                    <?php for ($o = 1; $o <= 4; $o++) { ?>
                        <?php if ($p["opcion$o"] === null || $p["opcion$o"] === "") continue;   // las opciones 3 y 4 son opcionales ?>
                        <?php
                        // Después de contestar: verde la correcta, rojo la que eligió mal.
                        // En vista previa se marca la correcta para que el docente la revise.
                        $clase   = "";
                        $elegida = (int) ($elegidas[$p["id"]] ?? 0);
                        if (($elegidas !== null || $vistaPrevia) && $o === (int) $p["correcta"]) {
                            $clase = "correcta";
                        } elseif ($elegidas !== null && $o === $elegida) {
                            $clase = "incorrecta";
                        }
                        ?>
                        <label class="opcion <?php echo $clase; ?>">
                            <input type="radio" name="respuesta[<?php echo $p["id"]; ?>]" value="<?php echo $o; ?>"
                                <?php echo $elegida === $o ? "checked" : ""; ?> required>
                            <?php e($p["opcion$o"]); ?>
                        </label>
                    <?php } ?>
                </fieldset>
            </div>
        <?php } ?>

        <?php if (!$vistaPrevia) { ?>
            <button type="submit" class="boton" style="margin-top: 16px">
                <?php echo $intento ? "Volver a intentar" : "Revisar mis respuestas"; ?>
            </button>
        <?php } else { ?>
            <p class="texto-gris" style="margin-bottom: 0">En la vista previa la respuesta correcta aparece marcada.</p>
        <?php } ?>
    </form>
<?php } ?>

<div class="fila entre">
    <div>
        <?php if ($anterior) { ?>
            <a href="tema.php?id=<?php echo $anterior["id"]; ?>">← Lección anterior</a>
        <?php } ?>
    </div>

    <div class="fila">
        <?php if ($vistaPrevia) { ?>
            <?php if ($siguiente) { ?>
                <a href="tema.php?id=<?php echo $siguiente["id"]; ?>">Lección siguiente →</a>
            <?php } ?>
        <?php } elseif ($hecho) { ?>
            <form method="POST">
                <input type="hidden" name="accion" value="desmarcar">
                <button type="submit" class="enlace-peligro">Desmarcar</button>
            </form>
            <?php if ($siguiente) { ?>
                <a href="tema.php?id=<?php echo $siguiente["id"]; ?>" class="boton">Siguiente lección →</a>
            <?php } else { ?>
                <a href="curso.php?id=<?php echo $cursoId; ?>" class="boton">Volver al curso</a>
            <?php } ?>
        <?php } else { ?>
            <form method="POST">
                <input type="hidden" name="accion" value="completar">
                <button type="submit" class="boton">
                    <?php echo $siguiente ? "Marcar como completada y continuar →" : "Marcar como completada"; ?>
                </button>
            </form>
        <?php } ?>
    </div>
</div>

<?php include "includes/abajo.php"; ?>
