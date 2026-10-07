/* ==========================================================================
   Colegio8 — Interacciones (vanilla JS, sin dependencias)
   Modal de documentos, confirmaciones, toasts, menú móvil, modo oscuro/claro.
   Respeta prefers-reduced-motion (los estilos CSS lo desactivan).
   ========================================================================== */
(function () {
  'use strict';

  var base = window.COL8 ? window.COL8.base : '';
  var csrf = window.COL8 ? window.COL8.csrf : '';

  /* ---------------- Modo oscuro / claro ----------------
     La preferencia se aplica temprano en el <head> de layout.php (para no
     parpadear) y acá solo se maneja la pulsación del botón. La elección
     explícita queda en localStorage y se respeta el esquema del sistema
     si el usuario nunca eligió nada. */
  function temaActual() {
    var t = document.documentElement.getAttribute('data-tema');
    return (t === 'claro' || t === 'oscuro') ? t : 'claro';
  }

  function aplicarTema(t) {
    document.documentElement.setAttribute('data-tema', t);
    try { localStorage.setItem('col8_tema', t); } catch (err) { /* ignorar */ }
    var btn = document.getElementById('btnTema');
    if (btn) {
      var oscuro = t === 'oscuro';
      btn.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
      btn.setAttribute('aria-label', oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    }
  }

  var btnTema = document.getElementById('btnTema');
  if (btnTema) {
    btnTema.addEventListener('click', function () {
      aplicarTema(temaActual() === 'oscuro' ? 'claro' : 'oscuro');
    });
    /* sincronizar estado accesible al cargar (si ya venía oscuro del <head>) */
    aplicarTema(temaActual());
  }

  /* ---------------- Toasts (mensajes flash) ---------------- */
  var contenedorToasts = document.getElementById('toastRegion');

  function toast(tipo, texto) {
    if (!contenedorToasts) return;
    var el = document.createElement('div');
    el.className = 'toast ' + tipo;
    el.textContent = texto;
    el.setAttribute('role', 'status');
    contenedorToasts.appendChild(el);
    setTimeout(function () {
      el.classList.add('sale');
      setTimeout(function () { el.remove(); }, 350);
    }, 4500);
  }

  if (window.COL8 && Array.isArray(window.COL8.flashes)) {
    window.COL8.flashes.forEach(function (f) {
      var tipo = f.tipo;
      if (tipo === 'ok' || tipo === 'exito') tipo = 'exito';
      if (tipo === 'error') tipo = 'error';
      if (tipo === 'info') tipo = 'info';
      toast(tipo, f.texto);
    });
  }

  /* ---------------- Modal genérico ---------------- */
  var capa = document.getElementById('modalCapa');
  var tituloModal = document.getElementById('modalTitulo');
  var cuerpoModal = document.getElementById('modalCuerpo');

  function abrirModal(titulo, html, ancho) {
    if (!capa) return;
    var caja = capa.querySelector('.modal-caja');
    caja.classList.remove('modal-amplio');
    caja.style.maxWidth = ancho ? ancho + 'px' : '';
    tituloModal.textContent = titulo;
    cuerpoModal.innerHTML = html;
    capa.hidden = false;
    document.body.style.overflow = 'hidden';
    var foco = capa.querySelector('[autofocus], a, button, input, select, textarea');
    if (foco) foco.focus();
  }

  function cerrarModal() {
    if (!capa) return;
    capa.hidden = true;
    document.body.style.overflow = '';
  }

  if (capa) {
    capa.addEventListener('click', function (e) {
      if (e.target === capa || e.target.closest('[data-cerrar-modal]')) cerrarModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && capa && !capa.hidden) cerrarModal();
    });
  }

  /* ---------------- Modal automático (#modalAuto) ----------------
     Se abre sin interacción: útil para la confirmación de registro.
     Lee data-titulo / data-ancho / data-boton-cerrar y copia el HTML
     del atributo data-contenido-html (ya codificado) al cuerpo. */
  var modalAuto = document.getElementById('modalAuto');
  if (modalAuto && capa) {
    var htmlAuto = modalAuto.getAttribute('data-contenido-html') || '';
    var titAuto  = modalAuto.getAttribute('data-titulo') || '';
    var anchoAuto = modalAuto.getAttribute('data-ancho') || '';
    var botonCerrar = modalAuto.getAttribute('data-boton-cerrar');
    var htmlCuerpo = htmlAuto;
    if (botonCerrar) {
      htmlCuerpo += '<div style="text-align:center;margin-top:.9rem">' +
        '<button type="button" class="btn btn-fantasma" data-cerrar-modal>' +
        escHtml(botonCerrar) + '</button></div>';
    }
    window.addEventListener('DOMContentLoaded', function () {
      abrirModal(titAuto, htmlCuerpo, anchoAuto ? parseInt(anchoAuto, 10) : 0);
    });
  }

  /* Escapar HTML para usarlo dentro de textContent/atributos del modal. */
  function escHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------------- Preview de documentos (sin salir del panel) -------
     Los documentos se abren en un modal amplio. Las imágenes (JPG/PNG,
     campo data-doc-img) se muestran como <img> con object-fit: contain:
     se ven completas y nítidas, sin zoom manual ni recortes. Si el botón
     está dentro de un .doc-grupo (p. ej. las 4 fotos del DNI), se puede
     navegar entre todos los documentos del grupo sin cerrar el cuadro. */
  var docNav = { lista: [], idx: 0 };

  function renderDocPreviewHTML() {
    var btn = docNav.lista[docNav.idx];
    if (!btn) return '';
    var ruta = btn.getAttribute('data-doc-preview');
    var nombre = btn.getAttribute('data-doc-nombre') || 'Documento';
    var src = base + ruta + (ruta.indexOf('?') === -1 ? '?' : '&') + 'dirigir=1';
    var cuerpo = btn.hasAttribute('data-doc-img')
      ? '<img class="doc-img-grande" src="' + src + '" alt="' + nombre + '">'
      : '<iframe src="' + src + '" title="Vista previa de ' + nombre + '"></iframe>';
    var navegacion = '';
    if (docNav.lista.length > 1) {
      navegacion =
        '<div class="doc-nav" role="group" aria-label="Documentos de la solicitud">' +
        '<button type="button" class="btn btn-pequeno btn-fantasma" data-doc-nav="prev"' + (docNav.idx === 0 ? ' disabled' : '') + '>‹ Anterior</button>' +
        '<span class="doc-nav-conteo">' + (docNav.idx + 1) + ' / ' + docNav.lista.length + '</span>' +
        '<button type="button" class="btn btn-pequeno btn-fantasma" data-doc-nav="next"' + (docNav.idx === docNav.lista.length - 1 ? ' disabled' : '') + '>Siguiente ›</button>' +
        '</div>';
    }
    return navegacion + cuerpo;
  }

  function abrirDocPreview(btn) {
    var grupo = btn.closest('.doc-grupo');
    docNav.lista = grupo
      ? Array.prototype.slice.call(grupo.querySelectorAll('[data-doc-preview]'))
      : [btn];
    docNav.idx = docNav.lista.indexOf(btn);
    if (docNav.idx === -1) docNav.idx = 0;
    abrirModal(btn.getAttribute('data-doc-nombre') || 'Documento', renderDocPreviewHTML(), 0);
    capa.querySelector('.modal-caja').classList.add('modal-amplio');
  }

  document.addEventListener('click', function (e) {
    var botonDoc = e.target.closest('[data-doc-preview]');
    if (botonDoc) {
      e.preventDefault();
      abrirDocPreview(botonDoc);
      return;
    }
    var navBtn = e.target.closest('[data-doc-nav]');
    if (navBtn && !navBtn.disabled && docNav.lista.length) {
      e.preventDefault();
      docNav.idx += navBtn.getAttribute('data-doc-nav') === 'prev' ? -1 : 1;
      if (docNav.idx < 0) docNav.idx = 0;
      if (docNav.idx >= docNav.lista.length) docNav.idx = docNav.lista.length - 1;
      abrirModal(docNav.lista[docNav.idx].getAttribute('data-doc-nombre') || 'Documento', renderDocPreviewHTML(), 0);
      capa.querySelector('.modal-caja').classList.add('modal-amplio');
    }
  });

  /* ---------------- Confirmaciones sobre formularios ---------------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.tagName !== 'FORM' || !form.hasAttribute('data-confirm')) return;
    if (form.dataset.confirmado === '1') return;
    e.preventDefault();
    var mensaje = form.getAttribute('data-confirm');

    abrirModal('Confirmar acción',
      '<p>' + mensaje + '</p>' +
      '<div style="display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem">' +
      '<button type="button" class="btn btn-fantasma" data-cerrar-modal>Cancelar</button>' +
      '<button type="button" class="btn btn-peligro" id="confirmarSi">Confirmar</button></div>',
      420);

    var botonSi = document.getElementById('confirmarSi');
    if (botonSi) {
      botonSi.addEventListener('click', function () {
        form.dataset.confirmado = '1';
        cerrarModal();
        form.requestSubmit();
      });
    }
  });

  /* ---------------- Menú lateral (móvil) ---------------- */
  var panel = document.querySelector('.panel');
  var abrir = document.getElementById('abrirBarra');
  var cerrar = document.getElementById('cerrarBarra');

  function toggleBarra(forzar) {
    if (!panel) return;
    var abierto = typeof forzar === 'boolean' ? forzar : !panel.classList.contains('abierto');
    panel.classList.toggle('abierto', abierto);
    if (abrir) abrir.setAttribute('aria-expanded', abierto ? 'true' : 'false');
  }
  if (abrir) abrir.addEventListener('click', function () { toggleBarra(true); });
  if (cerrar) cerrar.addEventListener('click', function () { toggleBarra(false); });
  document.addEventListener('click', function (e) {
    if (!panel || !panel.classList.contains('abierto')) return;
    if (!e.target.closest('.barra-lateral') && !e.target.closest('#abrirBarra')) toggleBarra(false);
  });

  /* ---------------- Mostrar/ocultar contraseña ---------------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-ver-clave]');
    if (!el) return;
    var campo = document.getElementById(el.getAttribute('data-ver-clave'));
    if (!campo) return;
    campo.type = campo.type === 'password' ? 'text' : 'password';
    el.textContent = campo.type === 'password' ? 'Mostrar' : 'Ocultar';
  });

  /* ---------------- Mostrar/ocultar varias contraseñas juntas ---------------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-ver-claves]');
    if (!el) return;
    var ids = el.getAttribute('data-ver-claves').split(',');
    var mostrar = null;
    for (var i = 0; i < ids.length; i++) {
      var campo = document.getElementById(ids[i].replace(/^\s+|\s+$/g, ''));
      if (!campo) continue;
      if (mostrar === null) mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
    }
    if (mostrar !== null) el.textContent = mostrar ? 'Ocultar contraseñas' : 'Mostrar contraseñas';
  });

  /* ---------------- Completar motivo de rechazo con sugerencia -------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-llenar-motivo]');
    if (!el) return;
    var zona = document.getElementById('motivo');
    if (!zona) return;
    zona.value = el.getAttribute('data-llenar-motivo');
    zona.focus();
  });

  /* ---------------- Vínculos de deshabilitado ---------------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('a[aria-disabled="true"]');
    if (el) e.preventDefault();
  });

  /* ---------------- Contadores animados (paneles) ----------------
     Los números importantes (.stats-num[data-contar]) cuentan desde 0
     hasta su valor real al cargar. Respeta prefers-reduced-motion y el
     estallido final muestra el texto original ya formateado. */
  function contadorAnimado(el) {
    var meta = parseInt(el.getAttribute('data-contar'), 10);
    if (isNaN(meta)) { return; }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return; /* deja el valor formateado quieto */
    }
    var original = el.textContent;
    var duracion = 950;
    var inicio = null;
    function paso(ts) {
      if (inicio === null) { inicio = ts; }
      var p = Math.min(1, (ts - inicio) / duracion);
      p = 1 - Math.pow(1 - p, 3); /* easeOutCubic */
      el.textContent = (Math.round(meta * p)).toLocaleString('es-AR');
      if (p < 1) {
        requestAnimationFrame(paso);
      } else {
        el.textContent = original;
      }
    }
    requestAnimationFrame(paso);
  }

  document.querySelectorAll('.stats-num[data-contar]').forEach(contadorAnimado);

  /* ---------------- Destello de pulsación de botones ----------------
     Respuesta inmediata y clara al clic: compresión leve + destello
     (CSS .btn-destello). Idempotente por elemento: se reinicia la
     animación en cada pulsación y nunca se apila con clics rápidos.
     Con movimiento reducido no hace nada (el CSS tampoco anima). */
  if (!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
    document.querySelectorAll('.btn, .accion-principal').forEach(function (el) {
      el.addEventListener('pointerdown', function (e) {
        if (e.button !== undefined && e.button !== 0) return;
        el.classList.remove('btn-destello');
        void el.offsetWidth; /* forzar reinicio limpio de la animación */
        el.classList.add('btn-destello');
        clearTimeout(el._dtimer);
        el._dtimer = setTimeout(function () { el.classList.remove('btn-destello'); }, 200);
      });
    });
  }

  /* ---------------- Mover alumno de curso (secretaría) ----------------
     Modal con los destinos posibles: muestra cupos disponibles, los
     cursos llenos aparecen deshabilitados y solo se habilita con la
     opción explícita de mover igualmente (el alumno entra a la lista de
     espera del destino). */
  var destinosMover = null;
  try {
    var nodoDestinos = document.getElementById('destinosMoverJson');
    if (nodoDestinos && nodoDestinos.textContent) destinosMover = JSON.parse(nodoDestinos.textContent);
  } catch (err) { destinosMover = null; }

  function abrirMoverModal(alumnoId, nombre, cursoId) {
    var filas = '';
    var hayLlenos = false;
    destinosMover.forEach(function (d) {
      if (d.lleno) hayLlenos = true;
      var texto = d.titulo + ' — ' +
        (d.lleno ? 'curso lleno (lista de espera)' :
          d.disponibles + (d.disponibles === 1 ? ' cupo disponible' : ' cupos disponibles'));
      filas += '<label class="mover-opcion">' +
        '<input type="radio" name="destino_id" value="' + d.id + '"' + (d.lleno ? ' disabled data-mov-lleno="1"' : '') + '>' +
        '<span>' + texto + '</span></label>';
    });
    if (!filas) filas = '<p class="ayuda" style="margin:0">No hay otros cursos disponibles para mover.</p>';

    var html =
      '<form method="post" action="' + base + 'secretaria/curso.php?id=' + cursoId + '">' +
      '<input type="hidden" name="csrf" value="' + csrf + '">' +
      '<input type="hidden" name="accion" value="mover">' +
      '<input type="hidden" name="alumno_id" value="' + alumnoId + '">' +
      '<p style="color:var(--gris-500);font-size:.9rem">Elegí el curso de destino del alumno.</p>' +
      filas +
      (hayLlenos
        ? '<label class="mover-excepcion"><input type="checkbox" id="moverPermitirLleno" name="permitir_lleno" value="1"> ' +
          'Mover de todos modos a un curso lleno (el alumno entra a la lista de espera del destino)</label>'
        : '') +
      '<div style="display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem;flex-wrap:wrap">' +
      '<button type="button" class="btn btn-fantasma" data-cerrar-modal>Cancelar</button>' +
      '<button type="submit" class="btn btn-primario">Mover alumno</button>' +
      '</div></form>';

    abrirModal('Mover a ' + nombre, html, 580);

    var chk = document.getElementById('moverPermitirLleno');
    if (chk) {
      chk.addEventListener('change', function () {
        document.querySelectorAll('[data-mov-lleno]').forEach(function (r) {
          r.disabled = !chk.checked;
        });
      });
    }
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-mover-alumno]');
    if (!el || !destinosMover) return;
    e.preventDefault();
    abrirMoverModal(
      el.getAttribute('data-mover-alumno'),
      el.getAttribute('data-mover-nombre'),
      el.getAttribute('data-curso-id') || ''
    );
  });

  /* ---------------- Simple "guardado" de foco al saltar de tab en el wizard */
  var estados = document.querySelectorAll('.wizard-paso');
  if (estados.length) {
    try {
      sessionStorage.removeItem('col8_scroll'); // reservado
    } catch (err) { /* ignorar */ }
  }

  /* ---------------- Autocompletar (input + lista) ----------------
     Genera inputs de búsqueda como los de préstamos. Los datos viven en
     <script type="application/json" id="datos-<clave>"[]>. El input debe
     tener data-autocompletar="<clave>" y a su lado un <input type="hidden">
     con el nombre del campo que guarda el valor real. Se navega con
     flechas y Enter, y se elige con clic. */
  function iniciarAutocompletar(input) {
    var clave = input.getAttribute('data-autocompletar');
    if (!clave) return;
    var script = document.getElementById('datos-' + clave);
    if (!script) return;
    var opciones = [];
    try { opciones = JSON.parse(script.textContent) || []; } catch (err) { return; }

    var caja = input.closest('.autocompletar');
    if (!caja) return;
    var oculto = caja.querySelector('input[type="hidden"]');
    var lista = caja.querySelector('.autocompletar-lista');
    if (!oculto || !lista) return;

    var descartado = false;
    var destacadoIdx = -1;

    function dibujar(filtro) {
      var texto = (filtro || '').trim().toLowerCase();
      var resultados = texto
        ? opciones.filter(function (op) {
            return (op.etiqueta + ' ' + (op.sub || '')).toLowerCase().indexOf(texto) !== -1;
          })
        : opciones;
      if (resultados.length > 8) resultados = resultados.slice(0, 8);

      lista.innerHTML = '';
      destacadoIdx = -1;
      if (!resultados.length) {
        var li = document.createElement('li');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Sin resultados';
        btn.disabled = true;
        btn.className = 'autocompletar-sub';
        li.appendChild(btn);
        lista.appendChild(li);
        lista.classList.add('visible');
        return;
      }
      resultados.forEach(function (op, i) {
        var li = document.createElement('li');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('role', 'option');
        btn.textContent = op.etiqueta;
        if (op.sub) {
          var sub = document.createElement('span');
          sub.className = 'autocompletar-sub';
          sub.textContent = op.sub;
          btn.appendChild(sub);
        }
        btn.addEventListener('click', function () {
          elegir(op);
        });
        li.appendChild(btn);
        lista.appendChild(li);
      });
      lista.classList.add('visible');
    }

    function elegir(op) {
      oculto.value = op.v;
      input.value = op.etiqueta;
      descartado = false;
      lista.classList.remove('visible');
      input.focus();
    }

    function moverDestacado(step) {
      var botones = lista.querySelectorAll('li button:not(:disabled)');
      if (!botones.length) return;
      botones.forEach(function (b) { b.classList.remove('destacado'); });
      destacadoIdx += step;
      if (destacadoIdx < 0) destacadoIdx = botones.length - 1;
      if (destacadoIdx >= botones.length) destacadoIdx = 0;
      botones[destacadoIdx].classList.add('destacado');
      botones[destacadoIdx].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('input', function () {
      if (!descartado && oculto.value) oculto.value = '';
      dibujar(input.value);
    });
    input.addEventListener('focus', function () { dibujar(input.value); });
    input.addEventListener('blur', function () {
      setTimeout(function () {
        lista.classList.remove('visible');
        // Si el usuario escribió algo pero no eligió, descartar el hidden.
        if (input.value && !oculto.value) {
          descartado = true;
          input.value = '';
        }
      }, 120);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); moverDestacado(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); moverDestacado(-1); }
      else if (e.key === 'Enter') {
        var activado = lista.querySelector('li button:not(:disabled).destacado');
        if (activado) {
          e.preventDefault();
          activado.click();
        }
      }
      else if (e.key === 'Escape') { lista.classList.remove('visible'); }
    });

    document.addEventListener('click', function (e) {
      if (!lista.contains(e.target) && e.target !== input) {
        lista.classList.remove('visible');
      }
    });
  }

  document.querySelectorAll('[data-autocompletar]').forEach(iniciarAutocompletar);
})();