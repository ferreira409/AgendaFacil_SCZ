// Gráficos del panel (Chart.js). Los datos vienen de PHP en window.DATOS_GRAFICOS
(function () {
    if (typeof Chart === "undefined" || !window.DATOS_GRAFICOS) return;
    const d = window.DATOS_GRAFICOS;
    const verde = "#00843d", verdeClaro = "#b7e1c8";

    Chart.defaults.font.family = "Inter, system-ui, sans-serif";
    Chart.defaults.color = "#64748b";
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.maintainAspectRatio = false;

    const dias = document.getElementById("graficoDias");
    if (dias) new Chart(dias, {
        type: "bar",
        data: {
            labels: d.dias.etiquetas,
            datasets: [{
                label: "Citas",
                data: d.dias.valores,
                backgroundColor: d.dias.etiquetas.map((_, i) => i === d.dias.hoy ? verde : verdeClaro),
                borderRadius: 6,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
        },
    });

    const estados = document.getElementById("graficoEstados");
    if (estados) new Chart(estados, {
        type: "doughnut",
        data: {
            labels: d.estados.etiquetas,
            datasets: [{ data: d.estados.valores, backgroundColor: ["#f59e0b", verde, "#ef4444", "#0ea5e9"], borderWidth: 0 }],
        },
        options: { cutout: "65%", plugins: { legend: { position: "bottom" } } },
    });

    const negocios = document.getElementById("graficoNegocios");
    if (negocios) new Chart(negocios, {
        type: "bar",
        data: { labels: d.negocios.etiquetas, datasets: [{ data: d.negocios.valores, backgroundColor: verde, borderRadius: 6 }] },
        options: {
            indexAxis: "y",
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } },
        },
    });
})();

// Gráficos de la encuesta (admin/encuesta.php)
(function () {
    if (typeof Chart === "undefined" || !window.DATOS_ENCUESTA) return;
    const d = window.DATOS_ENCUESTA;
    Chart.defaults.font.family = "Inter, system-ui, sans-serif";
    Chart.defaults.maintainAspectRatio = false;
    const verde = "#00843d";
    const barras = (id, datos, colores, horizontal) => {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: "bar",
            data: { labels: datos.etiquetas, datasets: [{ data: datos.valores, backgroundColor: colores, borderRadius: 6 }] },
            options: {
                indexAxis: horizontal ? "y" : "x",
                plugins: { legend: { display: false } },
                scales: { [horizontal ? "x" : "y"]: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    };
    const dona = (id, datos, colores) => {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: "doughnut",
            data: { labels: datos.etiquetas, datasets: [{ data: datos.valores, backgroundColor: colores, borderWidth: 0 }] },
            options: { cutout: "62%", plugins: { legend: { position: "bottom" } } },
        });
    };
    barras("encGeneral", d.general, ["#ef4444", "#f97316", "#f59e0b", "#84cc16", verde]);
    dona("encUsaria", d.usaria, [verde, "#f59e0b", "#ef4444"]);
    dona("encDispositivo", d.dispositivo, [verde, "#0ea5e9", "#a855f7"]);
    barras("encPerfil", d.perfil, verde, true);
})();
