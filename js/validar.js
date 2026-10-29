// =====================================================
// Validación en vivo de formularios.
// Muestra el error DEBAJO de cada campo mientras el usuario escribe.
// Funciona en cualquier formulario que tenga class="validar".
//
// Las reglas se leen del HTML de cada campo:
//   required          -> obligatorio
//   minlength="6"     -> mínimo de caracteres
//   type="email"      -> debe ser un correo
//   type="url"        -> debe empezar con http:// o https://
//   data-igual-a="x"  -> debe ser igual al campo llamado "x" (confirmar contraseña)
//
// PHP vuelve a revisar todo al enviar: esto solo es para ayudar al usuario.
// =====================================================

// Regresa el mensaje de error de un campo, o "" si está bien
function mensajeDe(campo) {
    const valor = campo.value;

    if (campo.required && valor.trim() === "") {
        return "Este campo es obligatorio.";
    }
    if (campo.type === "email" && valor !== "" && campo.validity.typeMismatch) {
        return "Escribe un correo válido (ej. nombre@correo.com).";
    }
    if (campo.type === "url" && valor !== "" && !/^https?:\/\//.test(valor)) {
        return "El enlace debe empezar con http:// o https://";
    }
    if (campo.minLength > 0 && valor !== "" && valor.length < campo.minLength) {
        const quien = campo.type === "password" ? "La contraseña" : "Este campo";
        return quien + " debe tener al menos " + campo.minLength + " caracteres (llevas " + valor.length + ").";
    }
    if (campo.dataset.igualA) {
        const otro = campo.form.elements[campo.dataset.igualA];
        if (valor !== "" && valor !== otro.value) {
            return "Las contraseñas no coinciden.";
        }
    }
    return "";
}

// Pone (o quita) el mensaje debajo del campo. Regresa true si el campo está bien.
function revisar(campo) {
    const caja    = campo.closest(".campo");
    const mensaje = mensajeDe(campo);
    let aviso     = caja.querySelector(".error-campo");

    if (mensaje === "") {
        if (aviso) aviso.remove();
        return true;
    }
    if (!aviso) {
        aviso = document.createElement("small");
        aviso.className = "error-campo";
        caja.appendChild(aviso);
    }
    aviso.textContent = mensaje;
    return false;
}

document.querySelectorAll("form.validar").forEach(function (formulario) {
    const campos = formulario.querySelectorAll(".campo input, .campo textarea, .campo select");

    campos.forEach(function (campo) {
        // Al salir del campo, si ya escribió algo
        campo.addEventListener("blur", function () {
            if (campo.value !== "") revisar(campo);
        });

        // Mientras escribe: actualiza el mensaje si el campo ya tenía error
        campo.addEventListener("input", function () {
            if (campo.closest(".campo").querySelector(".error-campo")) revisar(campo);

            // Si cambia la contraseña, revisamos otra vez el campo "repite la contraseña"
            campos.forEach(function (otro) {
                if (otro.dataset.igualA === campo.name && otro.value !== "") revisar(otro);
            });
        });
    });

    // Al enviar: revisa todo; si algo está mal, no envía
    formulario.addEventListener("submit", function (evento) {
        let todoBien = true;
        campos.forEach(function (campo) {
            if (!revisar(campo)) todoBien = false;
        });
        if (!todoBien) evento.preventDefault();
    });
});

// =====================================================
// Confirmar antes de cerrar sesión.
// En vez de salir de golpe, abre la ventanita "¿Estás seguro...?"
// =====================================================
const abrirSalir     = document.getElementById("abrir-salir");
const confirmarSalir = document.getElementById("confirmar-salir");

if (abrirSalir && confirmarSalir) {
    abrirSalir.addEventListener("click", function (evento) {
        evento.preventDefault();
        confirmarSalir.showModal();
    });

    // "Seguir navegando" solo cierra la ventanita
    document.getElementById("seguir-navegando").addEventListener("click", function () {
        confirmarSalir.close();
    });

    // Clic fuera de la ventanita (en lo oscuro) también la cierra
    confirmarSalir.addEventListener("click", function (evento) {
        const caja = confirmarSalir.getBoundingClientRect();
        const fuera = evento.clientX < caja.left || evento.clientX > caja.right ||
                      evento.clientY < caja.top  || evento.clientY > caja.bottom;
        if (fuera) confirmarSalir.close();
    });
}

// =====================================================
// Cambios sin guardar.
// Los formularios con class="vigilar" se marcan como "sucios" cuando
// el usuario escribe algo. Si intenta irse o usar OTRO formulario,
// se le avisa para que no pierda lo que escribió.
// =====================================================
let enviando = false;   // true mientras se envía un formulario (para no avisar al salir)

