<?php
require "includes/proteger.php";
permitir(["docente"]);

$errores     = [];
$tituloCurso = "";
$descripcion = "";
$categoriaId = 0;
$categorias  = consulta("SELECT * FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Crear un curso nuevo (empieza como borrador)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tituloCurso = trim($_POST["titulo"]);
    $descripcion = trim($_POST["descripcion"]);
    $categoriaId = (int) $_POST["categoria_id"];

    if ($tituloCurso === "") {
        $errores["titulo"] = "Escribe el título del curso.";
    } elseif (mb_strlen($tituloCurso) > 150) {
        $errores["titulo"] = "El título puede tener máximo 150 caracteres.";
    } else {
        consulta(
            "INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion) VALUES (?, ?, ?, ?)",
            [$miId, $categoriaId ?: null, $tituloCurso, $descripcion]
        );
        // insert_id = el id que MySQL le dio al curso recién creado
        irA("editar_curso.php?id=" . $conexion->insert_id, "Curso creado. Ahora agrega sus módulos y lecciones.");
    }
}

$cursos = consulta(
    "SELECT c.id, c.titulo, c.estado, cat.nombre AS categoria,
            (SELECT COUNT(*) FROM entregas e JOIN tareas t ON e.tarea_id = t.id
             WHERE t.curso_id = c.id AND e.calificacion IS NULL) AS por_calificar,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) AS alumnos,
            (SELECT COUNT(*) FROM temas t JOIN bloques b ON t.bloque_id = b.id
             WHERE b.curso_id = c.id) AS temas
     FROM cursos c
     LEFT JOIN categorias cat ON c.categoria_id = cat.id
     WHERE c.docente_id = ?
     ORDER BY c.created_at DESC",
    [$miId]
)->fetch_all(MYSQLI_ASSOC);

$titulo = "Mis cursos";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Panel del docente</p>
        <h1>Mis cursos</h1>
        <p class="texto-gris">Crea y organiza tus cursos.</p>
    </div>
</div>

<details class="tarjeta" <?php echo count($cursos) === 0 || count($errores) > 0 ? "open" : ""; ?>>
    <summary>+ Crear nuevo curso</summary>
    <form method="POST" class="validar" novalidate>
        <div class="campo">
            <label for="titulo">Título del curso</label>
            <input type="text" id="titulo" name="titulo" value="<?php e($tituloCurso); ?>" placeholder="Ej. Introducción a la programación" maxlength="150" required>
            <?php errorCampo($errores, "titulo"); ?>
        </div>
        <div class="campo">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id">
                <option value="0">Sin categoría</option>
                <?php foreach ($categorias as $cat) { ?>
                    <option value="<?php echo $cat["id"]; ?>" <?php echo $cat["id"] == $categoriaId ? "selected" : ""; ?>><?php e($cat["nombre"]); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="¿Qué aprenderán los alumnos?"><?php e($descripcion); ?></textarea>
        </div>
        <button type="submit" class="boton">Crear curso</button>
    </form>
</details>

<div class="rejilla">
    <?php foreach ($cursos as $c) { ?>
        <div class="tarjeta curso-tarjeta">
            <?php portada($c["id"]); ?>
            <div class="curso-cuerpo">
                <div class="fila" style="gap: 8px">
                    <?php if ($c["estado"] === "publicado") { ?>
                        <span class="etiqueta">Publicado</span>
                    <?php } else { ?>
                        <span class="etiqueta etiqueta-gris">Borrador</span>
                    <?php } ?>
                    <?php if ($c["categoria"]) { ?>
                        <span class="etiqueta etiqueta-gris"><?php e($c["categoria"]); ?></span>
                    <?php } ?>
                    <?php if ($c["por_calificar"] > 0) { ?>
                        <span class="etiqueta etiqueta-alerta"><?php echo $c["por_calificar"]; ?> por calificar</span>
                    <?php } ?>
                </div>
                <h3><?php e($c["titulo"]); ?></h3>
                <p class="texto-gris"><?php echo $c["alumnos"]; ?> alumnos · <?php echo $c["temas"]; ?> lecciones</p>
                <a href="editar_curso.php?id=<?php echo $c["id"]; ?>" class="boton">Editar curso</a>
            </div>
        </div>
    <?php } ?>
</div>

<?php include "includes/abajo.php"; ?>
