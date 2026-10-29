<?php
require "includes/proteger.php";
permitir(["super_admin", "director"]);

$errorCategoria = "";

// Solo el Super Admin puede eliminar cursos y administrar categorías
if ($_SERVER["REQUEST_METHOD"] === "POST" && $rol === "super_admin") {
    $accion = $_POST["accion"];

    if ($accion === "borrar_curso") {
        borrarArchivosCurso((int) $_POST["id"]);   // imágenes de lecciones y archivos de tareas
        consulta("DELETE FROM cursos WHERE id = ?", [(int) $_POST["id"]]);
        irA("cursos.php", "Curso eliminado.");
    }

    if ($accion === "nueva_categoria") {
        $nombre = trim($_POST["nombre"]);
        if ($nombre === "" || mb_strlen($nombre) > 60) {
            $errorCategoria = "Escribe un nombre de máximo 60 caracteres.";
        } else {
            try {
                consulta("INSERT INTO categorias (nombre) VALUES (?)", [$nombre]);
                irA("cursos.php#categorias", "Categoría agregada.");
            } catch (mysqli_sql_exception $e) {
                $errorCategoria = "Esa categoría ya existe.";
            }
        }
    }

    // Los cursos de esa categoría se quedan "sin categoría" (ON DELETE SET NULL)
    if ($accion === "borrar_categoria") {
        consulta("DELETE FROM categorias WHERE id = ?", [(int) $_POST["id"]]);
        irA("cursos.php#categorias", "Categoría eliminada.");
    }
}

$cursos = consulta(
    "SELECT c.id, c.titulo, c.estado, u.nombre AS docente, cat.nombre AS categoria,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) AS alumnos,
            (SELECT COALESCE(AVG(estrellas), 0) FROM resenas r WHERE r.curso_id = c.id) AS promedio,
            (SELECT COUNT(*) FROM resenas r WHERE r.curso_id = c.id) AS resenas
     FROM cursos c
     JOIN usuarios u          ON c.docente_id = u.id
     LEFT JOIN categorias cat ON c.categoria_id = cat.id
     ORDER BY c.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$titulo = "Cursos";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Administración</p>
        <h1>Cursos</h1>
        <p class="texto-gris">Todos los cursos de la plataforma y el avance de sus alumnos.</p>
    </div>
    <a href="exportar.php?tipo=cursos" class="boton boton-secundario">Descargar en Excel</a>
</div>

<div class="tarjeta">
    <?php if (count($cursos) === 0) { ?>
        <p class="vacio">Todavía no hay cursos. Los crean los docentes.</p>
    <?php } else { ?>
    <div class="tabla-contenedor">
        <table class="tabla">
            <tr>
                <th>Curso</th>
                <th>Docente</th>
                <th>Estado</th>
                <th>Reseñas</th>
                <th>Alumnos</th>
                <th>Avance promedio</th>
                <th></th>
            </tr>
            <?php foreach ($cursos as $c) { ?>
                <?php $promedio = avancePromedio($c["id"]); ?>
                <tr>
                    <td>
                        <strong><?php e($c["titulo"]); ?></strong><br>
                        <span class="texto-gris"><?php e($c["categoria"] ?? "Sin categoría"); ?></span>
                    </td>
                    <td><?php e($c["docente"]); ?></td>
                    <td>
                        <?php if ($c["estado"] === "publicado") { ?>
                            <span class="etiqueta">Publicado</span>
                        <?php } else { ?>
                            <span class="etiqueta etiqueta-gris">Borrador</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($c["resenas"] > 0) { ?>
                            <?php estrellas($c["promedio"]); ?> <span class="texto-gris">(<?php echo $c["resenas"]; ?>)</span>
                        <?php } else { ?>
                            <span class="texto-gris">—</span>
                        <?php } ?>
                    </td>
                    <td><?php echo $c["alumnos"]; ?></td>
                    <td style="min-width: 140px">
                        <div class="fila">
                            <div class="barra-avance" style="flex: 1"><div style="width: <?php echo $promedio; ?>%"></div></div>
                            <span><?php echo $promedio; ?>%</span>
                        </div>
                    </td>
                    <td>
                        <div class="fila">
                            <a href="reporte.php?id=<?php echo $c["id"]; ?>" class="boton boton-secundario boton-chico">Reporte</a>
                            <?php if ($rol === "super_admin") { ?>
                                <form method="POST" data-confirmar="¿Eliminar este curso y todo su contenido?">
                                    <input type="hidden" name="accion" value="borrar_curso">
                                    <input type="hidden" name="id" value="<?php echo $c["id"]; ?>">
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

<?php if ($rol === "super_admin") { ?>
    <?php
    $categorias = consulta(
        "SELECT cat.*, (SELECT COUNT(*) FROM cursos c WHERE c.categoria_id = cat.id) AS cursos
         FROM categorias cat ORDER BY cat.nombre"
    )->fetch_all(MYSQLI_ASSOC);
    ?>
    <h2 class="seccion" id="categorias">Categorías</h2>
    <p class="texto-gris">Los docentes eligen una al crear su curso; los alumnos las usan para filtrar el catálogo.</p>
    <div class="tarjeta">
        <form method="POST" class="fila" style="margin-bottom: 16px; align-items: flex-start">
            <input type="hidden" name="accion" value="nueva_categoria">
            <div class="campo" style="flex: 1; min-width: 200px; margin: 0">
                <input type="text" name="nombre" placeholder="Nueva categoría (ej. Música)" maxlength="60" required aria-label="Nombre de la categoría">
                <?php if ($errorCategoria !== "") { ?><small class="error-campo"><?php e($errorCategoria); ?></small><?php } ?>
            </div>
            <button type="submit" class="boton">+ Agregar</button>
        </form>
        <ul class="lista-temas">
            <?php foreach ($categorias as $cat) { ?>
                <li>
                    <span style="flex: 1"><?php e($cat["nombre"]); ?></span>
                    <span class="texto-gris"><?php echo $cat["cursos"]; ?> curso(s)</span>
                    <form method="POST" data-confirmar="¿Eliminar la categoría «<?php e($cat["nombre"]); ?>»? Sus cursos quedarán sin categoría.">
                        <input type="hidden" name="accion" value="borrar_categoria">
                        <input type="hidden" name="id" value="<?php echo $cat["id"]; ?>">
                        <button type="submit" class="enlace-peligro">Eliminar</button>
                    </form>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<?php include "includes/abajo.php"; ?>
