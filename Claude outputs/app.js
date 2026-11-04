// Menú responsive (celular)
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
        navLinks.classList.toggle("abierto");
    });

    // Cerrar el menú al tocar un enlace
    navLinks.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => navLinks.classList.remove("abierto"));
    });
}

// Reservar: al cambiar la fecha, recargar para ver los horarios libres de ese día
const fechaCita = document.getElementById("fechaCita");
if (fechaCita) {
    fechaCita.addEventListener("change", () => {
        if (!fechaCita.value) return;
        const params = new URLSearchParams({ negocio: fechaCita.dataset.negocio, fecha: fechaCita.value });
        window.location.href = "?" + params.toString();
    });
}

// ===== Efecto "onda" al hacer clic en los botones =====
document.querySelectorAll(".btn").forEach(boton => {
    boton.addEventListener("click", e => {
        const rect = boton.getBoundingClientRect();
        const tam = Math.max(rect.width, rect.height);
        const onda = document.createElement("span");
        onda.className = "onda";
        onda.style.width = onda.style.height = tam + "px";
        onda.style.left = (e.clientX - rect.left - tam / 2) + "px";
        onda.style.top = (e.clientY - rect.top - tam / 2) + "px";
        boton.appendChild(onda);
        setTimeout(() => onda.remove(), 600);
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
    ".titulo-seccion, .subtitulo-seccion, .paso, .tarjeta, .stat, .tabla-caja, .cta h2, .cta p"
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
        el.style.transitionDelay = (i % 4) * 80 + "ms"; // aparecen en cascada
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
    cerrar.innerHTML = "✕";
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
