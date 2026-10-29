<?php
// Editor de una LECCIÓN (en la base de datos, la tabla "temas").
// En una sola página: 1) título, 2) secciones del contenido, 3) cuestionario final.
require "includes/proteger.php";
permitir(["docente"]);

// Buscamos la lección con su módulo y su curso, revisando que el curso sea de este docente
$temaId = (int) ($_GET["id"] ?? 0);
$tema   = consulta(
    "SELECT t.*, b.titulo AS modulo, c.id AS curso_id, c.titulo AS curso
     FROM temas t
     JOIN bloques b ON t.bloque_id = b.id
     JOIN cursos c  ON b.curso_id = c.id
     WHERE t.id = ? AND c.docente_id = ?",
    [$temaId, $miId]
)->fetch_assoc();

if (!$tema) {
    irA("docente_cursos.php", "Esa lección no existe o no es tuya.", "error");
}

$aqui = "editar_tema.php?id=" . $temaId;

// Tipos de imagen que se pueden subir en una sección
$imagenesPermitidas = ["jpg", "jpeg", "png", "webp", "gif"];

// Cuántos alumnos ya contestaron el cuestionario (para advertir antes de cambiarlo)
$contestaron = consulta("SELECT COUNT(*) AS total FROM intentos WHERE tema_id = ?", [$temaId])->fetch_assoc()["total"];

// Si un formulario tiene errores, aquí guardamos qué escribió para volver a mostrarlo
$errorTitulo    = "";
$nuevaTipo      = $_GET["nueva"] ?? "";          // tipo de sección que se está agregando
$formSeccion    = ["id" => null, "valores" => [], "errores" => []];
$formPregunta   = ["id" => null, "valores" => [], "errores" => []];

// ---------- Revisa los datos de una sección ----------
// Regresa [errores, contenido final]. $anterior es la sección que se está editando (o null si es nueva).
function revisarSeccion($tipo, $titulo, $contenido, $anterior)
{
    global $imagenesPermitidas;
    $errores = [];

    if (mb_strlen($titulo) > 150) {
        $errores["titulo"] = "Máximo 150 caracteres.";
    }

    if (in_array($tipo, ["texto", "codigo", "nota"])) {
        if (trim($contenido) === "") {
            $errores["contenido"] = "Escribe el contenido de la sección.";
        } elseif (mb_strlen($contenido) > 20000) {
            $errores["contenido"] = "Es demasiado largo (máximo 20,000 caracteres). Divídelo en varias secciones.";
        }
    }

    if ($tipo === "video" && !idYoutube($contenido)) {
        $errores["contenido"] = "Pega la dirección de un video de YouTube (ej. https://www.youtube.com/watch?v=...).";
    }

    if ($tipo === "enlace") {
        if ($titulo === "") {
            $errores["titulo"] = "Escribe el texto que verá el alumno (ej. Documentación oficial).";
        }
        if (!preg_match("#^https?://#", $contenido) || mb_strlen($contenido) > 2000) {
            $errores["contenido"] = "La dirección debe empezar con http:// o https://";
        }
    }

    if ($tipo === "imagen") {
        $contenido = $anterior["contenido"] ?? "";        // si no sube otra, se queda la de antes
        $subida = subirArchivo("imagen", "lecciones", $imagenesPermitidas);
        if ($subida["ruta"] !== "" && !getimagesize(__DIR__ . "/subidas/" . $subida["ruta"])) {
            borrarArchivo($subida["ruta"]);
            $subida = ["ruta" => "", "error" => "Ese archivo no es una imagen válida."];
        }
        if ($subida["error"] !== "") {
            $errores["imagen"] = $subida["error"];
        } elseif ($subida["ruta"] !== "") {
            if (count($errores) > 0) {
                borrarArchivo($subida["ruta"]);           // hay otro error: no dejamos el archivo tirado
            } else {
                $contenido = $subida["ruta"];
            }
        } elseif ($contenido === "") {
            $errores["imagen"] = "Elige una imagen.";
        }
    }

    return [$errores, $contenido];
}

