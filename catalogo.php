<?php
require "includes/proteger.php";
permitir(["alumno"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cursoId = (int) $_POST["curso_id"];
    $curso   = consulta("SELECT id, titulo, docente_id FROM cursos WHERE id = ? AND estado = 'publicado'", [$cursoId])
        ->fetch_assoc();

    // Volvemos al catálogo con los mismos filtros que tenía
    $regresar = "catalogo.php" . ($_POST["filtros"] !== "" ? "?" . $_POST["filtros"] : "");

    if ($curso && $_POST["accion"] === "inscribir") {
        $yaInscrito = consulta("SELECT 1 FROM inscripciones WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId])
            ->num_rows > 0;
        if (!$yaInscrito) {
            consulta("INSERT INTO inscripciones (alumno_id, curso_id) VALUES (?, ?)", [$miId, $cursoId]);
            notificar($curso["docente_id"], $_SESSION["nombre"] . " se inscribió a " . $curso["titulo"] . ".",
                "reporte.php?id=" . $cursoId);
        }
        irA("curso.php?id=" . $cursoId, "¡Te inscribiste! Empieza cuando quieras.");
    }

    // Poner o quitar de favoritos (si ya estaba, se quita)
    if ($curso && $_POST["accion"] === "favorito") {
        $yaEsta = consulta("SELECT 1 FROM favoritos WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId])
            ->num_rows > 0;
        if ($yaEsta) {
            consulta("DELETE FROM favoritos WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId]);
            irA($regresar, "Quitado de favoritos.");
        }
        consulta("INSERT INTO favoritos (alumno_id, curso_id) VALUES (?, ?)", [$miId, $cursoId]);
        irA($regresar, "Guardado en favoritos.");
    }
    irA($regresar);
}

// ---------- Filtros (vienen en la dirección: catalogo.php?buscar=...&categoria=...) ----------
$buscar     = trim($_GET["buscar"] ?? "");
$categoria  = (int) ($_GET["categoria"] ?? 0);
$docente    = (int) ($_GET["docente"] ?? 0);
$favoritos  = isset($_GET["favoritos"]);
$orden      = $_GET["orden"] ?? "recientes";

$ordenes = [
    "recientes" => "c.created_at DESC",
    "populares" => "alumnos DESC, c.created_at DESC",
    "mejores"   => "promedio DESC, resenas DESC",
    "nombre"    => "c.titulo",
];

$sql = "SELECT c.id, c.titulo, c.descripcion, u.nombre AS docente, cat.nombre AS categoria,
            (SELECT COUNT(*) FROM temas t JOIN bloques b ON t.bloque_id = b.id
             WHERE b.curso_id = c.id) AS temas,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) AS alumnos,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id AND i.alumno_id = ?) AS inscrito,
            (SELECT COUNT(*) FROM favoritos f WHERE f.curso_id = c.id AND f.alumno_id = ?) AS favorito,
            (SELECT COALESCE(AVG(estrellas), 0) FROM resenas r WHERE r.curso_id = c.id) AS promedio,
            (SELECT COUNT(*) FROM resenas r WHERE r.curso_id = c.id) AS resenas
        FROM cursos c
        JOIN usuarios u        ON c.docente_id = u.id
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE c.estado = 'publicado' AND (c.titulo LIKE ? OR c.descripcion LIKE ?)";
$parametros = [$miId, $miId, "%$buscar%", "%$buscar%"];

if ($categoria > 0) {
    $sql .= " AND c.categoria_id = ?";
    $parametros[] = $categoria;
}
if ($docente > 0) {
    $sql .= " AND c.docente_id = ?";
    $parametros[] = $docente;
}
if ($favoritos) {
    $sql .= " HAVING favorito > 0";
}
$sql .= " ORDER BY " . ($ordenes[$orden] ?? $ordenes["recientes"]);   // solo se usan órdenes de la lista

$cursos = consulta($sql, $parametros)->fetch_all(MYSQLI_ASSOC);

// Para llenar las listas de los filtros
$categorias = consulta("SELECT * FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$docentes   = consulta(
    "SELECT DISTINCT u.id, u.nombre FROM usuarios u
     JOIN cursos c ON c.docente_id = u.id AND c.estado = 'publicado'
     ORDER BY u.nombre"
)->fetch_all(MYSQLI_ASSOC);

$hayFiltros = $buscar !== "" || $categoria > 0 || $docente > 0 || $favoritos;

$titulo = "Explorar cursos";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Catálogo</p>
        <h1>Explorar cursos</h1>
        <p class="texto-gris">Encuentra algo nuevo para aprender.</p>
    </div>
</div>

<!-- Al cambiar una lista, el formulario se envía solo (onchange) -->
<form method="GET" class="filtros">
    <input type="search" name="buscar" value="<?php e($buscar); ?>" placeholder="Buscar por nombre o descripción" aria-label="Buscar">
    <select name="categoria" onchange="this.form.submit()" aria-label="Categoría">
        <option value="0">Todas las categorías</option>
        <?php foreach ($categorias as $cat) { ?>
            <option value="<?php echo $cat["id"]; ?>" <?php echo $cat["id"] == $categoria ? "selected" : ""; ?>><?php e($cat["nombre"]); ?></option>
        <?php } ?>
    </select>
    <select name="docente" onchange="this.form.submit()" aria-label="Docente">
        <option value="0">Todos los docentes</option>
        <?php foreach ($docentes as $d) { ?>
            <option value="<?php echo $d["id"]; ?>" <?php echo $d["id"] == $docente ? "selected" : ""; ?>><?php e($d["nombre"]); ?></option>
        <?php } ?>
    </select>
    <select name="orden" onchange="this.form.submit()" aria-label="Ordenar">
        <option value="recientes" <?php echo $orden === "recientes" ? "selected" : ""; ?>>Más recientes</option>
        <option value="populares" <?php echo $orden === "populares" ? "selected" : ""; ?>>Más populares</option>
        <option value="mejores"   <?php echo $orden === "mejores"   ? "selected" : ""; ?>>Mejor calificados</option>
        <option value="nombre"    <?php echo $orden === "nombre"    ? "selected" : ""; ?>>Por nombre (A-Z)</option>
    </select>
    <label><input type="checkbox" name="favoritos" onchange="this.form.submit()" <?php echo $favoritos ? "checked" : ""; ?>> Solo favoritos</label>
    <button type="submit" class="boton boton-secundario">Buscar</button>
    <?php if ($hayFiltros) { ?>
        <a href="catalogo.php">Quitar filtros</a>
    <?php } ?>
</form>

<?php if (count($cursos) === 0) { ?>
    <div class="tarjeta vacio">
        <?php dibujoVacio(); ?>
        <h2>No se encontraron cursos</h2>
        <p><?php echo $favoritos ? "Todavía no guardas cursos en favoritos (toca el ♡ de un curso)." : "Prueba con otra palabra o quita los filtros."; ?></p>
    </div>
<?php } else { ?>
    <p class="mono texto-gris"><?php echo count($cursos); ?> curso(s)</p>
<?php } ?>

<div class="rejilla">
    <?php foreach ($cursos as $c) { ?>
        <div class="tarjeta curso-tarjeta">
            <?php portada($c["id"]); ?>

            <form method="POST" class="favorito">
                <input type="hidden" name="accion" value="favorito">
                <input type="hidden" name="curso_id" value="<?php echo $c["id"]; ?>">
                <input type="hidden" name="filtros" value="<?php e($_SERVER["QUERY_STRING"] ?? ""); ?>">
                <button type="submit" class="<?php echo $c["favorito"] ? "si" : ""; ?>"
                        aria-label="<?php echo $c["favorito"] ? "Quitar de favoritos" : "Guardar en favoritos"; ?>"
                        title="<?php echo $c["favorito"] ? "Quitar de favoritos" : "Guardar en favoritos"; ?>">
                    <?php echo $c["favorito"] ? "♥" : "♡"; ?>
                </button>
            </form>

            <div class="curso-cuerpo">
                <?php if ($c["categoria"]) { ?>
                    <div><span class="etiqueta"><?php e($c["categoria"]); ?></span></div>
                <?php } ?>
                <h3><?php e($c["titulo"]); ?></h3>
                <span class="texto-gris"><?php e($c["docente"]); ?> · <?php echo $c["temas"]; ?> lecciones · <?php echo $c["alumnos"]; ?> alumnos</span>
                <div class="fila" style="gap: 8px">
                    <?php if ($c["resenas"] > 0) { ?>
                        <?php estrellas($c["promedio"]); ?>
                        <span class="texto-gris"><?php echo number_format($c["promedio"], 1); ?> (<?php echo $c["resenas"]; ?>)</span>
                    <?php } else { ?>
                        <span class="texto-gris">Sin reseñas todavía</span>
                    <?php } ?>
                </div>
                <!-- mb_strimwidth recorta la descripción a 110 letras y agrega "…" -->
                <p><?php e(mb_strimwidth($c["descripcion"] ?? "", 0, 110, "…")); ?></p>

                <?php if ($c["inscrito"]) { ?>
                    <?php $porcentaje = avance($miId, $c["id"]); ?>
                    <div class="fila">
                        <div class="barra-avance <?php echo $porcentaje == 100 ? "completa" : ""; ?>" style="flex: 1"><div style="width: <?php echo $porcentaje; ?>%"></div></div>
                        <span class="mono"><?php echo $porcentaje; ?>%</span>
                    </div>
                    <a href="curso.php?id=<?php echo $c["id"]; ?>" class="boton boton-secundario">Ir al curso</a>
                <?php } else { ?>
                    <form method="POST">
                        <input type="hidden" name="accion" value="inscribir">
                        <input type="hidden" name="curso_id" value="<?php echo $c["id"]; ?>">
                        <input type="hidden" name="filtros" value="">
                        <button type="submit" class="boton">Inscribirme</button>
                    </form>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>

<?php include "includes/abajo.php"; ?>
