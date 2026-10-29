<?php
// Parte de arriba del login y del registro:
// a la izquierda la banda verde azulado con el mensaje, a la derecha el formulario.
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
<?php include "includes/globo.php"; ?>
<div class="acceso">

    <section class="acceso-marca">
        <span class="logo"><?php logo(); ?> CursoNet</span>

        <div>
            <h1>Aprende algo nuevo, a tu ritmo.</h1>
            <p>Cursos creados por docentes, con tareas, cuestionarios y tu avance siempre a la vista.</p>
        </div>

        <!-- Dibujo de líneas: una ventana con un curso, una barra de avance y una constancia -->
        <svg class="acceso-dibujo" viewBox="0 0 420 220" aria-hidden="true">
            <rect x="1" y="1" width="300" height="190" fill="#fffcf7" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="1" y1="28" x2="301" y2="28" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="16" cy="14" r="4" fill="#fae59b" stroke="#0d0129"/>
            <circle cx="30" cy="14" r="4" fill="#fff" stroke="#0d0129"/>
            <rect x="20" y="48" width="120" height="70" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="80" cy="83" r="18" fill="#fff" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="160" y1="54" x2="280" y2="54" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="160" y1="72" x2="260" y2="72" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="160" y1="90" x2="240" y2="90" stroke="#0d0129" stroke-width="1.5"/>
            <rect x="20" y="140" width="260" height="14" fill="#fff" stroke="#0d0129" stroke-width="1.5"/>
            <rect x="20" y="140" width="170" height="14" fill="#19615c" stroke="#0d0129" stroke-width="1.5"/>
            <rect x="250" y="96" width="160" height="116" fill="#fff" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="270" y1="122" x2="390" y2="122" stroke="#0d0129" stroke-width="1.5"/>
            <line x1="270" y1="138" x2="360" y2="138" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="370" cy="182" r="16" fill="#fae59b" stroke="#0d0129" stroke-width="1.5"/>
            <path d="M362 182 l6 6 l10 -12" fill="none" stroke="#0d0129" stroke-width="1.5"/>
            <circle cx="330" cy="40" r="5" fill="#fae59b"/>
            <circle cx="400" cy="70" r="4" fill="#fffcf7"/>
            <circle cx="360" cy="18" r="3" fill="#fae59b"/>
        </svg>
    </section>

    <section class="acceso-formulario">
        <div class="tarjeta">