// ---------- Lee el formulario de una pregunta ----------
// Junta las opciones que tienen texto (de 2 a 4) y averigua cuál es la correcta.
function leerPregunta()
{
    $valores  = ["pregunta" => trim($_POST["pregunta"]), "correcta" => (int) ($_POST["correcta"] ?? 0)];
    $opciones = [];
    $correcta = 0;
    $errores  = [];

    for ($o = 1; $o <= 4; $o++) {
        $valores["opcion$o"] = trim($_POST["opcion$o"] ?? "");
        if ($valores["opcion$o"] !== "") {
            $opciones[] = $valores["opcion$o"];
            if ($valores["correcta"] === $o) {
                $correcta = count($opciones);    // su posición ya sin los huecos
            }
            if (mb_strlen($valores["opcion$o"]) > 150) {
                $errores["opcion$o"] = "Máximo 150 caracteres.";
            }
        }
    }

    if ($valores["pregunta"] === "" || mb_strlen($valores["pregunta"]) > 255) {
        $errores["pregunta"] = "Escribe la pregunta (máximo 255 caracteres).";
    }
    if (count($opciones) < 2) {
        $errores["opciones"] = "Escribe al menos 2 opciones.";
    } elseif ($correcta === 0) {
        $errores["opciones"] = "Marca con el circulito cuál opción es la correcta.";
    }

    // Rellenamos hasta 4 con null (las opciones 3 y 4 son opcionales)
    $opciones = array_pad($opciones, 4, null);
    return [$valores, $opciones, $correcta, $errores];
}