document.querySelectorAll("form.vigilar").forEach(function (formulario) {
    formulario.addEventListener("input", function () { formulario.dataset.sucio = "si"; });
    formulario.addEventListener("change", function () { formulario.dataset.sucio = "si"; });
});

// ¿Hay formularios con cambios sin guardar, aparte de "excepto"?
function hayCambiosSinGuardar(excepto) {
    return Array.from(document.querySelectorAll("form.vigilar[data-sucio]")).some(function (f) {
        return f !== excepto;
    });
}

// Al cerrar la pestaña o ir a otra página con cambios sin guardar, el navegador pregunta
window.addEventListener("beforeunload", function (evento) {
    if (!enviando && hayCambiosSinGuardar(null)) {
        evento.preventDefault();
        evento.returnValue = "";
    }
});

// =====================================================
// Confirmar antes de hacer algo.
// - Formularios con data-confirmar="¿Eliminar...?" (y opcional data-detalle="...")
// - Cualquier formulario, si hay cambios sin guardar en otro
// Se usa la ventanita de arriba.php en lugar del cuadro gris del navegador.
// =====================================================
const confirmarAccion = document.getElementById("confirmar-accion");
let formularioPendiente = null;   // el formulario que se enviará si dice "Sí"

// Escuchamos los envíos en todo el documento (después de la validación de cada formulario)
document.addEventListener("submit", function (evento) {
    const formulario = evento.target;
    if (evento.defaultPrevented) return;   // la validación ya lo detuvo

    let pregunta = formulario.dataset.confirmar || "";
    let detalle  = formulario.dataset.detalle || "Esta acción no se puede deshacer.";

    if (hayCambiosSinGuardar(formulario)) {
        if (pregunta === "") {
            pregunta = "Tienes cambios sin guardar";
            detalle  = "Si continúas, se perderá lo que escribiste y no guardaste en otra parte de esta página.";
        } else {
            detalle += " Además, perderás los cambios sin guardar de esta página.";
        }
    }

    if (pregunta === "" || !confirmarAccion) {
        enviando = true;
        return;   // no hay nada que preguntar: se envía normal
    }

    evento.preventDefault();
    formularioPendiente = formulario;
    // Recordamos qué botón se presionó (por ejemplo ↑ o ↓), porque submit() no lo manda solo
    formularioPendiente.botonUsado = evento.submitter;
    document.getElementById("confirmar-texto").textContent   = pregunta;
    document.getElementById("confirmar-detalle").textContent = detalle;
    confirmarAccion.showModal();
});

if (confirmarAccion) {
    document.getElementById("confirmar-si").addEventListener("click", function () {
        confirmarAccion.close();
        enviando = true;
        const boton = formularioPendiente.botonUsado;
        if (boton && boton.name) {
            const oculto = document.createElement("input");
            oculto.type  = "hidden";
            oculto.name  = boton.name;
            oculto.value = boton.value;
            formularioPendiente.appendChild(oculto);
        }
        formularioPendiente.submit();   // submit() directo: no vuelve a preguntar
    });
    document.getElementById("confirmar-no").addEventListener("click", function () {
        confirmarAccion.close();
    });
}

// =====================================================
// Cajas de código: la tecla Tab agrega 4 espacios
// (normalmente Tab saltaría al siguiente campo)
// =====================================================
document.querySelectorAll("textarea.area-codigo").forEach(function (caja) {
    caja.addEventListener("keydown", function (evento) {
        if (evento.key !== "Tab" || evento.shiftKey) return;
        evento.preventDefault();
        const inicio = caja.selectionStart;
        caja.value = caja.value.slice(0, inicio) + "    " + caja.value.slice(caja.selectionEnd);
        caja.selectionStart = caja.selectionEnd = inicio + 4;
        caja.form.dataset.sucio = "si";
    });
});

// =====================================================
// Globitos de mensaje: se van solos a los 5 segundos
// o al tocar la ×.
// =====================================================
document.querySelectorAll(".globo").forEach(function (globo) {
    function quitar() {
        globo.classList.add("saliendo");
        setTimeout(function () { globo.remove(); }, 300);
    }
    globo.querySelector("button").addEventListener("click", quitar);
    setTimeout(quitar, 5000);
});

// =====================================================
// Menú del celular: el botón ☰ lo abre y lo cierra
// =====================================================
const botonMenu = document.getElementById("boton-menu");
if (botonMenu) {
    botonMenu.addEventListener("click", function () {
        const abierto = document.getElementById("lateral").classList.toggle("abierto");
        botonMenu.setAttribute("aria-expanded", abierto);
        botonMenu.textContent = abierto ? "×" : "☰";
    });
}
