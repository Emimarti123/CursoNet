<?php
// Cómo se muestra cada rol en pantalla
$nombresRoles = [
    "super_admin" => "Super Admin",
    "director"    => "Director",
    "docente"     => "Docente",
    "alumno"      => "Alumno",
];

// Imprime un texto de forma segura.
// htmlspecialchars evita que alguien escriba código en un formulario
function e($texto)
{
    echo htmlspecialchars($texto ?? "");
}

// Si un campo tiene error, lo muestra en rojo debajo del campo.
// $errores es una lista como ["password" => "Las contraseñas no coinciden."]
function errorCampo($errores, $campo)
{
    if (isset($errores[$campo])) {
        echo '<small class="error-campo">' . htmlspecialchars($errores[$campo]) . '</small>';
    }
}

// Hace una consulta a la base de datos de forma segura.
// Cada ? del SQL se reemplaza, en orden, por un valor de $parametros.
// Ejemplo: consulta("SELECT * FROM cursos WHERE id = ?", [$id])
function consulta($sql, $parametros = [])
{
    global $conexion;

    $stmt = $conexion->prepare($sql);
    if (count($parametros) > 0) {
        $stmt->bind_param(str_repeat("s", count($parametros)), ...$parametros);
    }
    $stmt->execute();

    // En un SELECT regresa los resultados; en INSERT/UPDATE/DELETE regresa false
    return $stmt->get_result();
}

// Manda al usuario a otra página. Opcionalmente deja un mensaje
// que aparecerá como globito en esa página (una sola vez).
// $tipo: "exito" (verde azulado) o "error" (rojo)
function irA($pagina, $aviso = "", $tipo = "exito")
{
    if ($aviso !== "") {
        $_SESSION["aviso"]      = $aviso;
        $_SESSION["aviso_tipo"] = $tipo;
    }
    header("Location: " . $pagina);
    exit;
}

// Solo deja pasar a los roles indicados; a los demás los regresa al inicio.
// Ejemplo: permitir(["super_admin", "director"]);
function permitir($roles)
{
    if (!in_array($_SESSION["rol"], $roles)) {
        irA("panel.php", "No tienes permiso para entrar a esa página.", "error");
    }
}

// "Emiliano Martínez" -> "EM"
function iniciales($texto)
{
    $letras = "";
    foreach (explode(" ", trim($texto)) as $palabra) {
        $letras .= mb_strtoupper(mb_substr($palabra, 0, 1));
    }
    return mb_substr($letras, 0, 2);
}

// "2026-10-27 10:30:00" -> "27/10/2026"
function fecha($texto)
{
    return date("d/m/Y", strtotime($texto));
}

// Todos los temas de un curso, en orden (bloque 1 tema 1, bloque 1 tema 2...)
function temasDelCurso($cursoId)
{
    return consulta(
        "SELECT t.id, t.titulo, t.bloque_id
         FROM temas t
         JOIN bloques b ON t.bloque_id = b.id
         WHERE b.curso_id = ?
         ORDER BY b.orden, b.id, t.orden, t.id",
        [$cursoId]
    )->fetch_all(MYSQLI_ASSOC);
}

// Cuántos temas de un curso ha completado un alumno
function temasCompletados($alumnoId, $cursoId)
{
    $fila = consulta(
        "SELECT COUNT(*) AS total
         FROM temas_completados tc
         JOIN temas t   ON tc.tema_id = t.id
         JOIN bloques b ON t.bloque_id = b.id
         WHERE tc.alumno_id = ? AND b.curso_id = ?",
        [$alumnoId, $cursoId]
    )->fetch_assoc();
    return (int) $fila["total"];
}

// Porcentaje de avance de un alumno en un curso (de 0 a 100)
function avance($alumnoId, $cursoId)
{
    $total = count(temasDelCurso($cursoId));
    if ($total === 0) {
        return 0;
    }
    return round(temasCompletados($alumnoId, $cursoId) * 100 / $total);
}

// Promedio del avance de todos los alumnos inscritos en un curso
function avancePromedio($cursoId)
{
    $alumnos = consulta("SELECT alumno_id FROM inscripciones WHERE curso_id = ?", [$cursoId])
        ->fetch_all(MYSQLI_ASSOC);
    if (count($alumnos) === 0) {
        return 0;
    }

    $suma = 0;
    foreach ($alumnos as $alumno) {
        $suma += avance($alumno["alumno_id"], $cursoId);
    }
    return round($suma / count($alumnos));
}