// ===================== Acciones =====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"];

    // ----- 1. Título -----
    if ($accion === "titulo") {
        $nuevoTitulo = trim($_POST["titulo"]);
        if ($nuevoTitulo === "" || mb_strlen($nuevoTitulo) > 150) {
            $errorTitulo   = "El título debe tener entre 1 y 150 caracteres.";
            $tema["titulo"] = $nuevoTitulo;
        } else {
            consulta("UPDATE temas SET titulo = ? WHERE id = ?", [$nuevoTitulo, $temaId]);
            irA($aqui, "Título guardado.");
        }
    }

    // ----- 2. Secciones -----
    if ($accion === "nueva_seccion" && isset($tiposSeccion[$_POST["tipo"]])) {
        $tipo      = $_POST["tipo"];
        $tituloS   = trim($_POST["titulo"] ?? "");
        $contenido = $tipo === "codigo" ? rtrim($_POST["contenido"] ?? "") : trim($_POST["contenido"] ?? "");
        [$errores, $contenido] = revisarSeccion($tipo, $tituloS, $contenido, null);

        if (count($errores) === 0) {
            consulta("INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES (?, ?, ?, ?, ?)",
                [$temaId, $tipo, $tituloS ?: null, $contenido, siguienteOrden("secciones", "tema_id", $temaId)]);
            irA($aqui . "#seccion" . $conexion->insert_id, "Sección agregada.");
        }
        $nuevaTipo   = $tipo;
        $formSeccion = ["id" => "nueva", "valores" => ["titulo" => $tituloS, "contenido" => $_POST["contenido"] ?? ""], "errores" => $errores];
    }

    if ($accion === "editar_seccion") {
        $seccion = consulta("SELECT * FROM secciones WHERE id = ? AND tema_id = ?", [(int) $_POST["seccion_id"], $temaId])->fetch_assoc();
        if ($seccion) {
            $tituloS   = trim($_POST["titulo"] ?? "");
            $contenido = $seccion["tipo"] === "codigo" ? rtrim($_POST["contenido"] ?? "") : trim($_POST["contenido"] ?? "");
            [$errores, $contenido] = revisarSeccion($seccion["tipo"], $tituloS, $contenido, $seccion);

            if (count($errores) === 0) {
                if ($seccion["tipo"] === "imagen" && $contenido !== $seccion["contenido"]) {
                    borrarArchivo($seccion["contenido"]);   // reemplazó la imagen: borramos la vieja
                }
                consulta("UPDATE secciones SET titulo = ?, contenido = ? WHERE id = ?", [$tituloS ?: null, $contenido, $seccion["id"]]);
                irA($aqui . "#seccion" . $seccion["id"], "Sección guardada.");
            }
            $formSeccion = ["id" => $seccion["id"], "valores" => ["titulo" => $tituloS, "contenido" => $_POST["contenido"] ?? ""], "errores" => $errores];
        }
    }

    if ($accion === "mover_seccion") {
        mover("secciones", $temaId, (int) $_POST["seccion_id"], $_POST["direccion"]);
        irA($aqui . "#seccion" . (int) $_POST["seccion_id"]);
    }

    if ($accion === "borrar_seccion") {
        $seccion = consulta("SELECT * FROM secciones WHERE id = ? AND tema_id = ?", [(int) $_POST["seccion_id"], $temaId])->fetch_assoc();
        if ($seccion) {
            if ($seccion["tipo"] === "imagen") {
                borrarArchivo($seccion["contenido"]);
            }
            consulta("DELETE FROM secciones WHERE id = ?", [$seccion["id"]]);
        }
        irA($aqui . "#contenido", "Sección eliminada.");
    }

    // ----- 3. Cuestionario -----
    // Si cambian las preguntas o la respuesta correcta, los resultados viejos ya no sirven
    $reiniciado = $contestaron > 0 ? " Se reiniciaron los resultados de $contestaron alumno(s)." : "";

    if ($accion === "nueva_pregunta") {
        [$valores, $op, $correcta, $errores] = leerPregunta();
        if (count($errores) === 0) {
            consulta(
                "INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, opcion4, correcta, orden) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$temaId, $valores["pregunta"], $op[0], $op[1], $op[2], $op[3], $correcta, siguienteOrden("preguntas", "tema_id", $temaId)]
            );
            consulta("DELETE FROM intentos WHERE tema_id = ?", [$temaId]);
            irA($aqui . "#nueva-pregunta", "Pregunta agregada. Puedes seguir agregando más." . $reiniciado);
        }
        $formPregunta = ["id" => "nueva", "valores" => $valores, "errores" => $errores];
    }

    if ($accion === "editar_pregunta") {
        $vieja = consulta("SELECT * FROM preguntas WHERE id = ? AND tema_id = ?", [(int) $_POST["pregunta_id"], $temaId])->fetch_assoc();
        if ($vieja) {
            [$valores, $op, $correcta, $errores] = leerPregunta();
            if (count($errores) === 0) {
                consulta(
                    "UPDATE preguntas SET pregunta = ?, opcion1 = ?, opcion2 = ?, opcion3 = ?, opcion4 = ?, correcta = ? WHERE id = ?",
                    [$valores["pregunta"], $op[0], $op[1], $op[2], $op[3], $correcta, $vieja["id"]]
                );
                // Solo reiniciamos si cambió la respuesta correcta o el número de opciones;
                // corregir una falta de ortografía no afecta los resultados
                $antes = count(array_filter([$vieja["opcion1"], $vieja["opcion2"], $vieja["opcion3"], $vieja["opcion4"]]));
                $ahora = count(array_filter($op));
                if ((int) $vieja["correcta"] !== $correcta || $antes !== $ahora) {
                    consulta("DELETE FROM intentos WHERE tema_id = ?", [$temaId]);
                    irA($aqui . "#pregunta" . $vieja["id"], "Pregunta guardada." . $reiniciado);
                }
                irA($aqui . "#pregunta" . $vieja["id"], "Pregunta guardada.");
            }
            $formPregunta = ["id" => $vieja["id"], "valores" => $valores, "errores" => $errores];
        }
    }

    if ($accion === "mover_pregunta") {
        mover("preguntas", $temaId, (int) $_POST["pregunta_id"], $_POST["direccion"]);
        irA($aqui . "#pregunta" . (int) $_POST["pregunta_id"]);
    }

    if ($accion === "borrar_pregunta") {
        consulta("DELETE FROM preguntas WHERE id = ? AND tema_id = ?", [(int) $_POST["pregunta_id"], $temaId]);
        consulta("DELETE FROM intentos WHERE tema_id = ?", [$temaId]);
        irA($aqui . "#cuestionario", "Pregunta eliminada." . $reiniciado);
    }
}

// ===================== Datos para mostrar =====================
$secciones = consulta("SELECT * FROM secciones WHERE tema_id = ? ORDER BY orden, id", [$temaId])->fetch_all(MYSQLI_ASSOC);
$preguntas = consulta("SELECT * FROM preguntas WHERE tema_id = ? ORDER BY orden, id", [$temaId])->fetch_all(MYSQLI_ASSOC);
if (!isset($tiposSeccion[$nuevaTipo])) {
    $nuevaTipo = "";
}

