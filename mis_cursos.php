<?php
require "includes/proteger.php";
permitir(["alumno"]);

$cursos = consulta(
    "SELECT c.id, c.titulo, u.nombre AS docente
     FROM inscripciones i
     JOIN cursos c   ON i.curso_id = c.id
     JOIN usuarios u ON c.docente_id = u.id
     WHERE i.alumno_id = ?
     ORDER BY i.fecha DESC",
    [$miId]
)->fetch_all(MYSQLI_ASSOC);

$titulo = "Mis cursos";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Mi aprendizaje</p>
        <h1>Mis cursos</h1>
        <p class="texto-gris">Los cursos en los que estás inscrito.</p>
    </div>
    <a href="catalogo.php" class="boton boton-secundario">Explorar más cursos</a>
</div>

<?php if (count($cursos) === 0) { ?>
    <div class="tarjeta vacio">
        <?php dibujoVacio(); ?>
        <h2>Todavía no te inscribes a ningún curso</h2>
        <p>En el catálogo hay cursos esperándote.</p>
        <a href="catalogo.php" class="boton">Explorar cursos</a>
    </div>
<?php } ?>

<div class="rejilla">
    <?php foreach ($cursos as $c) { ?>
        <?php $porcentaje = avance($miId, $c["id"]); ?>
        <div class="tarjeta curso-tarjeta">
            <?php portada($c["id"]); ?>
            <div class="curso-cuerpo">
                <?php if ($porcentaje == 100) { ?>
                    <div><span class="etiqueta etiqueta-alerta">✓ Terminado</span></div>
                <?php } ?>
                <h3><?php e($c["titulo"]); ?></h3>
                <span class="texto-gris"><?php e($c["docente"]); ?></span>
                <div class="fila">
                    <div class="barra-avance <?php echo $porcentaje == 100 ? "completa" : ""; ?>" style="flex: 1"><div style="width: <?php echo $porcentaje; ?>%"></div></div>
                    <span class="mono"><?php echo $porcentaje; ?>%</span>
                </div>
                <?php if ($porcentaje == 100) { ?>
                    <a href="constancia.php?curso=<?php echo $c["id"]; ?>" class="boton">Ver constancia</a>
                    <a href="curso.php?id=<?php echo $c["id"]; ?>" style="text-align: center">Repasar el curso</a>
                <?php } else { ?>
                    <a href="curso.php?id=<?php echo $c["id"]; ?>" class="boton">Continuar</a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>

<?php include "includes/abajo.php"; ?>