// =====================================================
// Piezas visuales
// =====================================================

// Logo de CursoNet: dos marcos encimados (dos "páginas" conectadas)
// y un punto. currentColor = toma el color del texto de alrededor.
function logo()
{
    echo '<svg class="logo-marca" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true">
        <rect x="2.75" y="8.75" width="20.5" height="20.5" fill="#fae59b" stroke="currentColor" stroke-width="1.5"/>
        <rect x="8.75" y="2.75" width="20.5" height="20.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
        <circle cx="19" cy="13" r="2.5" fill="currentColor"/>
    </svg>';
}

// Cuadro con la foto de la persona; si no tiene foto, sus iniciales.
// $clase sirve para hacerlo más grande (ej. "avatar-grande").
function avatar($nombre, $foto = null, $clase = "")
{
    if ($foto) {
        echo '<img class="avatar ' . $clase . '" src="subidas/' . htmlspecialchars($foto) . '" alt="">';
    } else {
        echo '<div class="avatar ' . $clase . '">' . htmlspecialchars(iniciales($nombre)) . '</div>';
    }
}

// Dibujo de líneas para la portada de un curso.
// Cada curso recibe uno de 4 dibujos según su id, así no todos se ven iguales.
function portada($cursoId)
{
    $dibujos = [
        // 0: círculo partido a la mitad
        '<circle cx="150" cy="60" r="38" fill="#fff" stroke="#0d0129" stroke-width="1.5"/>
         <path d="M150 22 A38 38 0 0 1 150 98 Z" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
         <line x1="60" y1="98" x2="240" y2="98" stroke="#0d0129" stroke-width="1.5"/>',
        // 1: gráfica de barras
        '<rect x="96"  y="62" width="20" height="36" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <rect x="124" y="40" width="20" height="58" fill="#19615c" stroke="#0d0129" stroke-width="1.5"/>
         <rect x="152" y="24" width="20" height="74" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
         <rect x="180" y="50" width="20" height="48" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <line x1="80" y1="98" x2="220" y2="98" stroke="#0d0129" stroke-width="1.5"/>',
        // 2: cuadrícula con un cuadro amarillo
        '<rect x="106" y="20" width="26" height="26" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <rect x="137" y="20" width="26" height="26" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
         <rect x="168" y="20" width="26" height="26" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <rect x="106" y="51" width="26" height="26" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <rect x="137" y="51" width="26" height="26" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <rect x="168" y="51" width="26" height="26" fill="#19615c" stroke="#0d0129" stroke-width="1.5"/>
         <line x1="106" y1="98" x2="194" y2="98" stroke="#0d0129" stroke-width="1.5"/>',
        // 3: hojas encimadas
        '<rect x="112" y="30" width="64" height="72" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
         <rect x="126" y="18" width="64" height="72" fill="#fff"    stroke="#0d0129" stroke-width="1.5"/>
         <line x1="136" y1="34" x2="180" y2="34" stroke="#0d0129" stroke-width="1.5"/>
         <line x1="136" y1="46" x2="180" y2="46" stroke="#0d0129" stroke-width="1.5"/>
         <line x1="136" y1="58" x2="164" y2="58" stroke="#0d0129" stroke-width="1.5"/>',
    ];
    // Puntitos amarillos de adorno
    $puntos = '<circle cx="62" cy="32" r="4" fill="#fae59b"/><circle cx="244" cy="44" r="3" fill="#19615c"/>
               <circle cx="230" cy="20" r="4" fill="#fae59b"/>';

    echo '<svg class="curso-portada" viewBox="0 0 300 120" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
        . $dibujos[$cursoId % 4] . $puntos . '</svg>';
}

// Estrellas de calificación: 4.3 -> ★★★★☆
function estrellas($promedio)
{
    $llenas = (int) round($promedio);
    echo '<span class="estrellas" aria-label="' . round($promedio, 1) . ' de 5 estrellas">'
        . str_repeat("★", $llenas) . '<span class="estrellas-vacias">' . str_repeat("★", 5 - $llenas) . '</span>'
        . '</span>';
}

// =====================================================
// Notificaciones (la campanita)
// =====================================================