// Lección anterior y siguiente del curso (para moverse sin regresar)
$todas = temasDelCurso($tema["curso_id"]);
$anterior = $siguiente = null;
foreach ($todas as $posicion => $t) {
    if ($t["id"] == $temaId) {
        $anterior  = $todas[$posicion - 1] ?? null;
        $siguiente = $todas[$posicion + 1] ?? null;
    }
}

// ---------- Campos del formulario de una sección (según su tipo) ----------
function camposSeccion($tipo, $valores, $errores, $actual = null)
{
    global $imagenesPermitidas;
    $titulo    = $valores["titulo"] ?? "";
    $contenido = $valores["contenido"] ?? "";

    // Etiqueta y ejemplo del campo "título" según el tipo
    $etiquetas = [
        "texto"  => ["Subtítulo (opcional)", "Ej. ¿Dónde se usa?"],
        "codigo" => ["Lenguaje o nombre (opcional)", "Ej. Python"],
        "nota"   => ["Título de la nota (opcional)", "Importante"],
        "video"  => ["Descripción del video (opcional)", "Ej. Explicación paso a paso"],
        "imagen" => ["Pie de imagen (opcional)", "Describe la imagen para quien no pueda verla"],
        "enlace" => ["Texto del enlace", "Ej. Documentación oficial"],
    ];
    [$etiqueta, $ejemplo] = $etiquetas[$tipo];
    ?>
    <div class="campo">
        <label><?php echo $etiqueta; ?></label>
        <input type="text" name="titulo" value="<?php e($titulo); ?>" maxlength="150" placeholder="<?php e($ejemplo); ?>">
        <?php errorCampo($errores, "titulo"); ?>
    </div>

    <?php if ($tipo === "texto" || $tipo === "nota") { ?>
        <div class="campo">
            <label><?php echo $tipo === "nota" ? "Mensaje" : "Texto"; ?></label>
            <textarea name="contenido" rows="<?php echo $tipo === "nota" ? 3 : 8; ?>" placeholder="<?php echo $tipo === "nota" ? "Algo que el alumno no debe olvidar." : "Escribe la explicación. Puedes usar varios párrafos y listas con guiones (-)."; ?>"><?php e($contenido); ?></textarea>
            <?php errorCampo($errores, "contenido"); ?>
        </div>
    <?php } elseif ($tipo === "codigo") { ?>
        <div class="campo">
            <label>Código</label>
            <textarea name="contenido" rows="8" class="area-codigo" spellcheck="false" placeholder="print(&quot;Hola&quot;)"><?php e($contenido); ?></textarea>
            <small>Se respetan los espacios. La tecla Tab agrega 4 espacios.</small>
            <?php errorCampo($errores, "contenido"); ?>
        </div>
    <?php } elseif ($tipo === "video") { ?>
        <div class="campo">
            <label>Dirección del video de YouTube</label>
            <input type="url" name="contenido" value="<?php e($contenido); ?>" placeholder="https://www.youtube.com/watch?v=...">
            <small>Copia la dirección desde YouTube (botón Compartir). El video se verá dentro de la lección.</small>
            <?php errorCampo($errores, "contenido"); ?>
        </div>
    <?php } elseif ($tipo === "enlace") { ?>
        <div class="campo">
            <label>Dirección (URL)</label>
            <input type="url" name="contenido" value="<?php e($contenido); ?>" placeholder="https://...">
            <small>Una página web, un PDF o un documento compartido. Se abre en otra pestaña.</small>
            <?php errorCampo($errores, "contenido"); ?>
        </div>
    <?php } elseif ($tipo === "imagen") { ?>
        <div class="campo">
            <label><?php echo $actual ? "Cambiar imagen (opcional)" : "Imagen"; ?></label>
            <input type="file" name="imagen" accept=".<?php echo implode(",.", $imagenesPermitidas); ?>">
            <small>JPG, PNG, WEBP o GIF. Máximo 2 MB.</small>
            <?php errorCampo($errores, "imagen"); ?>
        </div>
    <?php } ?>
    <?php
}

