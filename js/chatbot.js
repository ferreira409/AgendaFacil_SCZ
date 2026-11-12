// =========================================================
//  ASISTENTE VIRTUAL — chat de la página
//  Manda los mensajes a bot/web.php (el mismo motor que Telegram
//  y WhatsApp) y muestra las respuestas con botones.
// =========================================================
(function () {
    const raiz = document.getElementById('chatbot');
    if (!raiz) return;

    const $ = (id) => document.getElementById(id);
    const ventana = $('chatVentana'), lista = $('chatMensajes'), form = $('chatForm'), entrada = $('chatTexto');
    const btnAbrir = $('chatAbrir'), tooltip = $('chatTooltip');
    const URL_CHAT = raiz.dataset.url, CSRF = raiz.dataset.csrf;
    const CLAVE = 'agendafacil-chat-' + raiz.dataset.usuario;   // historial por usuario
    let historial = [];
    let ocupado = false;

    // ---------- Guardar el historial al cambiar de página ----------
    const leer = (k) => { try { return JSON.parse(sessionStorage.getItem(k)); } catch (e) { return null; } };
    const guardar = (k, v) => { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* modo privado */ } };

    // ---------- Formato: *negrita*, enlaces y saltos de línea ----------
    function formatear(texto) {
        const seguro = texto.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        return seguro
            .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
            .replace(/\n/g, '<br>');
    }

    function bajar() { lista.scrollTop = lista.scrollHeight; }

    // ---------- Dibujar mensajes ----------
    function pintarMensaje(m) {
        if (m.tipo === 'opciones') return pintarOpciones(m);
        const div = document.createElement('div');
        div.className = 'chat-msj chat-' + m.tipo;
        div.innerHTML = m.tipo === 'bot' ? formatear(m.texto) : formatear(m.texto).replace(/<a [^>]+>|<\/a>/g, '');
        lista.appendChild(div);
    }

    function pintarOpciones(m) {
        const caja = document.createElement('div');
        caja.className = 'chat-opciones' + (m.usada ? ' usadas' : '');
        m.opciones.forEach((o) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'chat-opcion' + (m.elegida === o.id ? ' elegida' : '');
            b.disabled = !!m.usada;
            b.textContent = o.titulo;
            if (o.desc) {
                const s = document.createElement('small');
                s.textContent = o.desc;
                b.appendChild(s);
            }
            b.addEventListener('click', () => {
                if (ocupado || m.usada) return;
                m.usada = true;
                m.elegida = o.id;
                caja.classList.add('usadas');
                b.classList.add('elegida');
                caja.querySelectorAll('button').forEach((x) => (x.disabled = true));
                enviar(o.id, o.titulo);
            });
            caja.appendChild(b);
        });
        lista.appendChild(caja);
    }

    function agregar(m) {
        historial.push(m);
        if (historial.length > 80) historial = historial.slice(-80);
        pintarMensaje(m);
        guardar(CLAVE, historial);
        bajar();
    }

    // Al escribir a mano, los botones anteriores ya no se pueden usar
    function cerrarOpcionesPendientes() {
        historial.forEach((m) => { if (m.tipo === 'opciones') m.usada = true; });
        lista.querySelectorAll('.chat-opciones:not(.usadas)').forEach((c) => {
            c.classList.add('usadas');
            c.querySelectorAll('button').forEach((x) => (x.disabled = true));
        });
    }

    function mostrarEscribiendo(si) {
        let el = lista.querySelector('.chat-escribiendo');
        if (si && !el) {
            el = document.createElement('div');
            el.className = 'chat-msj chat-bot chat-escribiendo';
            el.setAttribute('aria-label', 'El asistente está escribiendo');
            el.innerHTML = '<span></span><span></span><span></span>';
            lista.appendChild(el);
            bajar();
        } else if (!si && el) {
            el.remove();
        }
    }

    // ---------- Hablar con el servidor ----------
    async function enviar(valor, textoVisible) {
        if (ocupado) return;
        ocupado = true;
        form.querySelector('button').disabled = true;
        if (textoVisible !== null) agregar({ tipo: 'yo', texto: textoVisible });
        mostrarEscribiendo(true);

        try {
            const datos = new FormData();
            datos.append('csrf', CSRF);
            datos.append('mensaje', valor);
            const r = await fetch(URL_CHAT, { method: 'POST', body: datos, credentials: 'same-origin' });
            const json = await r.json().catch(() => null);
            mostrarEscribiendo(false);
            if (!json || !json.ok) {
                agregar({ tipo: 'error', texto: (json && json.error) || 'No pude conectarme. Recargá la página e intentá de nuevo.' });
            } else {
                for (const resp of json.respuestas) {
                    agregar({ tipo: 'bot', texto: resp.texto });
                    if (resp.opciones && resp.opciones.length) agregar({ tipo: 'opciones', opciones: resp.opciones });
                }
            }
        } catch (e) {
            mostrarEscribiendo(false);
            agregar({ tipo: 'error', texto: 'Sin conexión. Revisá tu internet e intentá de nuevo.' });
        } finally {
            ocupado = false;
            form.querySelector('button').disabled = false;
            if (window.matchMedia('(min-width: 601px)').matches) entrada.focus();
        }
    }

    // ---------- Abrir / cerrar ----------
    function abrir() {
        ventana.hidden = false;
        tooltip.hidden = true;
        raiz.classList.add('abierto', 'visto');
        btnAbrir.setAttribute('aria-expanded', 'true');
        guardar('agendafacil-chat-visto', true);
        guardar('agendafacil-chat-abierto', true);
        if (!historial.length) enviar('hola', null);   // saludo inicial
        bajar();
        if (window.matchMedia('(min-width: 601px)').matches) entrada.focus();
    }

    function cerrar() {
        ventana.hidden = true;
        raiz.classList.remove('abierto');
        btnAbrir.setAttribute('aria-expanded', 'false');
        guardar('agendafacil-chat-abierto', false);
        btnAbrir.focus();
    }

    btnAbrir.addEventListener('click', () => (ventana.hidden ? abrir() : cerrar()));
    $('chatCerrar').addEventListener('click', cerrar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !ventana.hidden) cerrar(); });

    $('chatReiniciar').addEventListener('click', () => {
        if (ocupado) return;
        historial = [];
        lista.innerHTML = '';
        guardar(CLAVE, historial);
        enviar('menu', null);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const texto = entrada.value.trim();
        if (!texto || ocupado) return;
        entrada.value = '';
        cerrarOpcionesPendientes();
        enviar(texto, texto);
    });

    // ---------- Al cargar la página ----------
    historial = leer(CLAVE) || [];
    historial.forEach(pintarMensaje);
    if (leer('agendafacil-chat-visto')) raiz.classList.add('visto');
    if (leer('agendafacil-chat-abierto') && window.matchMedia('(min-width: 601px)').matches) abrir();

    // Invitación a los 5 segundos (una vez por visita)
    if (!leer('agendafacil-chat-visto')) {
        setTimeout(() => { if (ventana.hidden) tooltip.hidden = false; }, 5000);
    }
    $('chatTooltipCerrar').addEventListener('click', () => {
        tooltip.hidden = true;
        guardar('agendafacil-chat-visto', true);
        raiz.classList.add('visto');
    });
    tooltip.addEventListener('click', (e) => { if (e.target === tooltip) abrir(); });
})();
