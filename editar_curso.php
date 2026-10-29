<?php
require "includes/proteger.php";
permitir(["docente"]);

// Buscamos el curso, pero SOLO si es de este docente
$cursoId = (int) ($_GET["id"] ?? 0);
$curso   = consulta("SELECT * FROM cursos WHERE id = ? AND docente_id = ?", [$cursoId, $miId])->fetch_assoc();
if (!$curso) {
    irA("docente_cursos.php", "Ese curso no existe o no es tuyo.", "error");
}

// Dos vistas: "contenido" (módulos y lecciones) y "ajustes" (título, categoría, eliminar)
$vista = ($_GET["vista"] ?? "") === "ajustes" ? "ajustes" : "contenido";
$aqui  = "editar_curso.php?id=" . $cursoId;

// Lo que se muestra en el formulario de Ajustes
$info    = ["titulo" => $curso["titulo"], "descripcion" => $curso["descripcion"], "categoria_id" => (int) $curso["categoria_id"]];
$errores = [];

// Cuántos alumnos hay: sirve para advertir antes de borrar algo
$inscritos = consulta("SELECT COUNT(*) AS total FROM inscripciones WHERE curso_id = ?", [$cursoId])->fetch_assoc()["total"];

// ---------- Acciones de los formularios ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"];

    // Guardar título, categoría y descripción
    if ($accion === "info") {
        $info = ["titulo" => trim($_POST["titulo"]), "descripcion" => trim($_POST["descripcion"]),
                 "categoria_id" => (int) $_POST["categoria_id"]];
        if ($info["titulo"] === "") {
            $errores["titulo"] = "El título no puede quedar vacío.";
        } elseif (mb_strlen($info["titulo"]) > 150) {
            $errores["titulo"] = "El título puede tener máximo 150 caracteres.";
        } else {
            consulta("UPDATE cursos SET titulo = ?, descripcion = ?, categoria_id = ? WHERE id = ?",
                [$info["titulo"], $info["descripcion"], $info["categoria_id"] ?: null, $cursoId]);
            irA($aqui . "&vista=ajustes", "Ajustes guardados.");
        }
        $vista = "ajustes";
    }

    // Publicar (solo si pasa la revisión) o regresar a borrador
    if ($accion === "estado") {
        if ($curso["estado"] === "publicado") {
            consulta("UPDATE cursos SET estado = 'borrador' WHERE id = ?", [$cursoId]);
            irA($aqui, "El curso volvió a borrador: ya no aparece en el catálogo.");
        }
        if (!listoParaPublicar(revisionCurso($curso))) {
            irA($aqui, "Todavía no se puede publicar: revisa la lista \"Antes de publicar\".", "error");
        }
        consulta("UPDATE cursos SET estado = 'publicado' WHERE id = ?", [$cursoId]);
        irA($aqui, "¡Curso publicado! Ya aparece en el catálogo de los alumnos.");
    }

    // ----- Módulos -----
    if ($accion === "nuevo_bloque" || $accion === "renombrar_bloque") {
        $tituloModulo = trim($_POST["titulo"]);
        if ($tituloModulo === "" || mb_strlen($tituloModulo) > 150) {
            irA($aqui, "El nombre del módulo debe tener entre 1 y 150 caracteres.", "error");
        }
        if ($accion === "nuevo_bloque") {
            consulta("INSERT INTO bloques (curso_id, titulo, orden) VALUES (?, ?, ?)",
                [$cursoId, $tituloModulo, siguienteOrden("bloques", "curso_id", $cursoId)]);
            irA($aqui . "#modulo" . $conexion->insert_id, "Módulo agregado. Ahora agrégale lecciones.");
        }
        consulta("UPDATE bloques SET titulo = ? WHERE id = ? AND curso_id = ?", [$tituloModulo, (int) $_POST["bloque_id"], $cursoId]);
        irA($aqui . "#modulo" . (int) $_POST["bloque_id"], "Módulo renombrado.");
    }

    if ($accion === "mover_bloque") {
        mover("bloques", $cursoId, (int) $_POST["bloque_id"], $_POST["direccion"]);
        irA($aqui . "#modulo" . (int) $_POST["bloque_id"]);
    }

    // Eliminar un módulo (sus lecciones se borran solas por el ON DELETE CASCADE)
    if ($accion === "borrar_bloque") {
        $bloqueId = (int) $_POST["bloque_id"];
        borrarImagenesSecciones("b.id = ? AND b.curso_id = ?", [$bloqueId, $cursoId]);
        consulta("DELETE FROM bloques WHERE id = ? AND curso_id = ?", [$bloqueId, $cursoId]);
        irA($aqui, "Módulo eliminado.");
    }

    // ----- Lecciones -----
    // Al crear una lección te lleva directo a escribir su contenido
    if ($accion === "nuevo_tema") {
        $bloqueId      = (int) $_POST["bloque_id"];
        $tituloLeccion = trim($_POST["titulo"]);
        $bloque        = consulta("SELECT id FROM bloques WHERE id = ? AND curso_id = ?", [$bloqueId, $cursoId])->fetch_assoc();
        if (!$bloque || $tituloLeccion === "" || mb_strlen($tituloLeccion) > 150) {
            irA($aqui, "El nombre de la lección debe tener entre 1 y 150 caracteres.", "error");
        }
        consulta("INSERT INTO temas (bloque_id, titulo, orden) VALUES (?, ?, ?)",
            [$bloqueId, $tituloLeccion, siguienteOrden("temas", "bloque_id", $bloqueId)]);
        irA("editar_tema.php?id=" . $conexion->insert_id, "Lección creada. Ahora agrégale contenido con las secciones.");
    }

    // Para mover o borrar una lección, primero revisamos que sea de este curso
    if ($accion === "mover_tema" || $accion === "borrar_tema") {
        $temaId = (int) $_POST["tema_id"];
        $leccion = consulta(
            "SELECT t.id, t.bloque_id FROM temas t JOIN bloques b ON t.bloque_id = b.id
             WHERE t.id = ? AND b.curso_id = ?",
            [$temaId, $cursoId]
        )->fetch_assoc();

        if ($leccion && $accion === "mover_tema") {
            mover("temas", $leccion["bloque_id"], $temaId, $_POST["direccion"]);
            irA($aqui . "#modulo" . $leccion["bloque_id"]);
        }
        if ($leccion && $accion === "borrar_tema") {
            borrarImagenesSecciones("t.id = ?", [$temaId]);
            consulta("DELETE FROM temas WHERE id = ?", [$temaId]);
            irA($aqui . "#modulo" . $leccion["bloque_id"], "Lección eliminada.");
        }
        irA($aqui);
    }

    // Eliminar todo el curso (y sus archivos subidos)
    if ($accion === "borrar_curso") {
        borrarArchivosCurso($cursoId);
        consulta("DELETE FROM cursos WHERE id = ?", [$cursoId]);
        irA("docente_cursos.php", "Curso eliminado.");
    }
}

