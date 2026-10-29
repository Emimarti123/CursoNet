<?php
// Descarga datos como archivo .csv, que se abre directo en Excel.
//   exportar.php?tipo=usuarios        (Super Admin)
//   exportar.php?tipo=cursos          (Super Admin y Director)
//   exportar.php?tipo=reporte&id=5    (Super Admin, Director y el docente del curso)
require "includes/proteger.php";

$tipo  = $_GET["tipo"] ?? "";
$filas = [];   // la primera fila son los títulos de las columnas

if ($tipo === "usuarios") {
    permitir(["super_admin"]);
    $archivo = "usuarios";
    $filas[] = ["Nombre", "Correo", "Rol", "Estado", "Fecha de alta"];
    foreach (consulta("SELECT * FROM usuarios ORDER BY nombre") as $u) {
        $filas[] = [$u["nombre"], $u["email"], $nombresRoles[$u["rol"]], $u["activo"] ? "Activo" : "Inactivo", fecha($u["created_at"])];
    }

} elseif ($tipo === "cursos") {
    permitir(["super_admin", "director"]);
    $archivo = "cursos";
    $filas[] = ["Curso", "Categoría", "Docente", "Estado", "Alumnos", "Avance promedio (%)", "Estrellas", "Reseñas"];
    $cursos = consulta(
        "SELECT c.id, c.titulo, c.estado, u.nombre AS docente, cat.nombre AS categoria,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) AS alumnos
         FROM cursos c
         JOIN usuarios u          ON c.docente_id = u.id
         LEFT JOIN categorias cat ON c.categoria_id = cat.id
         ORDER BY c.titulo"
    );
    foreach ($cursos as $c) {
        $resumen = resumenResenas($c["id"]);
        $filas[] = [$c["titulo"], $c["categoria"] ?? "", $c["docente"], ucfirst($c["estado"]), $c["alumnos"],
            avancePromedio($c["id"]), $resumen["total"] > 0 ? round($resumen["promedio"], 1) : "", $resumen["total"]];
    }

} elseif ($tipo === "reporte") {
    $cursoId = (int) ($_GET["id"] ?? 0);
    $curso   = consulta("SELECT * FROM cursos WHERE id = ?", [$cursoId])->fetch_assoc();
    if (!$curso || $rol === "alumno" || ($rol === "docente" && $curso["docente_id"] != $miId)) {
        irA("panel.php", "No tienes acceso a ese reporte.", "error");
    }
    // Nombre del archivo sin acentos ni espacios, ej. "reporte-desarrollo-web-desde-cero"
    $archivo = "reporte-" . trim(preg_replace("/[^a-z0-9]+/", "-", strtolower(iconv("UTF-8", "ASCII//TRANSLIT", $curso["titulo"]))), "-");
    $totalTemas = count(temasDelCurso($cursoId));
    $filas[] = ["Alumno", "Correo", "Inscrito desde", "Lecciones completadas", "Total de lecciones", "Avance (%)",
                "Cuestionarios contestados", "Aciertos en cuestionarios (%)", "Promedio de tareas"];
    $alumnos = consulta(
        "SELECT u.id, u.nombre, u.email, i.fecha FROM inscripciones i
         JOIN usuarios u ON i.alumno_id = u.id
         WHERE i.curso_id = ? ORDER BY u.nombre",
        [$cursoId]
    );
    foreach ($alumnos as $a) {
        $cu = resultadoCuestionarios($a["id"], $cursoId);
        $filas[] = [$a["nombre"], $a["email"], fecha($a["fecha"]), temasCompletados($a["id"], $cursoId), $totalTemas,
            avance($a["id"], $cursoId), $cu["contestados"] . " de " . $cu["cuestionarios"], $cu["porcentaje"] ?? "",
            promedioTareas($a["id"], $cursoId) ?? ""];
    }

} else {
    irA("panel.php", "Ese reporte no existe.", "error");
}

// Le decimos al navegador que esto es un archivo para descargar, no una página
header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="' . $archivo . "-" . date("Y-m-d") . '.csv"');

$salida = fopen("php://output", "w");
fwrite($salida, "\xEF\xBB\xBF");   // marca especial para que Excel lea bien los acentos
foreach ($filas as $fila) {
    fputcsv($salida, $fila, ",", '"', "");
}
fclose($salida);