// ---------- Campos del formulario de una pregunta ----------
function camposPregunta($valores, $errores, $sufijo)
{
    ?>
    <div class="campo">
        <label for="pregunta<?php echo $sufijo; ?>">Pregunta</label>
        <input type="text" id="pregunta<?php echo $sufijo; ?>" name="pregunta" value="<?php e($valores["pregunta"] ?? ""); ?>" maxlength="255" placeholder="Ej. ¿Qué etiqueta se usa para un párrafo?">
        <?php errorCampo($errores, "pregunta"); ?>
    </div>
    <p class="texto-gris" style="margin-top: 0">Escribe de 2 a 4 opciones y marca la correcta con el circulito.</p>
    <?php for ($o = 1; $o <= 4; $o++) { ?>
        <div class="campo" style="margin-bottom: 12px">
            <label class="opcion-editor">
                <input type="radio" name="correcta" value="<?php echo $o; ?>" aria-label="La opción <?php echo $o; ?> es la correcta"
                    <?php echo (int) ($valores["correcta"] ?? 0) === $o ? "checked" : ""; ?>>
                <input type="text" name="opcion<?php echo $o; ?>" value="<?php e($valores["opcion$o"] ?? ""); ?>" maxlength="150"
                       placeholder="Opción <?php echo $o; ?><?php echo $o > 2 ? " (opcional)" : ""; ?>" aria-label="Opción <?php echo $o; ?>">
            </label>
            <?php errorCampo($errores, "opcion$o"); ?>
        </div>
    <?php } ?>
    <div class="campo"><?php errorCampo($errores, "opciones"); ?></div>
    <?php
}

$titulo     = "Editar lección";
$menuActivo = "docente_cursos.php";
include "includes/arriba.php";
?>

<div class="migas">
    <a href="docente_cursos.php">Mis cursos</a> ›
    <a href="editar_curso.php?id=<?php echo $tema["curso_id"]; ?>"><?php e($tema["curso"]); ?></a> ›
    <a href="editar_curso.php?id=<?php echo $tema["curso_id"]; ?>#modulo<?php echo $tema["bloque_id"]; ?>"><?php e($tema["modulo"]); ?></a>
</div>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Editar lección · <?php e($tema["modulo"]); ?></p>
        <h1><?php e($tema["titulo"]); ?></h1>
    </div>
    <div class="fila">
        <a href="tema.php?id=<?php echo $temaId; ?>" class="boton boton-secundario">👁 Vista previa</a>
        <a href="editar_curso.php?id=<?php echo $tema["curso_id"]; ?>#modulo<?php echo $tema["bloque_id"]; ?>" class="boton">Listo, volver al curso</a>
    </div>
</div>

<!-- Pasos de la página -->
<nav class="pasos" aria-label="Partes de la lección">
    <a href="#titulo-leccion"><span>1</span> Título</a>
    <a href="#contenido"><span>2</span> Contenido (<?php echo count($secciones); ?>)</a>
    <a href="#cuestionario"><span>3</span> Cuestionario (<?php echo count($preguntas); ?>)</a>
</nav>

<!-- ===================== 1. Título ===================== -->
<form method="POST" class="tarjeta vigilar" id="titulo-leccion">
    <input type="hidden" name="accion" value="titulo">
    <div class="campo" style="margin-bottom: 16px">
        <label for="titulo">1. Título de la lección</label>
        <input type="text" id="titulo" name="titulo" value="<?php e($tema["titulo"]); ?>" maxlength="150" required>
        <?php if ($errorTitulo !== "") { ?><small class="error-campo"><?php e($errorTitulo); ?></small><?php } ?>
    </div>
    <button type="submit" class="boton boton-chico">Guardar título</button>
</form>

<!-- ===================== 2. Contenido ===================== -->
<h2 class="seccion" id="contenido">2. Contenido</h2>
<p class="texto-gris">
    Arma la lección con secciones, en el orden en que el alumno las leerá. Puedes alternar
    explicaciones, ejemplos de código, videos, imágenes y enlaces, y reordenarlas con las flechas.
</p>

<?php if (count($secciones) === 0) { ?>
    <div class="tarjeta vacio">
        <?php dibujoVacio(); ?>
        <p>Esta lección todavía no tiene contenido. Agrega la primera sección abajo.</p>
    </div>
<?php } ?>

