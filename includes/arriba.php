<?php
// Parte de arriba de todas las páginas privadas: encabezado HTML + menú lateral.
// Cada página define $titulo antes de incluirlo. Opcionalmente define
// $menuActivo para marcar una opción del menú (si no, se marca la página actual).

// Opciones del menú según el rol: "archivo" => "texto"
$menus = [
    "super_admin" => ["panel.php" => "Inicio", "usuarios.php" => "Usuarios", "cursos.php" => "Cursos"],
    "director"    => ["panel.php" => "Inicio", "cursos.php" => "Cursos y reportes"],
    "docente"     => ["panel.php" => "Inicio", "docente_cursos.php" => "Mis cursos"],
    "alumno"      => ["panel.php" => "Inicio", "catalogo.php" => "Explorar cursos",
                      "mis_cursos.php" => "Mis cursos", "tareas.php" => "Mis tareas"],
];

$menuActivo = $menuActivo ?? basename($_SERVER["PHP_SELF"]);   // ej. "panel.php"
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php e($titulo); ?> · CursoNet</title>
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
    <script src="js/validar.js" defer></script>
</head>
<body>
<div class="app">

    <aside class="lateral no-imprimir" id="lateral">
        <div class="lateral-arriba">
            <a href="panel.php" class="logo"><?php logo(); ?> CursoNet</a>
            <!-- Solo se ve en el celular: abre y cierra el menú -->
            <button type="button" class="boton-menu" id="boton-menu" aria-label="Abrir menú" aria-expanded="false">☰</button>
        </div>

        <nav class="menu">
            <?php foreach ($menus[$rol] as $archivo => $texto) { ?>
                <a href="<?php echo $archivo; ?>"
                   class="<?php echo $archivo === $menuActivo ? "activo" : ""; ?>">
                    <?php echo $texto; ?>
                </a>
            <?php } ?>
            <a href="notificaciones.php" class="<?php echo $menuActivo === "notificaciones.php" ? "activo" : ""; ?>">
                Notificaciones
                <?php if ($sinLeer > 0) { ?>
                    <span class="contador" aria-label="<?php echo $sinLeer; ?> sin leer"><?php echo $sinLeer; ?></span>
                <?php } ?>
            </a>
            <a href="perfil.php" class="<?php echo $menuActivo === "perfil.php" ? "activo" : ""; ?>">Mi perfil</a>
            <a href="logout.php" class="cerrar-sesion" id="abrir-salir">Cerrar sesión</a>
        </nav>

        <a href="perfil.php" class="usuario-menu">
            <?php avatar($_SESSION["nombre"], $miFoto); ?>
            <div>
                <strong><?php e($_SESSION["nombre"]); ?></strong>
                <small><?php echo $nombresRoles[$rol]; ?></small>
            </div>
        </a>
    </aside>

    <!-- Ventanita que pregunta antes de cerrar sesión (la abre js/validar.js) -->
    <dialog class="confirmar" id="confirmar-salir">
        <h2>¿Estás seguro de cerrar tu sesión?</h2>
        <p class="texto-gris">Tendrás que volver a escribir tu correo y contraseña para entrar.</p>
        <div class="confirmar-botones">
            <button type="button" class="boton" id="seguir-navegando" autofocus>Seguir navegando</button>
            <a href="logout.php" class="boton boton-pared">Cerrar sesión</a>
        </div>
    </dialog>

    <!-- Ventanita para confirmar antes de borrar algo.
         Se usa en cualquier formulario con data-confirmar="¿Seguro...?" -->
    <dialog class="confirmar" id="confirmar-accion">
        <h2 id="confirmar-texto">¿Seguro?</h2>
        <p class="texto-gris" id="confirmar-detalle">Esta acción no se puede deshacer.</p>
        <div class="confirmar-botones">
            <button type="button" class="boton boton-secundario" id="confirmar-no" autofocus>Cancelar</button>
            <button type="button" class="boton boton-peligro" id="confirmar-si">Sí, continuar</button>
        </div>
    </dialog>

    <main class="contenido">

    <?php include "includes/globo.php"; ?>