// Le manda una notificación a una persona
function notificar($usuarioId, $mensaje, $enlace = "")
{
    consulta("INSERT INTO notificaciones (usuario_id, mensaje, enlace) VALUES (?, ?, ?)",
        [$usuarioId, mb_substr($mensaje, 0, 255), $enlace]);
}

// Le manda la misma notificación a todos los alumnos inscritos en un curso
function notificarAlumnos($cursoId, $mensaje, $enlace = "")
{
    $alumnos = consulta("SELECT alumno_id FROM inscripciones WHERE curso_id = ?", [$cursoId]);
    foreach ($alumnos as $a) {
        notificar($a["alumno_id"], $mensaje, $enlace);
    }
}

// =====================================================
// Subir archivos (fotos de perfil y entregas de tareas)
// =====================================================

// Guarda el archivo que llegó en el campo $campo dentro de subidas/$carpeta.
// Regresa ["ruta" => "carpeta/nombre.ext", "error" => ""].
// Si no se eligió archivo, ruta y error quedan vacíos.
function subirArchivo($campo, $carpeta, $extensiones, $maxMB = 2)
{
    $archivo = $_FILES[$campo] ?? null;
    if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
        return ["ruta" => "", "error" => ""];
    }
    if ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["size"] > $maxMB * 1024 * 1024) {
        return ["ruta" => "", "error" => "El archivo pesa más de $maxMB MB."];
    }
    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        return ["ruta" => "", "error" => "No se pudo subir el archivo. Intenta otra vez."];
    }

    // Solo dejamos pasar ciertas extensiones (así nadie sube un .php peligroso)
    $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones)) {
        return ["ruta" => "", "error" => "Tipo de archivo no permitido. Usa: " . implode(", ", $extensiones) . "."];
    }

    // Nombre al azar para que no se repita ni se pueda adivinar
    $nombre = bin2hex(random_bytes(8)) . "." . $extension;
    $destino = __DIR__ . "/../subidas/" . $carpeta;
    if (!is_dir($destino)) {
        mkdir($destino, 0775, true);
    }
    move_uploaded_file($archivo["tmp_name"], $destino . "/" . $nombre);

    return ["ruta" => $carpeta . "/" . $nombre, "error" => ""];
}

// Borra un archivo de subidas/ (por ejemplo, la foto vieja)
function borrarArchivo($ruta)
{
    $completa = __DIR__ . "/../subidas/" . $ruta;
    if ($ruta && is_file($completa)) {
        unlink($completa);
    }
}

// =====================================================
// Paginación (listas largas divididas en páginas)
// =====================================================

// Con el total de elementos calcula en qué página estamos.
// Regresa ["actual" => 2, "paginas" => 5, "desde" => 20, "por" => 20]
function paginar($total, $porPagina = 20)
{
    $paginas = max(1, (int) ceil($total / $porPagina));
    $actual  = min(max(1, (int) ($_GET["pagina"] ?? 1)), $paginas);
    return ["actual" => $actual, "paginas" => $paginas, "desde" => ($actual - 1) * $porPagina, "por" => $porPagina];
}

// Dibuja los botones « 1 2 3 » respetando la búsqueda actual (?buscar=...)
function enlacesPaginas($p)
{
    if ($p["paginas"] <= 1) {
        return;
    }
    echo '<nav class="paginas" aria-label="Páginas">';
    for ($i = 1; $i <= $p["paginas"]; $i++) {
        $url = "?" . http_build_query(array_merge($_GET, ["pagina" => $i]));
        $clase = $i === $p["actual"] ? ' class="activa" aria-current="page"' : "";
        echo '<a href="' . htmlspecialchars($url) . '"' . $clase . '>' . $i . '</a>';
    }
    echo '</nav>';
}

// =====================================================
// Datos de un curso
// =====================================================

// Promedio de estrellas y número de reseñas de un curso
function resumenResenas($cursoId)
{
    return consulta("SELECT COALESCE(AVG(estrellas), 0) AS promedio, COUNT(*) AS total FROM resenas WHERE curso_id = ?",
        [$cursoId])->fetch_assoc();
}