<?php foreach ($secciones as $num => $s) { ?>
    <?php
    [$icono, $nombreTipo] = $tiposSeccion[$s["tipo"]];
    $conError = $formSeccion["id"] == $s["id"];
    $valores  = $conError ? $formSeccion["valores"] : $s;
    ?>
    <article class="seccion-editor" id="seccion<?php echo $s["id"]; ?>">
        <div class="seccion-editor-cabeza">
            <span class="mono"><?php echo $num + 1; ?> · <?php echo $icono . " " . $nombreTipo; ?></span>
            <div class="fila" style="gap: 8px">
                <form method="POST" class="fila" style="gap: 4px">
                    <input type="hidden" name="accion" value="mover_seccion">
                    <input type="hidden" name="seccion_id" value="<?php echo $s["id"]; ?>">
                    <button type="submit" name="direccion" value="subir" class="boton-icono" aria-label="Subir sección" title="Subir" <?php echo $num === 0 ? "disabled" : ""; ?>>↑</button>
                    <button type="submit" name="direccion" value="bajar" class="boton-icono" aria-label="Bajar sección" title="Bajar" <?php echo $num === count($secciones) - 1 ? "disabled" : ""; ?>>↓</button>
                </form>
                <form method="POST" data-confirmar="¿Eliminar esta sección (<?php echo $nombreTipo; ?>)?">
                    <input type="hidden" name="accion" value="borrar_seccion">
                    <input type="hidden" name="seccion_id" value="<?php echo $s["id"]; ?>">
                    <button type="submit" class="enlace-peligro">Eliminar</button>
                </form>
            </div>
        </div>

        <div class="seccion-editor-vista">
            <?php mostrarSeccion($s); ?>
        </div>

        <details class="seccion-editor-form" <?php echo $conError ? "open" : ""; ?>>
            <summary>✏️ Editar esta sección</summary>
            <form method="POST" enctype="multipart/form-data" class="vigilar" novalidate>
                <input type="hidden" name="accion" value="editar_seccion">
                <input type="hidden" name="seccion_id" value="<?php echo $s["id"]; ?>">
                <?php camposSeccion($s["tipo"], $valores, $conError ? $formSeccion["errores"] : [], $s); ?>
                <button type="submit" class="boton boton-chico">Guardar sección</button>
            </form>
        </details>
    </article>
<?php } ?>

<!-- Agregar sección: primero se elige el tipo, luego aparece su formulario -->
<div class="tarjeta" id="nueva">
    <h3>+ Agregar una sección</h3>
    <div class="tipos-seccion">
        <?php foreach ($tiposSeccion as $clave => [$icono, $nombre]) { ?>
            <a href="<?php echo $aqui; ?>&amp;nueva=<?php echo $clave; ?>#nueva" class="<?php echo $nuevaTipo === $clave ? "activo" : ""; ?>">
                <span aria-hidden="true"><?php echo $icono; ?></span> <?php echo $nombre; ?>
            </a>
        <?php } ?>
    </div>

    <?php if ($nuevaTipo !== "") { ?>
        <form method="POST" enctype="multipart/form-data" class="vigilar nueva-seccion" novalidate>
            <input type="hidden" name="accion" value="nueva_seccion">
            <input type="hidden" name="tipo" value="<?php echo $nuevaTipo; ?>">
            <h3 style="margin-top: 0">Nueva sección: <?php echo $tiposSeccion[$nuevaTipo][0] . " " . $tiposSeccion[$nuevaTipo][1]; ?></h3>
            <?php camposSeccion($nuevaTipo, $formSeccion["id"] === "nueva" ? $formSeccion["valores"] : [], $formSeccion["id"] === "nueva" ? $formSeccion["errores"] : []); ?>
            <div class="fila">
                <button type="submit" class="boton">Agregar sección</button>
                <a href="<?php echo $aqui; ?>#nueva">Cancelar</a>
            </div>
        </form>
    <?php } else { ?>
        <p class="texto-gris" style="margin-bottom: 0">Elige qué tipo de sección quieres agregar al final de la lección.</p>
    <?php } ?>
</div>

<!-- ===================== 3. Cuestionario ===================== -->
<h2 class="seccion" id="cuestionario">3. Cuestionario final</h2>
<p class="texto-gris">
    Opcional. Aparece al final de la lección y se califica solo. Agrega todas las preguntas que quieras, una por una.
</p>

<?php if ($contestaron > 0) { ?>
    <div class="aviso">
        <strong><?php echo $contestaron; ?> alumno(s) ya contestaron este cuestionario.</strong>
        Si agregas o quitas preguntas, o cambias una respuesta correcta, sus resultados se reiniciarán
        para que lo vuelvan a contestar. Corregir el texto de una pregunta no los afecta.
    </div>
<?php } ?>

