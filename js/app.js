// Menú responsive (celular)
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
        const abierto = navLinks.classList.toggle("abierto");
        menuToggle.innerHTML = abierto
            ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    });

    // Cerrar el menú al tocar un enlace
    navLinks.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => navLinks.classList.remove("abierto"));
    });
}

// ===== Reservar: al elegir otra fecha, recargar con los horarios de ese día =====
const fechaCita = document.getElementById("fechaCita");
if (fechaCita) {
    fechaCita.addEventListener("change", () => {
        if (fechaCita.value) window.location.href = fechaCita.dataset.url + "&fecha=" + fechaCita.value;
    });
}

// ===== Mostrar / ocultar contraseña =====
document.querySelectorAll(".ver-password").forEach(boton => {
    boton.addEventListener("click", () => {
        const input = boton.parentNode.querySelector("input");
        const visible = input.type === "text";
        input.type = visible ? "password" : "text";
        boton.innerHTML = visible
            ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
            : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
    });
});

// ===== Sombra del navbar y botón "volver arriba" según el scroll =====
const navbar = document.querySelector(".navbar");
const btnArriba = document.getElementById("btnArriba");

window.addEventListener("scroll", () => {
    if (navbar) navbar.classList.toggle("con-sombra", window.scrollY > 40);
    if (btnArriba) btnArriba.classList.toggle("visible", window.scrollY > 400);
});

if (btnArriba) {
    btnArriba.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
}

// ===== Elementos que aparecen al hacer scroll =====
const animables = document.querySelectorAll(
    ".titulo-seccion, .subtitulo-seccion, .paso, .tarjeta-negocio, .beneficio, .galeria-item, .stat"
);

if ("IntersectionObserver" in window) {
    const observador = new IntersectionObserver(entradas => {
        entradas.forEach(entrada => {
            if (entrada.isIntersecting) {
                entrada.target.classList.add("visible");
                observador.unobserve(entrada.target);
            }
        });
    }, { threshold: 0.12 });

    animables.forEach((el, i) => {
        el.classList.add("revelar");
        el.style.transitionDelay = (i % 4) * 60 + "ms"; // aparecen en cascada
        observador.observe(el);
        // al terminar de aparecer, quitar la animación para que el hover responda rápido
        el.addEventListener("transitionend", () => {
            if (!el.classList.contains("visible")) return;
            el.style.transitionDelay = "";
            el.classList.remove("revelar", "visible");
        }, { once: true });
    });
}

// ===== Alertas: botón de cerrar y desaparecen solas =====
document.querySelectorAll(".alerta").forEach(alerta => {
    const cerrar = document.createElement("button");
    cerrar.className = "alerta-cerrar";
    cerrar.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
    cerrar.setAttribute("aria-label", "Cerrar");
    alerta.appendChild(cerrar);

    const ocultar = () => {
        alerta.classList.add("saliendo");
        setTimeout(() => alerta.remove(), 350);
    };
    cerrar.addEventListener("click", ocultar);

    // Los mensajes de éxito se van solos a los 5 segundos
    if (alerta.classList.contains("alerta-exito")) setTimeout(ocultar, 5000);
});

// ===== Contadores animados (franja de estadísticas) =====
document.querySelectorAll("[data-contar]").forEach(el => {
    const final = parseInt(el.dataset.contar, 10) || 0;
    const animar = () => {
        const inicio = performance.now();
        const duracion = 1200;
        const paso = ahora => {
            const p = Math.min((ahora - inicio) / duracion, 1);
            el.textContent = Math.round(final * (1 - Math.pow(1 - p, 3))); // desacelera al final
            if (p < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    };
    if ("IntersectionObserver" in window) {
        const obs = new IntersectionObserver(e => {
            if (e[0].isIntersecting) { animar(); obs.disconnect(); }
        });
        obs.observe(el);
    } else {
        el.textContent = final;
    }
});

// =========================================================
//  APP INSTALABLE (PWA)
// =========================================================
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register((window.BASE_URL || "/") + "sw.js").catch(() => {});
    });
}

(function () {
    const aviso = document.getElementById("avisoApp");
    if (!aviso) return;
    const yaInstalada = window.matchMedia("(display-mode: standalone)").matches || navigator.standalone;
    let cerrado = false;
    try { cerrado = localStorage.getItem("avisoAppCerrado") === "1"; } catch (e) {}
    if (yaInstalada || cerrado) return;

    const btn = document.getElementById("btnInstalar");
    const texto = document.getElementById("avisoAppTexto");
    let eventoInstalar = null;

    // Android / Chrome / Edge: el navegador avisa que se puede instalar
    window.addEventListener("beforeinstallprompt", e => {
        e.preventDefault();
        eventoInstalar = e;
        aviso.hidden = false;
    });
    btn.addEventListener("click", async () => {
        if (!eventoInstalar) return;
        eventoInstalar.prompt();
        await eventoInstalar.userChoice;
        aviso.hidden = true;
    });

    // iPhone (Safari): no tiene botón automático, se explica cómo hacerlo
    const esIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    if (esIOS) {
        texto.innerHTML = 'Tocá <i class="fa-solid fa-arrow-up-from-bracket"></i> Compartir y luego <b>"Agregar a inicio"</b>.';
        btn.hidden = true;
        aviso.hidden = false;
    }

    document.getElementById("cerrarAvisoApp").addEventListener("click", () => {
        aviso.hidden = true;
        try { localStorage.setItem("avisoAppCerrado", "1"); } catch (e) {}
    });
    window.addEventListener("appinstalled", () => { aviso.hidden = true; });
})();

// ===== Botón "Copiar" (link de la encuesta) =====
document.querySelectorAll("[data-copiar]").forEach(boton => {
    boton.addEventListener("click", async () => {
        const input = document.getElementById(boton.dataset.copiar);
        try { await navigator.clipboard.writeText(input.value); }
        catch (e) { input.select(); document.execCommand("copy"); }
        const original = boton.innerHTML;
        boton.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copiado';
        setTimeout(() => { boton.innerHTML = original; }, 2000);
    });
});
