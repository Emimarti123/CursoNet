<?php
require "includes/proteger.php";

$titulo = "Inicio";
include "includes/arriba.php";

// Primer nombre, para saludar ("Emiliano Martínez" -> "Emiliano")
$primerNombre = explode(" ", trim($_SESSION["nombre"]))[0];

// ===================== ALUMNO =====================
if ($rol === "alumno") {
    $cursos = consulta(
        "SELECT c.id, c.titulo, u.nombre AS docente
         FROM inscripciones i
         JOIN cursos c   ON i.curso_id = c.id
         JOIN usuarios u ON c.docente_id = u.id
         WHERE i.alumno_id = ?
         ORDER BY i.fecha DESC",
        [$miId]
    )->fetch_all(MYSQLI_ASSOC);

    // Buscamos el primer curso que todavía no termina
    $siguiente = null;
    foreach ($cursos as $curso) {
        $curso["avance"] = avance($miId, $curso["id"]);
        if ($curso["avance"] < 100) {
            $siguiente = $curso;
            break;
        }
    }

    // Tareas de mis cursos que todavía no entrego (las que vencen antes, primero)
    $pendientes = consulta(
        "SELECT t.id, t.titulo, t.fecha_limite, c.titulo AS curso
         FROM tareas t
         JOIN cursos c        ON t.curso_id = c.id
         JOIN inscripciones i ON i.curso_id = c.id AND i.alumno_id = ?
         LEFT JOIN entregas e ON e.tarea_id = t.id AND e.alumno_id = ?
         WHERE e.id IS NULL
         ORDER BY t.fecha_limite IS NULL, t.fecha_limite",
        [$miId, $miId]
    )->fetch_all(MYSQLI_ASSOC);

    // Los 3 avisos más recientes de mis cursos
    $avisos = consulta(
        "SELECT a.*, c.titulo AS curso
         FROM avisos a
         JOIN inscripciones i ON i.curso_id = a.curso_id AND i.alumno_id = ?
         JOIN cursos c        ON a.curso_id = c.id
         ORDER BY a.fecha DESC
         LIMIT 3",
        [$miId]
    )->fetch_all(MYSQLI_ASSOC);
?>
    <section class="banda">
        <p class="mono" style="margin-bottom: 16px">Panel del alumno</p>
        <h1>Hola, <?php e($primerNombre); ?>.</h1>
        <p>
            <?php if (count($pendientes) > 0) { ?>
                Tienes <?php echo count($pendientes); ?> tarea(s) por entregar.
            <?php } else { ?>
                Estás al día con tus tareas. ¡Buen trabajo!
            <?php } ?>
        </p>
    </section>

    <?php if (count($cursos) === 0) { ?>
        <div class="tarjeta vacio">
            <?php dibujoVacio(); ?>
            <h2>Todavía no tienes cursos</h2>
            <p>Explora el catálogo e inscríbete al que más te interese.</p>
            <a href="catalogo.php" class="boton">Explorar cursos</a>
        </div>
    <?php } elseif ($siguiente) { ?>
        <h2 class="seccion">Continúa donde te quedaste</h2>
        <div class="tarjeta tarjeta-grande">
            <div class="fila entre">
                <div>
                    <h3><?php e($siguiente["titulo"]); ?></h3>
                    <span class="texto-gris">Docente: <?php e($siguiente["docente"]); ?></span>
                </div>
                <span class="nota"><?php echo $siguiente["avance"]; ?><small>%</small></span>
            </div>
            <div class="barra-avance" style="margin: 16px 0 24px">
                <div style="width: <?php echo $siguiente["avance"]; ?>%"></div>
            </div>
            <div class="fila">
                <a href="curso.php?id=<?php echo $siguiente["id"]; ?>" class="boton">Continuar</a>
                <a href="mis_cursos.php">Ver todos mis cursos (<?php echo count($cursos); ?>)</a>
            </div>
        </div>
    <?php } else { ?>
        <div class="tarjeta vacio">
            <?php dibujoVacio(); ?>
            <h2>¡Terminaste todos tus cursos!</h2>
            <p>Descarga tus constancias en "Mis cursos" o busca uno nuevo.</p>
            <a href="catalogo.php" class="boton">Explorar cursos</a>
        </div>
    <?php } ?>

    <?php if (count($cursos) > 0) { ?>
    <div class="rejilla rejilla-2" style="margin-top: 40px">
        <div class="tarjeta">
            <div class="fila entre" style="margin-bottom: 16px">
                <h2 style="margin: 0">Tareas pendientes</h2>
                <a href="tareas.php">Ver todas</a>
            </div>
            <?php if (count($pendientes) === 0) { ?>
                <p class="texto-gris">No tienes tareas por entregar.</p>
            <?php } ?>
            <ul class="lista-temas">
                <?php foreach (array_slice($pendientes, 0, 5) as $t) { ?>
                    <li>
                        <a href="tarea.php?id=<?php echo $t["id"]; ?>">
                            <strong><?php e($t["titulo"]); ?></strong><br>
                            <span class="texto-gris"><?php e($t["curso"]); ?></span>
                        </a>
                        <?php if ($t["fecha_limite"]) { ?>
                            <?php $vencida = $t["fecha_limite"] < date("Y-m-d"); ?>
                            <span class="etiqueta <?php echo $vencida ? "etiqueta-roja" : "etiqueta-alerta"; ?>">
                                <?php echo $vencida ? "Vencida" : "Para el " . fecha($t["fecha_limite"]); ?>
                            </span>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <div class="tarjeta">
            <h2>Avisos recientes</h2>
            <?php if (count($avisos) === 0) { ?>
                <p class="texto-gris">Tus docentes no han publicado avisos.</p>
            <?php } ?>
            <?php foreach ($avisos as $a) { ?>
                <div class="mensaje">
                    <div style="flex: 1">
                        <span class="mono texto-gris"><?php e($a["curso"]); ?> · <?php echo fecha($a["fecha"]); ?></span>
                        <h3 style="margin: 4px 0 0"><?php e($a["titulo"]); ?></h3>
                        <p><?php e(mb_strimwidth($a["mensaje"], 0, 140, "…")); ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

<?php
// ===================== DOCENTE =====================
} elseif ($rol === "docente") {
    $datos = consulta(
        "SELECT COUNT(*) AS cursos,
                SUM(estado = 'publicado') AS publicados,
                (SELECT COUNT(*) FROM inscripciones i
                 JOIN cursos c2 ON i.curso_id = c2.id
                 WHERE c2.docente_id = ?) AS alumnos
         FROM cursos WHERE docente_id = ?",
        [$miId, $miId]
    )->fetch_assoc();

    // Entregas de mis alumnos que todavía no califico
    $porCalificar = consulta(
        "SELECT e.fecha, t.id AS tarea_id, t.titulo AS tarea, c.titulo AS curso, u.nombre AS alumno, u.foto
         FROM entregas e
         JOIN tareas t   ON e.tarea_id = t.id
         JOIN cursos c   ON t.curso_id = c.id
         JOIN usuarios u ON e.alumno_id = u.id
         WHERE c.docente_id = ? AND e.calificacion IS NULL
         ORDER BY e.fecha",
        [$miId]
    )->fetch_all(MYSQLI_ASSOC);
?>
    <section class="banda">
        <p class="mono" style="margin-bottom: 16px">Panel del docente</p>
        <h1>Hola, <?php e($primerNombre); ?>.</h1>
        <p>
            <?php if (count($porCalificar) > 0) { ?>
                Tienes <?php echo count($porCalificar); ?> entrega(s) esperando calificación.
            <?php } else { ?>
                No tienes entregas pendientes por calificar.
            <?php } ?>
        </p>
    </section>

    <div class="numeros">
        <div class="numero"><strong><?php echo $datos["cursos"]; ?></strong><span>Cursos creados</span></div>
        <div class="numero"><strong><?php echo (int) $datos["publicados"]; ?></strong><span>Publicados</span></div>
        <div class="numero"><strong><?php echo $datos["alumnos"]; ?></strong><span>Alumnos inscritos</span></div>
        <div class="numero"><strong><?php echo count($porCalificar); ?></strong><span>Por calificar</span></div>
    </div>

    <div class="tarjeta">
        <div class="fila entre" style="margin-bottom: 16px">
            <h2 style="margin: 0">Entregas por calificar</h2>
            <a href="docente_cursos.php" class="boton boton-secundario boton-chico">Ir a mis cursos</a>
        </div>
        <?php if (count($porCalificar) === 0) { ?>
            <p class="texto-gris">Todo calificado. Cuando un alumno entregue una tarea aparecerá aquí.</p>
        <?php } ?>
        <?php foreach (array_slice($porCalificar, 0, 8) as $p) { ?>
            <div class="mensaje">
                <?php avatar($p["alumno"], $p["foto"]); ?>
                <div style="flex: 1">
                    <strong><?php e($p["alumno"]); ?></strong> entregó
                    <strong><?php e($p["tarea"]); ?></strong>
                    <p class="texto-gris"><?php e($p["curso"]); ?> · <?php echo fecha($p["fecha"]); ?></p>
                </div>
                <a href="tarea.php?id=<?php echo $p["tarea_id"]; ?>" class="boton boton-chico">Calificar</a>
            </div>
        <?php } ?>
    </div>

<?php
// ============== SUPER ADMIN y DIRECTOR ==============
} else {
    $datos = consulta(
        "SELECT
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'alumno')       AS alumnos,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'docente')      AS docentes,
            (SELECT COUNT(*) FROM cursos   WHERE estado = 'publicado') AS cursos,
            (SELECT COUNT(*) FROM inscripciones)                       AS inscripciones,
            (SELECT ROUND(AVG(calificacion)) FROM entregas)            AS promedio"
    )->fetch_assoc();

    // Gráfica 1: usuarios por rol
    $porRol = ["super_admin" => 0, "director" => 0, "docente" => 0, "alumno" => 0];
    foreach (consulta("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol") as $fila) {
        $porRol[$fila["rol"]] = $fila["total"];
    }

    // Gráfica 2: los 5 cursos con más alumnos
    $populares = consulta(
        "SELECT c.titulo, COUNT(i.id) AS total
         FROM cursos c LEFT JOIN inscripciones i ON i.curso_id = c.id
         GROUP BY c.id
         ORDER BY total DESC, c.titulo
         LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);

    // Gráfica 3: inscripciones de los últimos 6 meses.
    // Primero llenamos los 6 meses con 0, luego ponemos lo que diga la base de datos.
    $porMes = [];
    for ($i = 5; $i >= 0; $i--) {
        $porMes[date("Y-m", strtotime("first day of -$i month"))] = 0;
    }
    $filas = consulta(
        "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS total
         FROM inscripciones
         WHERE fecha >= ?
         GROUP BY mes",
        [array_key_first($porMes) . "-01"]
    );
    foreach ($filas as $fila) {
        $porMes[$fila["mes"]] = $fila["total"];
    }

    // El valor más grande de cada gráfica = barra al 100%
    // (max(1, ...) evita dividir entre cero si todo está vacío)
    $maxRol = max(1, max($porRol));
    $maxPop = max([1, ...array_column($populares, "total")]);
    $maxMes = max(1, max($porMes));
?>
    <section class="banda">
        <p class="mono" style="margin-bottom: 16px">Panel de <?php echo $nombresRoles[$rol]; ?></p>
        <h1>Hola, <?php e($primerNombre); ?>.</h1>
        <p>Así va la plataforma hoy.</p>
    </section>

    <div class="numeros">
        <div class="numero"><strong><?php echo $datos["alumnos"]; ?></strong><span>Alumnos</span></div>
        <div class="numero"><strong><?php echo $datos["docentes"]; ?></strong><span>Docentes</span></div>
        <div class="numero"><strong><?php echo $datos["cursos"]; ?></strong><span>Cursos publicados</span></div>
        <div class="numero"><strong><?php echo $datos["inscripciones"]; ?></strong><span>Inscripciones</span></div>
        <div class="numero"><strong><?php echo $datos["promedio"] ?? "—"; ?></strong><span>Promedio de tareas</span></div>
    </div>

    <div class="rejilla rejilla-2">
        <div class="tarjeta">
            <h2>Inscripciones por mes</h2>
            <p class="texto-gris">Últimos 6 meses.</p>
            <div class="columnas">
                <?php foreach ($porMes as $mes => $total) { ?>
                    <div class="columna" title="<?php echo $total; ?> inscripciones">
                        <span><?php echo $total; ?></span>
                        <div style="height: <?php echo round($total * 100 / $maxMes); ?>%"></div>
                    </div>
                <?php } ?>
            </div>
            <div class="columnas-meses">
                <?php foreach (array_keys($porMes) as $mes) { ?>
                    <span><?php echo mesCorto((int) substr($mes, 5, 2)); ?></span>
                <?php } ?>
            </div>
        </div>

        <div class="tarjeta">
            <h2>Cursos más populares</h2>
            <p class="texto-gris">Por número de alumnos inscritos.</p>
            <?php if (count($populares) === 0) { ?>
                <p class="vacio">Todavía no hay cursos.</p>
            <?php } ?>
            <ul class="grafica">
                <?php foreach ($populares as $p) { ?>
                    <li title="<?php e($p["titulo"]); ?>: <?php echo $p["total"]; ?> alumnos">
                        <span class="grafica-nombre"><?php e($p["titulo"]); ?></span>
                        <span class="grafica-barra"><div style="width: <?php echo round($p["total"] * 100 / $maxPop); ?>%"></div></span>
                        <span class="grafica-valor"><?php echo $p["total"]; ?></span>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <div class="tarjeta">
            <h2>Usuarios por rol</h2>
            <p class="texto-gris">Total: <?php echo array_sum($porRol); ?> usuarios.</p>
            <ul class="grafica">
                <?php foreach ($porRol as $clave => $total) { ?>
                    <li title="<?php echo $nombresRoles[$clave]; ?>: <?php echo $total; ?>">
                        <span class="grafica-nombre"><?php echo $nombresRoles[$clave]; ?></span>
                        <span class="grafica-barra"><div style="width: <?php echo round($total * 100 / $maxRol); ?>%"></div></span>
                        <span class="grafica-valor"><?php echo $total; ?></span>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <div class="tarjeta">
            <h2>Accesos rápidos</h2>
            <p class="texto-gris">Descarga los datos para abrirlos en Excel.</p>
            <div class="fila" style="margin-top: 24px">
                <?php if ($rol === "super_admin") { ?>
                    <a href="usuarios.php" class="boton">Administrar usuarios</a>
                    <a href="exportar.php?tipo=usuarios" class="boton boton-secundario">Usuarios (Excel)</a>
                <?php } ?>
                <a href="cursos.php" class="boton <?php echo $rol === "super_admin" ? "boton-secundario" : ""; ?>">Cursos y reportes</a>
                <a href="exportar.php?tipo=cursos" class="boton boton-secundario">Cursos (Excel)</a>
            </div>
        </div>
    </div>
<?php } ?>

<?php include "includes/abajo.php"; ?>