// Promedio de calificaciones de tareas de un alumno en un curso (o null si no tiene)
function promedioTareas($alumnoId, $cursoId)
{
    $fila = consulta(
        "SELECT AVG(e.calificacion) AS promedio
         FROM entregas e JOIN tareas t ON e.tarea_id = t.id
         WHERE e.alumno_id = ? AND t.curso_id = ? AND e.calificacion IS NOT NULL",
        [$alumnoId, $cursoId]
    )->fetch_assoc();
    return $fila["promedio"] === null ? null : round($fila["promedio"]);
}

// Revisa si la persona puede entrar a las secciones de un curso
// (foro, tareas, avisos). Si no puede, la manda a otra página.
//   - Alumno: solo si está inscrito.
//   - Docente: solo si el curso es suyo.
//   - Super Admin y Director: pueden ver todo.
// Regresa el curso y si quien entra es el docente del curso.
function accesoCurso($cursoId)
{
    global $miId, $rol;

    $curso = consulta("SELECT * FROM cursos WHERE id = ?", [$cursoId])->fetch_assoc();
    if (!$curso) {
        irA("panel.php", "Ese curso no existe.", "error");
    }

    $esDocente = $rol === "docente" && $curso["docente_id"] == $miId;

    if ($rol === "alumno") {
        $inscrito = consulta("SELECT 1 FROM inscripciones WHERE alumno_id = ? AND curso_id = ?", [$miId, $cursoId])
            ->num_rows > 0;
        if (!$inscrito) {
            irA("catalogo.php", "Primero inscríbete a ese curso.", "error");
        }
    }
    if ($rol === "docente" && !$esDocente) {
        irA("panel.php", "Ese curso es de otro docente.", "error");
    }

    return [$curso, $esDocente];
}

// A dónde regresa el botón "Volver" de las secciones de un curso,
// y qué opción del menú se marca, según el rol
function volverAlCurso($cursoId)
{
    global $rol;
    if ($rol === "alumno") {
        return ["curso.php?id=" . $cursoId, "mis_cursos.php"];
    }
    if ($rol === "docente") {
        return ["editar_curso.php?id=" . $cursoId, "docente_cursos.php"];
    }
    return ["reporte.php?id=" . $cursoId, "cursos.php"];
}

// Dibujito de líneas para cuando una lista está vacía (una hoja con un punto)
function dibujoVacio()
{
    echo '<svg width="120" height="80" viewBox="0 0 120 80" aria-hidden="true">
        <rect x="30" y="10" width="52" height="62" fill="#fff" stroke="#0d0129" stroke-width="1.5"/>
        <rect x="38" y="4" width="52" height="62" fill="#fffcf7" stroke="#0d0129" stroke-width="1.5"/>
        <line x1="46" y1="20" x2="82" y2="20" stroke="#0d0129" stroke-width="1.5"/>
        <line x1="46" y1="32" x2="74" y2="32" stroke="#0d0129" stroke-width="1.5"/>
        <circle cx="96" cy="58" r="8" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
        <circle cx="18" cy="24" r="4" fill="#fae59b"/>
    </svg>';
}

// Nombre corto de un mes: 3 -> "mar"
function mesCorto($numero)
{
    $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    return $meses[$numero - 1];
}

// =====================================================
// Secciones de una lección
// =====================================================

// Los tipos de sección que existen: "tipo" => [ícono, nombre]
$tiposSeccion = [
    "texto"  => ["📝", "Texto"],
    "codigo" => ["💻", "Código"],
    "video"  => ["🎬", "Video de YouTube"],
    "imagen" => ["🖼️", "Imagen"],
    "enlace" => ["🔗", "Enlace"],
    "nota"   => ["💡", "Nota importante"],
];

// Saca el código de un video de YouTube de su dirección.
// "https://www.youtube.com/watch?v=abc123DEF45" -> "abc123DEF45"  (null si no es de YouTube)
function idYoutube($url)
{
    if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
        return $m[1];
    }
    return null;
}

