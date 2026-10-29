<?php
require "includes/proteger.php";
permitir(["super_admin"]);

// Datos del formulario "Nuevo usuario" (para volver a mostrarlos si hay errores)
$nuevo   = ["nombre" => "", "email" => "", "password" => "", "rol" => "alumno"];
$errores = [];

// ---------- Acciones de los formularios ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"];

    // Crear un usuario nuevo con cualquier rol
    if ($accion === "crear") {
        $nuevo = [
            "nombre"   => trim($_POST["nombre"]),
            "email"    => trim($_POST["email"]),
            "password" => $_POST["password"],
            "rol"      => $_POST["rol"],
        ];

        if ($nuevo["nombre"] === "") {
            $errores["nombre"] = "Escribe el nombre.";
        } elseif (mb_strlen($nuevo["nombre"]) > 100) {
            $errores["nombre"] = "El nombre puede tener máximo 100 caracteres.";
        }
        if (!filter_var($nuevo["email"], FILTER_VALIDATE_EMAIL) || mb_strlen($nuevo["email"]) > 150) {
            $errores["email"] = "Escribe un correo válido (ej. nombre@correo.com).";
        }
        if (mb_strlen($nuevo["password"]) < 6) {
            $errores["password"] = "La contraseña debe tener al menos 6 caracteres.";
        }
        if (!isset($nombresRoles[$nuevo["rol"]])) {
            $errores["rol"] = "Elige un rol.";
        }

        if (count($errores) === 0) {
            try {
                consulta(
                    "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)",
                    [$nuevo["nombre"], $nuevo["email"], password_hash($nuevo["password"], PASSWORD_DEFAULT), $nuevo["rol"]]
                );
                irA("usuarios.php", "Usuario creado.");
            } catch (mysqli_sql_exception $e) {
                $errores["email"] = "Ese correo ya está registrado.";
            }
        }

    // Cambiar el rol o activar/desactivar (nunca a uno mismo)
    } else {
        $id = (int) $_POST["id"];
        if ($id === (int) $miId) {
            irA("usuarios.php", "No puedes cambiar tu propio rol ni desactivarte.", "error");
        }

        if ($accion === "rol" && isset($nombresRoles[$_POST["rol"]])) {
            consulta("UPDATE usuarios SET rol = ? WHERE id = ?", [$_POST["rol"], $id]);
            irA("usuarios.php", "Rol actualizado.");
        }

        if ($accion === "estado") {
            consulta("UPDATE usuarios SET activo = 1 - activo WHERE id = ?", [$id]);   // 1 -> 0, 0 -> 1
            irA("usuarios.php", "Estado actualizado.");
        }
    }
}

// ---------- Números de arriba ----------
$conteo = ["super_admin" => 0, "director" => 0, "docente" => 0, "alumno" => 0];
foreach (consulta("SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol") as $fila) {
    $conteo[$fila["rol"]] = $fila["total"];
}

// ---------- Búsqueda y filtro ----------
$buscar     = trim($_GET["buscar"] ?? "");
$filtroRol  = $_GET["rol"] ?? "";

$sql        = "FROM usuarios WHERE (nombre LIKE ? OR email LIKE ?)";
$parametros = ["%$buscar%", "%$buscar%"];
if (isset($nombresRoles[$filtroRol])) {
    $sql .= " AND rol = ?";
    $parametros[] = $filtroRol;
}

// Paginación: 15 usuarios por página
$totalFiltrados = consulta("SELECT COUNT(*) AS total " . $sql, $parametros)->fetch_assoc()["total"];
$pagina = paginar($totalFiltrados, 15);

$usuarios = consulta(
    "SELECT * " . $sql . " ORDER BY created_at DESC LIMIT {$pagina["por"]} OFFSET {$pagina["desde"]}",
    $parametros
)->fetch_all(MYSQLI_ASSOC);

$titulo = "Usuarios";
include "includes/arriba.php";
?>

<div class="encabezado">
    <div>
        <p class="mono texto-gris">Administración</p>
        <h1>Usuarios</h1>
        <p class="texto-gris">Asigna roles y activa o desactiva cuentas.</p>
    </div>
    <a href="exportar.php?tipo=usuarios" class="boton boton-secundario">Descargar en Excel</a>
</div>

<div class="numeros">
    <div class="numero"><strong><?php echo array_sum($conteo); ?></strong><span>Usuarios en total</span></div>
    <div class="numero"><strong><?php echo $conteo["alumno"]; ?></strong><span>Alumnos</span></div>
    <div class="numero"><strong><?php echo $conteo["docente"]; ?></strong><span>Docentes</span></div>
    <div class="numero"><strong><?php echo $conteo["director"]; ?></strong><span>Directores</span></div>