<?php foreach ($preguntas as $num => $p) { ?>
    <?php
    $conError = $formPregunta["id"] == $p["id"];
    $valores  = $conError ? $formPregunta["valores"] : $p;
    ?>
    <article class="seccion-editor" id="pregunta<?php echo $p["id"]; ?>">
        <div class="seccion-editor-cabeza">
            <span class="mono">Pregunta <?php echo $num + 1; ?></span>
            <div class="fila" style="gap: 8px">
                <form method="POST" class="fila" style="gap: 4px">
                    <input type="hidden" name="accion" value="mover_pregunta">
                    <input type="hidden" name="pregunta_id" value="<?php echo $p["id"]; ?>">
                    <button type="submit" name="direccion" value="subir" class="boton-icono" aria-label="Subir pregunta" title="Subir" <?php echo $num === 0 ? "disabled" : ""; ?>>↑</button>
                    <button type="submit" name="direccion" value="bajar" class="boton-icono" aria-label="Bajar pregunta" title="Bajar" <?php echo $num === count($preguntas) - 1 ? "disabled" : ""; ?>>↓</button>
                </form>
                <form method="POST" data-confirmar="¿Eliminar la pregunta <?php echo $num + 1; ?>?"
                      <?php if ($contestaron > 0) { ?>data-detalle="Se reiniciarán los resultados de <?php echo $contestaron; ?> alumno(s)."<?php } ?>>
                    <input type="hidden" name="accion" value="borrar_pregunta">
                    <input type="hidden" name="pregunta_id" value="<?php echo $p["id"]; ?>">
                    <button type="submit" class="enlace-peligro">Eliminar</button>
                </form>
            </div>
        </div>
        <div class="seccion-editor-vista">
            <strong><?php e($p["pregunta"]); ?></strong>
            <ul class="opciones-vista">
                <?php for ($o = 1; $o <= 4; $o++) { ?>
                    <?php if ($p["opcion$o"] !== null && $p["opcion$o"] !== "") { ?>
                        <li class="<?php echo $o == $p["correcta"] ? "correcta" : ""; ?>">
                            <?php e($p["opcion$o"]); ?>
                            <?php if ($o == $p["correcta"]) { ?><span class="etiqueta">Correcta</span><?php } ?>
                        </li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
        <details class="seccion-editor-form" <?php echo $conError ? "open" : ""; ?>>
            <summary>✏️ Editar esta pregunta</summary>
            <form method="POST" class="vigilar" novalidate>
                <input type="hidden" name="accion" value="editar_pregunta">
                <input type="hidden" name="pregunta_id" value="<?php echo $p["id"]; ?>">
                <?php camposPregunta($valores, $conError ? $formPregunta["errores"] : [], $p["id"]); ?>
                <button type="submit" class="boton boton-chico">Guardar pregunta</button>
            </form>
        </details>
    </article>
<?php } ?>

<form method="POST" class="tarjeta vigilar" id="nueva-pregunta" novalidate
      <?php if ($contestaron > 0) { ?>data-confirmar="¿Agregar la pregunta?" data-detalle="Se reiniciarán los resultados de <?php echo $contestaron; ?> alumno(s) para que contesten el cuestionario completo."<?php } ?>>
    <input type="hidden" name="accion" value="nueva_pregunta">
    <h3>+ Nueva pregunta (será la número <?php echo count($preguntas) + 1; ?>)</h3>
    <?php camposPregunta($formPregunta["id"] === "nueva" ? $formPregunta["valores"] : [], $formPregunta["id"] === "nueva" ? $formPregunta["errores"] : [], "nueva"); ?>
    <button type="submit" class="boton">Agregar pregunta</button>
</form>

<!-- Moverse entre lecciones sin regresar al curso -->
<div class="fila entre" style="margin-top: 40px">
    <div>
        <?php if ($anterior) { ?>
            <a href="editar_tema.php?id=<?php echo $anterior["id"]; ?>">← Lección anterior: <?php e($anterior["titulo"]); ?></a>
        <?php } ?>
    </div>
    <div>
        <?php if ($siguiente) { ?>
            <a href="editar_tema.php?id=<?php echo $siguiente["id"]; ?>">Siguiente lección: <?php e($siguiente["titulo"]); ?> →</a>
        <?php } ?>
    </div>
</div>

<?php include "includes/abajo.php"; ?>