// Dibuja una sección tal como la ve el alumno
function mostrarSeccion($s)
{
    $titulo    = htmlspecialchars($s["titulo"] ?? "");
    $contenido = htmlspecialchars($s["contenido"]);

    if ($s["tipo"] === "texto") {
        echo '<div class="parte parte-texto">';
        if ($titulo !== "") {
            echo "<h3>$titulo</h3>";
        }
        // "texto-docente" respeta saltos de línea y espacios
        echo '<div class="texto-docente">' . $contenido . '</div></div>';
    }

    if ($s["tipo"] === "codigo") {
        echo '<figure class="parte parte-codigo">';
        if ($titulo !== "") {
            echo "<figcaption>$titulo</figcaption>";
        }
        echo "<pre><code>$contenido</code></pre></figure>";
    }

    if ($s["tipo"] === "nota") {
        echo '<aside class="parte parte-nota"><strong>💡 ' . ($titulo !== "" ? $titulo : "Importante") . '</strong>';
        echo '<div class="texto-docente">' . $contenido . '</div></aside>';
    }

    if ($s["tipo"] === "video") {
        $id = idYoutube($s["contenido"]);
        echo '<figure class="parte parte-video">';
        if ($id) {
            // youtube-nocookie: versión de YouTube que no guarda cookies del alumno
            echo '<div class="video-marco"><iframe src="https://www.youtube-nocookie.com/embed/' . $id . '"
                  title="' . ($titulo !== "" ? $titulo : "Video") . '" loading="lazy" allowfullscreen
                  allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe></div>';
        }
        if ($titulo !== "") {
            echo "<figcaption>$titulo</figcaption>";
        }
        echo '</figure>';
    }

    if ($s["tipo"] === "imagen") {
        echo '<figure class="parte parte-imagen"><img src="subidas/' . $contenido . '" alt="' . $titulo . '" loading="lazy">';
        if ($titulo !== "") {
            echo "<figcaption>$titulo</figcaption>";
        }
        echo '</figure>';
    }

    if ($s["tipo"] === "enlace") {
        echo '<a class="parte parte-enlace" href="' . $contenido . '" target="_blank" rel="noopener">
              <span>🔗 ' . $titulo . '</span><small>' . $contenido . '</small><b aria-hidden="true">↗</b></a>';
    }
}

// =====================================================
// Ordenar (flechas ↑ ↓)
// =====================================================

// Sube o baja un elemento dentro de su lista y vuelve a numerar todo 1, 2, 3...
// $tabla: "bloques", "temas", "secciones" o "preguntas"
// $padreId: el curso, módulo o lección al que pertenece (así no se mueve algo ajeno)
function mover($tabla, $padreId, $id, $direccion)
{
    // De quién depende cada tabla (solo se aceptan estas tablas)
    $padres = ["bloques" => "curso_id", "temas" => "bloque_id", "secciones" => "tema_id", "preguntas" => "tema_id"];
    if (!isset($padres[$tabla])) {
        return;
    }
    $campo = $padres[$tabla];

    $ids = array_column(
        consulta("SELECT id FROM $tabla WHERE $campo = ? ORDER BY orden, id", [$padreId])->fetch_all(MYSQLI_ASSOC),
        "id"
    );
    $posicion = array_search((int) $id, $ids);
    $vecino   = $direccion === "subir" ? $posicion - 1 : $posicion + 1;
    if ($posicion === false || !isset($ids[$vecino])) {
        return;   // no existe o ya está hasta arriba / hasta abajo
    }

    // Intercambiamos con el vecino y guardamos el nuevo orden
    [$ids[$posicion], $ids[$vecino]] = [$ids[$vecino], $ids[$posicion]];
    foreach ($ids as $numero => $cadaId) {
        consulta("UPDATE $tabla SET orden = ? WHERE id = ?", [$numero + 1, $cadaId]);
    }
}

// Siguiente número de orden para agregar algo al final de una lista
function siguienteOrden($tabla, $campo, $padreId)
{
    return consulta("SELECT COALESCE(MAX(orden), 0) + 1 AS orden FROM $tabla WHERE $campo = ?", [$padreId])
        ->fetch_assoc()["orden"];
}

// =====================================================
// Borrar archivos antes de borrar lecciones, módulos o cursos
// (la base de datos borra las filas sola, pero no los archivos)
// =====================================================

// $condicion usa los alias s (sección), t (lección) y b (módulo). Ej: "b.curso_id = ?"
function borrarImagenesSecciones($condicion, $parametros)
{
    $imagenes = consulta(
        "SELECT s.contenido FROM secciones s
         JOIN temas t   ON s.tema_id = t.id
         JOIN bloques b ON t.bloque_id = b.id
         WHERE s.tipo = 'imagen' AND $condicion",
        $parametros
    );
    foreach ($imagenes as $img) {
        borrarArchivo($img["contenido"]);
    }
}