$bloques    = consulta("SELECT * FROM bloques WHERE curso_id = ? ORDER BY orden, id", [$cursoId])->fetch_all(MYSQLI_ASSOC);
$categorias = consulta("SELECT * FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$revision   = revisionCurso($curso);
$listo      = listoParaPublicar($revision);

// Texto de advertencia para borrar cosas cuando ya hay alumnos
$aviso = $inscritos > 0
    ? "Hay $inscritos alumno(s) inscrito(s): perderán su avance y sus resultados de cuestionario en lo que borres. "
    : "";

$titulo     = $curso["titulo"];
$menuActivo = "docente_cursos.php";
$pestana    = $vista;
include "includes/arriba.php";
include "includes/curso_docente.php";
?>

<?php if ($vista === "contenido") { ?>
<!-- ======================= CONTENIDO ======================= -->

<?php
// Lista "Antes de publicar": siempre en borrador; ya publicado, solo si algo falla
$algoFalla = in_array(false, array_column($revision, "ok"), true);
?>
<?php if ($curso["estado"] === "borrador" || $algoFalla) { ?>
    <div class="tarjeta revision">
        <h2><?php echo $curso["estado"] === "borrador" ? "Antes de publicar" : "Revisa tu curso"; ?></h2>
        <ul>
            <?php foreach ($revision as $punto) { ?>
                <li class="<?php echo $punto["ok"] ? "ok" : ($punto["obligatorio"] ? "falta" : "sugerencia"); ?>">
                    <span class="marca <?php echo $punto["ok"] ? "hecha" : ""; ?>"><?php echo $punto["ok"] ? "✓" : ($punto["obligatorio"] ? "!" : "·"); ?></span>
                    <span><?php e($punto["texto"]); ?><?php echo !$punto["ok"] && !$punto["obligatorio"] ? " <em>(recomendado)</em>" : ""; ?></span>
                </li>
            <?php } ?>
        </ul>
        <?php if ($curso["estado"] === "borrador") { ?>
            <p class="texto-gris" style="margin: 16px 0 0">
                <?php echo $listo ? "¡Todo listo! Ya puedes publicar tu curso con el botón de arriba." : "Completa los puntos marcados con ! para poder publicar."; ?>
            </p>
        <?php } ?>
    </div>
<?php } ?>

<p class="texto-gris">
    Organiza tu curso en <strong>módulos</strong> (las partes grandes) y cada módulo en <strong>lecciones</strong>.
    Cada lección se arma con secciones de texto, código, video, imágenes y enlaces, y puede terminar con un cuestionario.
</p>

<?php if (count($bloques) === 0) { ?>
    <div class="tarjeta vacio">
        <?php dibujoVacio(); ?>
        <h2>Empieza con tu primer módulo</h2>
        <p>Por ejemplo: "Introducción", "Conceptos básicos" o "Semana 1".</p>
    </div>
<?php } ?>

<?php foreach ($bloques as $numModulo => $bloque) { ?>
    <?php
    $lecciones = consulta(
        "SELECT t.*,
                (SELECT COUNT(*) FROM secciones s WHERE s.tema_id = t.id) AS secciones,
                (SELECT COUNT(*) FROM preguntas p WHERE p.tema_id = t.id) AS preguntas
         FROM temas t WHERE t.bloque_id = ? ORDER BY t.orden, t.id",
        [$bloque["id"]]
    )->fetch_all(MYSQLI_ASSOC);
    $esPrimero = $numModulo === 0;
    $esUltimo  = $numModulo === count($bloques) - 1;
    ?>
    <section class="tarjeta modulo" id="modulo<?php echo $bloque["id"]; ?>">
        <div class="fila entre modulo-cabeza">
            <div>
                <p class="mono texto-gris" style="margin: 0">Módulo <?php echo $numModulo + 1; ?></p>
                <h2 style="margin: 0"><?php e($bloque["titulo"]); ?></h2>
            </div>
            <div class="fila acciones">
                <form method="POST">
                    <input type="hidden" name="accion" value="mover_bloque">
                    <input type="hidden" name="bloque_id" value="<?php echo $bloque["id"]; ?>">
                    <button type="submit" name="direccion" value="subir" class="boton-icono" title="Subir módulo" aria-label="Subir módulo" <?php echo $esPrimero ? "disabled" : ""; ?>>↑</button>
                    <button type="submit" name="direccion" value="bajar" class="boton-icono" title="Bajar módulo" aria-label="Bajar módulo" <?php echo $esUltimo ? "disabled" : ""; ?>>↓</button>
                </form>
                <details class="desplegable">
                    <summary class="boton boton-secundario boton-chico">Renombrar</summary>
                    <form method="POST" class="fila desplegable-caja">
                        <input type="hidden" name="accion" value="renombrar_bloque">
                        <input type="hidden" name="bloque_id" value="<?php echo $bloque["id"]; ?>">
                        <input type="text" name="titulo" value="<?php e($bloque["titulo"]); ?>" maxlength="150" required aria-label="Nuevo nombre del módulo">
                        <button type="submit" class="boton boton-chico">Guardar</button>
                    </form>
                </details>
                <form method="POST" data-confirmar="¿Eliminar el módulo «<?php e($bloque["titulo"]); ?>»?"
                      data-detalle="<?php e($aviso); ?>Se borrarán también sus <?php echo count($lecciones); ?> lección(es). Esta acción no se puede deshacer.">
                    <input type="hidden" name="accion" value="borrar_bloque">
                    <input type="hidden" name="bloque_id" value="<?php echo $bloque["id"]; ?>">
                    <button type="submit" class="enlace-peligro">Eliminar</button>
                </form>
            </div>
        </div>

        <?php if (count($lecciones) === 0) { ?>
            <p class="texto-gris">Este módulo todavía no tiene lecciones.</p>
        <?php } ?>

        <ul class="lista-temas lista-lecciones">
            <?php foreach ($lecciones as $numLeccion => $l) { ?>
                <li>
                    <span class="mono texto-gris"><?php echo ($numModulo + 1) . "." . ($numLeccion + 1); ?></span>
                    <a href="editar_tema.php?id=<?php echo $l["id"]; ?>"><?php e($l["titulo"]); ?></a>
                    <?php if ($l["secciones"] == 0) { ?>
                        <span class="etiqueta etiqueta-alerta">Sin contenido</span>
                    <?php } else { ?>
                        <span class="etiqueta etiqueta-gris"><?php echo $l["secciones"]; ?> secc.</span>
                    <?php } ?>
                    <?php if ($l["preguntas"] > 0) { ?>
                        <span class="etiqueta"><?php echo $l["preguntas"]; ?> preg.</span>
                    <?php } ?>
                    <form method="POST" class="fila" style="gap: 4px">
                        <input type="hidden" name="accion" value="mover_tema">
                        <input type="hidden" name="tema_id" value="<?php echo $l["id"]; ?>">
                        <button type="submit" name="direccion" value="subir" class="boton-icono" title="Subir lección" aria-label="Subir lección" <?php echo $numLeccion === 0 ? "disabled" : ""; ?>>↑</button>
                        <button type="submit" name="direccion" value="bajar" class="boton-icono" title="Bajar lección" aria-label="Bajar lección" <?php echo $numLeccion === count($lecciones) - 1 ? "disabled" : ""; ?>>↓</button>
                    </form>
                    <a href="editar_tema.php?id=<?php echo $l["id"]; ?>" class="boton boton-secundario boton-chico">Editar</a>
                    <a href="tema.php?id=<?php echo $l["id"]; ?>" class="boton-icono" title="Vista previa como alumno" aria-label="Vista previa como alumno">👁</a>
                    <form method="POST" data-confirmar="¿Eliminar la lección «<?php e($l["titulo"]); ?>»?"
                          data-detalle="<?php e($aviso); ?>Se borrarán sus secciones y su cuestionario.">
                        <input type="hidden" name="accion" value="borrar_tema">
                        <input type="hidden" name="tema_id" value="<?php echo $l["id"]; ?>">
                        <button type="submit" class="enlace-peligro">Eliminar</button>
                    </form>
                </li>
            <?php } ?>
        </ul>

        <form method="POST" class="fila agregar-linea">
            <input type="hidden" name="accion" value="nuevo_tema">
            <input type="hidden" name="bloque_id" value="<?php echo $bloque["id"]; ?>">
            <input type="text" name="titulo" placeholder="Nombre de la nueva lección" maxlength="150" required aria-label="Nombre de la nueva lección">
            <button type="submit" class="boton boton-secundario boton-chico">+ Agregar lección</button>
        </form>
    </section>
<?php } ?>

<form method="POST" class="tarjeta fila agregar-linea">
    <input type="hidden" name="accion" value="nuevo_bloque">
    <input type="text" name="titulo" placeholder="Nombre del nuevo módulo (ej. Introducción)" maxlength="150" required aria-label="Nombre del nuevo módulo">
    <button type="submit" class="boton">+ Agregar módulo</button>
</form>

<?php } else { ?>
<!-- ======================= AJUSTES ======================= -->

<form method="POST" class="tarjeta tarjeta-grande validar" novalidate>
    <input type="hidden" name="accion" value="info">
    <h2>Información del curso</h2>
    <div class="campo">
        <label for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" value="<?php e($info["titulo"]); ?>" maxlength="150" required>
        <?php errorCampo($errores, "titulo"); ?>
    </div>
    <div class="campo">
        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id">
            <option value="0">Sin categoría</option>
            <?php foreach ($categorias as $cat) { ?>
                <option value="<?php echo $cat["id"]; ?>" <?php echo $cat["id"] == $info["categoria_id"] ? "selected" : ""; ?>><?php e($cat["nombre"]); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="campo">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="4" placeholder="¿Qué aprenderán los alumnos? Se muestra en el catálogo."><?php e($info["descripcion"]); ?></textarea>
    </div>
    <button type="submit" class="boton">Guardar ajustes</button>
</form>

<div class="tarjeta zona-peligro">
    <h2>Eliminar curso</h2>
    <p class="texto-gris">Se borrarán sus módulos, lecciones, tareas, avisos, foro e inscripciones. No se puede deshacer.</p>
    <form method="POST" data-confirmar="¿Eliminar el curso «<?php e($curso["titulo"]); ?>»?"
          data-detalle="<?php echo $inscritos > 0 ? "Hay $inscritos alumno(s) inscrito(s) que perderán todo su avance. " : ""; ?>Se borrará todo su contenido. No se puede deshacer.">
        <input type="hidden" name="accion" value="borrar_curso">
        <button type="submit" class="boton boton-peligro">Eliminar este curso</button>
    </form>
</div>

<?php } ?>

<?php include "includes/abajo.php"; ?>
