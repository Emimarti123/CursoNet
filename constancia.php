<?php
require "includes/proteger.php";
permitir(["alumno"]);

// Solo se entrega constancia si el alumno terminó el 100% del curso
$cursoId = (int) ($_GET["curso"] ?? 0);
$curso   = consulta(
    "SELECT c.*, u.nombre AS docente
     FROM cursos c
     JOIN usuarios u      ON c.docente_id = u.id
     JOIN inscripciones i ON i.curso_id = c.id AND i.alumno_id = ?
     WHERE c.id = ?",
    [$miId, $cursoId]
)->fetch_assoc();

if (!$curso || avance($miId, $cursoId) < 100) {
    irA("mis_cursos.php", "La constancia se obtiene al completar todas las lecciones del curso.", "error");
}

// Fecha de término = el día que completó su último tema
$termino = consulta(
    "SELECT MAX(tc.fecha) AS fecha
     FROM temas_completados tc
     JOIN temas t   ON tc.tema_id = t.id
     JOIN bloques b ON t.bloque_id = b.id
     WHERE tc.alumno_id = ? AND b.curso_id = ?",
    [$miId, $cursoId]
)->fetch_assoc()["fecha"];

$totalTemas = count(temasDelCurso($cursoId));
$promedio   = promedioTareas($miId, $cursoId);

// Folio: un código para identificar la constancia, ej. "CN-0003-0012"
$folio = sprintf("CN-%04d-%04d", $cursoId, $miId);

$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio",
          "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
$fechaLarga = date("j", strtotime($termino)) . " de " . $meses[date("n", strtotime($termino)) - 1]
    . " de " . date("Y", strtotime($termino));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Constancia · <?php e($curso["titulo"]); ?> · CursoNet</title>
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        /* Estilos solo de esta página */
        .constancia-pagina { max-width: 1000px; margin: 0 auto; padding: 40px 16px; }
        .constancia {
            position: relative;
            background: var(--blanco);
            border: 1px solid var(--tinta);
            outline: 1px solid var(--tinta);
            outline-offset: -12px;          /* doble marco, como un diploma */
            padding: 80px 64px;
            text-align: center;
            overflow: hidden;
        }
        .constancia h1 { font-size: 56px; margin: 24px 0; }
        .constancia .nombre {
            font-size: 40px;
            font-weight: 700;
            border-bottom: 1px solid var(--tinta);
            display: inline-block;
            padding: 0 24px 8px;
            margin: 16px 0 24px;
        }
        .constancia .lectura { margin: 0 auto; font-size: 20px; }
        .constancia-pie {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            margin-top: 64px;
            text-align: center;
        }
        .firma { border-top: 1px solid var(--tinta); padding-top: 8px; }
        .esquina { position: absolute; width: 140px; }
        .esquina-1 { top: 24px; left: 24px; }
        .esquina-2 { bottom: 24px; right: 24px; transform: rotate(180deg); }
        @media (max-width: 700px) {
            .constancia { padding: 48px 24px; }
            .constancia h1 { font-size: 40px; }
            .constancia .nombre { font-size: 28px; }
            .constancia-pie { grid-template-columns: 1fr; }
            .esquina { display: none; }
        }
        @media print {
            @page { size: landscape; margin: 10mm; }
            .constancia-pagina { padding: 0; max-width: none; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="constancia-pagina">

    <div class="fila entre no-imprimir" style="margin-bottom: 24px">
        <a href="mis_cursos.php">← Volver a mis cursos</a>
        <button type="button" class="boton" onclick="window.print()">Imprimir o guardar como PDF</button>
    </div>

    <div class="constancia">
        <!-- Adornos de líneas en dos esquinas -->
        <svg class="esquina esquina-1" viewBox="0 0 140 80" aria-hidden="true">
            <rect x="12" y="12" width="40" height="40" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
            <rect x="28" y="28" width="40" height="40" fill="none" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="96" cy="24" r="5" fill="#19615c"/>
            <circle cx="120" cy="40" r="3" fill="#fae59b"/>
        </svg>
        <svg class="esquina esquina-2" viewBox="0 0 140 80" aria-hidden="true">
            <rect x="12" y="12" width="40" height="40" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
            <rect x="28" y="28" width="40" height="40" fill="none" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="96" cy="24" r="5" fill="#19615c"/>
        </svg>

        <span class="logo" style="justify-content: center"><?php logo(); ?> CursoNet</span>
        <h1>Constancia de terminación</h1>
        <p class="mono texto-gris">Se otorga la presente a</p>
        <div class="nombre"><?php e($_SESSION["nombre"]); ?></div>
        <p class="lectura">
            por haber completado satisfactoriamente el curso
            <strong>«<?php e($curso["titulo"]); ?>»</strong>,
            con <?php echo $totalTemas; ?> lecciones estudiadas<?php if ($promedio !== null) { ?>
            y un promedio de <?php echo $promedio; ?> en sus tareas<?php } ?>.
        </p>

        <div class="constancia-pie">
            <div>
                <div class="firma"><?php echo $fechaLarga; ?></div>
                <small class="mono texto-gris">Fecha</small>
            </div>
            <div>
                <div class="firma"><?php e($curso["docente"]); ?></div>
                <small class="mono texto-gris">Docente</small>
            </div>
            <div>
                <div class="firma mono"><?php echo $folio; ?></div>
                <small class="mono texto-gris">Folio</small>
            </div>
        </div>
    </div>
</div>
</body>
</html>