// Todo lo subido a un curso: imágenes de lecciones y archivos de tareas
function borrarArchivosCurso($cursoId)
{
    borrarImagenesSecciones("b.curso_id = ?", [$cursoId]);
    $entregas = consulta("SELECT e.archivo FROM entregas e JOIN tareas t ON e.tarea_id = t.id WHERE t.curso_id = ?", [$cursoId]);
    foreach ($entregas as $e) {
        borrarArchivo($e["archivo"]);
    }
}

// =====================================================
// Revisión antes de publicar
// =====================================================

// Lista de cosas que se revisan en un curso.
// Cada una: ["ok" => true/false, "texto" => "...", "obligatorio" => true/false]
function revisionCurso($curso)
{
    $modulos = consulta(
        "SELECT b.titulo, (SELECT COUNT(*) FROM temas t WHERE t.bloque_id = b.id) AS lecciones
         FROM bloques b WHERE b.curso_id = ? ORDER BY b.orden, b.id",
        [$curso["id"]]
    )->fetch_all(MYSQLI_ASSOC);

    $vacias = consulta(
        "SELECT t.titulo FROM temas t JOIN bloques b ON t.bloque_id = b.id
         WHERE b.curso_id = ? AND NOT EXISTS (SELECT 1 FROM secciones s WHERE s.tema_id = t.id)
         ORDER BY b.orden, t.orden",
        [$curso["id"]]
    )->fetch_all(MYSQLI_ASSOC);

    $totalLecciones = array_sum(array_column($modulos, "lecciones"));
    $modulosVacios  = array_filter($modulos, fn($m) => $m["lecciones"] == 0);

    return [
        ["ok" => $totalLecciones > 0, "obligatorio" => true,
         "texto" => "El curso tiene al menos una lección"],
        ["ok" => count($modulosVacios) === 0, "obligatorio" => true,
         "texto" => count($modulosVacios) === 0 ? "Todos los módulos tienen lecciones"
             : "Módulos sin lecciones: " . implode(", ", array_column($modulosVacios, "titulo"))],
        ["ok" => count($vacias) === 0, "obligatorio" => true,
         "texto" => count($vacias) === 0 ? "Todas las lecciones tienen contenido"
             : "Lecciones sin contenido: " . implode(", ", array_column($vacias, "titulo"))],
        ["ok" => trim($curso["descripcion"] ?? "") !== "", "obligatorio" => false,
         "texto" => "Tiene descripción (ayuda a que los alumnos se inscriban)"],
        ["ok" => $curso["categoria_id"] !== null, "obligatorio" => false,
         "texto" => "Tiene categoría (aparece en los filtros del catálogo)"],
    ];
}

// ¿Pasa todo lo obligatorio de la revisión?
function listoParaPublicar($revision)
{
    foreach ($revision as $punto) {
        if ($punto["obligatorio"] && !$punto["ok"]) {
            return false;
        }
    }
    return true;
}

// Resultados de cuestionarios de un alumno en un curso:
// ["contestados" => 3, "cuestionarios" => 5, "porcentaje" => 80]  (porcentaje = aciertos de lo contestado)
function resultadoCuestionarios($alumnoId, $cursoId)
{
    $fila = consulta(
        "SELECT
            (SELECT COUNT(DISTINCT p.tema_id) FROM preguntas p
             JOIN temas t ON p.tema_id = t.id JOIN bloques b ON t.bloque_id = b.id
             WHERE b.curso_id = ?) AS cuestionarios,
            COUNT(it.tema_id) AS contestados,
            SUM(it.aciertos)  AS aciertos,
            SUM(it.total)     AS total
         FROM intentos it
         JOIN temas t   ON it.tema_id = t.id
         JOIN bloques b ON t.bloque_id = b.id
         WHERE it.alumno_id = ? AND b.curso_id = ?",
        [$cursoId, $alumnoId, $cursoId]
    )->fetch_assoc();

    return [
        "contestados"   => (int) $fila["contestados"],
        "cuestionarios" => (int) $fila["cuestionarios"],
        "porcentaje"    => $fila["total"] > 0 ? round($fila["aciertos"] * 100 / $fila["total"]) : null,
    ];
}
