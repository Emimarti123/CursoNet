<?php
// Muestra el mensaje que dejó irA() como un globito arriba a la derecha.
// js/validar.js lo esconde solo después de unos segundos.
if (isset($_SESSION["aviso"])) {
    $tipoGlobo = $_SESSION["aviso_tipo"] ?? "exito";
?>
    <div class="globos" aria-live="polite">
        <div class="globo <?php echo $tipoGlobo === "error" ? "globo-error" : ""; ?>" role="status">
            <p><?php e($_SESSION["aviso"]); ?></p>
            <button type="button" aria-label="Cerrar mensaje">×</button>
        </div>
    </div>
<?php
    unset($_SESSION["aviso"], $_SESSION["aviso_tipo"]);
}