</div>

<!-- Si hubo errores, el formulario se queda abierto ("open") para que se vean -->
<details class="tarjeta" <?php echo count($errores) > 0 ? "open" : ""; ?>>
    <summary>+ Nuevo usuario</summary>
    <form method="POST" class="validar" novalidate>
        <input type="hidden" name="accion" value="crear">
        <div class="campo">
            <label>Nombre completo</label>
            <input type="text" name="nombre" value="<?php e($nuevo["nombre"]); ?>" maxlength="100" required>
            <?php errorCampo($errores, "nombre"); ?>
        </div>
        <div class="campo">
            <label>Correo electrónico</label>
            <input type="email" name="email" value="<?php e($nuevo["email"]); ?>" maxlength="150" required>
            <?php errorCampo($errores, "email"); ?>
        </div>
        <div class="campo">
            <label>Contraseña inicial</label>
            <input type="text" name="password" value="<?php e($nuevo["password"]); ?>" minlength="6" required>
            <small>Compártela con la persona para que entre y la cambie en "Mi perfil".</small>
            <?php errorCampo($errores, "password"); ?>
        </div>
        <div class="campo">
            <label>Rol</label>
            <select name="rol">
                <?php foreach ($nombresRoles as $valor => $texto) { ?>
                    <option value="<?php echo $valor; ?>" <?php echo $valor === $nuevo["rol"] ? "selected" : ""; ?>><?php echo $texto; ?></option>
                <?php } ?>
            </select>
            <?php errorCampo($errores, "rol"); ?>
        </div>
        <button type="submit" class="boton">Crear usuario</button>
    </form>
</details>

<div class="tarjeta">
    <form method="GET" class="fila" style="margin-bottom: 16px">
        <input type="search" name="buscar" value="<?php e($buscar); ?>" placeholder="Buscar por nombre o correo" style="flex: 1; min-width: 200px">
        <select name="rol">
            <option value="">Todos los roles</option>
            <?php foreach ($nombresRoles as $valor => $texto) { ?>
                <option value="<?php echo $valor; ?>" <?php echo $valor === $filtroRol ? "selected" : ""; ?>><?php echo $texto; ?></option>
            <?php } ?>
        </select>
        <button type="submit" class="boton boton-secundario">Buscar</button>
    </form>

    <?php if (count($usuarios) === 0) { ?>
        <p class="vacio">No se encontraron usuarios.</p>
    <?php } else { ?>
    <div class="tabla-contenedor">
        <table class="tabla">
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Alta</th>
            </tr>
            <?php foreach ($usuarios as $u) { ?>
                <?php $esYo = $u["id"] == $miId; ?>
                <tr>
                    <td>
                        <div class="fila">
                            <?php avatar($u["nombre"], $u["foto"]); ?>
                            <div>
                                <strong><?php e($u["nombre"]); ?></strong><?php echo $esYo ? " (tú)" : ""; ?><br>
                                <span class="texto-gris"><?php e($u["email"]); ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <!-- Al cambiar la opción, el formulario se envía solo (onchange) -->
                        <form method="POST">
                            <input type="hidden" name="accion" value="rol">
                            <input type="hidden" name="id" value="<?php echo $u["id"]; ?>">
                            <select name="rol" onchange="this.form.submit()" <?php echo $esYo ? "disabled" : ""; ?>>
                                <?php foreach ($nombresRoles as $valor => $texto) { ?>
                                    <option value="<?php echo $valor; ?>" <?php echo $valor === $u["rol"] ? "selected" : ""; ?>><?php echo $texto; ?></option>
                                <?php } ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($u["activo"]) { ?>
                            <span class="etiqueta">Activo</span>
                        <?php } else { ?>
                            <span class="etiqueta etiqueta-roja">Inactivo</span>
                        <?php } ?>
                        <?php if (!$esYo) { ?>
                            <form method="POST" style="display: inline"
                                <?php if ($u["activo"]) { ?>data-confirmar="¿Desactivar a <?php e($u["nombre"]); ?>? Ya no podrá entrar."<?php } ?>>
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id" value="<?php echo $u["id"]; ?>">
                                <button type="submit" class="enlace-peligro" style="margin-left: 8px">
                                    <?php echo $u["activo"] ? "Desactivar" : "Activar"; ?>
                                </button>
                            </form>
                        <?php } ?>
                    </td>
                    <td class="texto-gris"><?php echo fecha($u["created_at"]); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php enlacesPaginas($pagina); ?>
    <?php } ?>
</div>

<?php include "includes/abajo.php"; ?>
