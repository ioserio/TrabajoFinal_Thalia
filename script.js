// Al entrar cada card se anima su contenido en orden
const io = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      e.target.classList.add('in-view');
      io.unobserve(e.target);
    }
  });
},{ threshold: 0.25 });

// Observamos el hero y el cover-card (cada uno controla sus reveals internos)
document.querySelectorAll('.frame-card, .cover-card, .products-card').forEach(el=>io.observe(el));

// También observar secciones de catálogo si existen en la página de productos
document.querySelectorAll('.product-category .frame-card').forEach(el=>io.observe(el));

// --- Overlay Productos emergente ---
const overlay = document.getElementById('productsOverlay');
const driver = document.querySelector('.overlay-driver');
const progressWidget = document.querySelector('.overlay-progress');
const progressFill = progressWidget?.querySelector('.op-fill');
const progressLabel = progressWidget?.querySelector('.op-label');
const heroRoot = document.querySelector('.panel.hero');
const everyImg = document.querySelector('.bg-word-img img');
let overlayShown = false; // estado final (cuando llega a 100%)
let userInitiated = false; // si el usuario empezó a hacer scroll
const driverHeight = () => 0; // desactivado: no usamos desplazamiento natural, solo virtual
let manualProgressMode = true; // usamos modo progresivo
let startedScrollProgress = false; // marca cuando ya iniciamos progresión por scroll
let virtualProgress = 0; // progreso sintético 0..1 controlado por rueda/touch
// Activar scroll virtual solo en la portada (body.intro-seq); en otras páginas, permitir scroll normal
let captureVirtualScroll = (document.body && document.body.classList.contains('intro-seq'));
let renderedProgress = 0; // progreso realmente aplicado (para easing)
let rafId = null; // id de requestAnimationFrame activo
const EASE_FACTOR = 0.16; // ajustar (más alto = responde más rápido)
let partialProgress = 0.5; // primer scroll llega al 50%
let stepIndex = 0; // 0=sin iniciar,1=parcial alcanzado,2=full
let everydayHidden = false; // controla si EVERYDAY ya se ocultó antes de iniciar overlay
let hideInProgress = false; // evita múltiples disparos durante la transición
// Micro-animación para el salto 0→50% con sensación fina
let microRafId = null; // RAF específico para micro-animación

// --- LOGIN/REGISTRO FORM SLIDE ---
document.addEventListener('DOMContentLoaded', function() {
  const btnRegistro = document.getElementById('btn-registro');
  const btnVolver = document.getElementById('btn-volver-login');
  const loginForm = document.getElementById('login-form');
  const registroForm = document.getElementById('registro-form');
  const loginTitle = document.getElementById('login-title');

  if (btnRegistro && btnVolver && loginForm && registroForm) {
    btnRegistro.addEventListener('click', function(e) {
      e.preventDefault();
      loginForm.classList.add('slide-out');
      loginTitle.textContent = 'Registro';
      setTimeout(() => {
        loginForm.style.display = 'none';
        registroForm.style.display = 'flex';
        registroForm.classList.add('slide-in');
      }, 350);
    });

    btnVolver.addEventListener('click', function(e) {
      e.preventDefault();
      registroForm.classList.remove('slide-in');
      registroForm.classList.add('slide-out');
      loginTitle.textContent = 'Iniciar Sesión';
      setTimeout(() => {
        registroForm.style.display = 'none';
        loginForm.style.display = 'flex';
        loginForm.classList.remove('slide-out');
      }, 350);
    });
  }

  // Mostrar error de login si existe en la URL
  try{
    const params = new URLSearchParams(window.location.search);
    if(params.get('error') === '1'){
      const err = document.getElementById('login-error');
      if(err){ err.style.display = 'block'; }
    }
    if(params.get('success') === '1'){
      const t = document.getElementById('toast');
      if(t){
        t.style.display = 'block';
        setTimeout(()=>{ t.style.display = 'none'; }, 5000);
      }
    }
  } catch {}

  // Permitir escribir espacios en inputs: evitar que el listener global capture la barra espaciadora
  const editableSelectors = 'input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea';
  document.querySelectorAll(editableSelectors).forEach(inp => {
    inp.addEventListener('keydown', (e) => {
      if (e.key === ' ') {
        // No prevenir default; solo evitar que burbujee hasta window
        e.stopPropagation();
      }
    });
  });

  // Modal Contacto: abrir/cerrar
  const contactModal = document.getElementById('contactModal');
  const openContactLinks = document.querySelectorAll('a[href="#contact"], a.contact-link');
  const closeBtn = contactModal ? contactModal.querySelector('.modal-close') : null;
  if (contactModal && openContactLinks.length){
    openContactLinks.forEach(a=>{
      a.addEventListener('click', (e)=>{ e.preventDefault(); contactModal.classList.add('open'); });
    });
    closeBtn?.addEventListener('click', ()=> contactModal.classList.remove('open'));
    contactModal.addEventListener('click', (e)=>{
      if(e.target === contactModal){ contactModal.classList.remove('open'); }
    });
    window.addEventListener('keydown', (e)=>{ if(e.key==='Escape') contactModal.classList.remove('open'); });
  }
});
const HALF_EASE_MS = 260; // duración de la animación al 50%
function easeOutCubic(t){ return 1 - Math.pow(1 - t, 3); }
function cancelSmoothLoops(){
  if(rafId){ cancelAnimationFrame(rafId); rafId = null; }
  if(microRafId){ cancelAnimationFrame(microRafId); microRafId = null; }
}
function animateProgressTo(target, duration){
  const start = performance.now();
  const from = renderedProgress;
  const to = Math.min(1, Math.max(0, target));
  function tick(now){
    const t = Math.min(1, (now - start) / duration);
    const eased = easeOutCubic(t);
    renderedProgress = from + (to - from) * eased;
    setOverlayProgress(renderedProgress);
    if(t < 1){ microRafId = requestAnimationFrame(tick); }
    else { microRafId = null; renderedProgress = to; setOverlayProgress(renderedProgress); }
  }
  microRafId = requestAnimationFrame(tick);
}
function goToHalfSmooth(){
  // Oculta EVERYDAY en paralelo y anima hacia 0.5 con micro-easing
  if(!everydayHidden){ hideEveryday(); }
  stepIndex = 1;
  virtualProgress = partialProgress; // fija el objetivo lógico
  cancelSmoothLoops();
  animateProgressTo(partialProgress, HALF_EASE_MS);
}

// Permitir volver desde 100% (activo) a 50% con gesto hacia arriba/retroceso
function goBackToHalfSmooth(){
  // Reactivar captura y estado intermedio
  overlayShown = false;             // ya no se considera estado final
  captureVirtualScroll = true;      // volvemos a capturar gestos de scroll
  stepIndex = 1;                    // estado de mitad alcanzado
  // Quitar clase active si sigue presente y asegurar radios de borde en transición
  overlay.classList.remove('active');
  overlay.classList.add('half');
  // Permitir que la frase fija vuelva a mostrarse al 50%
  document.body.classList.remove('overlay-active');
  // UI inmediata: esquinas redondeadas y mostrar frase sin requerir otro scroll
  overlay.classList.add('sync-progress');
  overlay.style.borderRadius = '34px 34px 0 0';
  document.body.classList.add('half-state');
  const halfEl = document.querySelector('.half-quote');
  if(halfEl){ halfEl.setAttribute('aria-hidden','false'); }
  // Animar retroceso suave 1 -> 0.5
  cancelSmoothLoops();
  animateProgressTo(partialProgress, HALF_EASE_MS);
}

function hideEveryday(){
  if(!everyImg || everydayHidden === true || hideInProgress) return;
  hideInProgress = true;
  // Oculta la imagen EVERYDAY, pero SIN controlar el progreso del overlay.
  // La progresión al 50% ahora se hace inmediatamente en el gesto del usuario.
  // Cancelar cualquier animación de entrada en curso para responder al instante
  everyImg.style.animation = 'none';
  // Transición corta y suave (ease-out) para desaparecer sin golpe
  everyImg.style.transition = 'opacity 240ms cubic-bezier(.22,.61,.36,1), transform 240ms cubic-bezier(.22,.61,.36,1)';
  everyImg.style.opacity = '0';
  // Desplazamiento sutil hacia abajo para acompañar el fade (no 90px para evitar brusquedad)
  everyImg.style.transform = 'translateY(14px) scaleX(var(--everyday-scale-x))';
  everyImg.style.pointerEvents = 'none';
  const done = ()=>{
    everydayHidden = true;
    hideInProgress = false;
  };
  everyImg.addEventListener('transitionend', done, {once:true});
  // Fallback por si transitionend no dispara
  setTimeout(done, 650);
}

function requestSmoothUpdate(){
  if(rafId) return; // ya hay loop corriendo
  rafId = requestAnimationFrame(smoothStep);
}
function smoothStep(){
  const target = Math.min(1, Math.max(0, virtualProgress));
  // interpolación suave (lerp exponencial)
  renderedProgress += (target - renderedProgress) * EASE_FACTOR;
  // si muy cerca, fijar exactamente
  if(Math.abs(target - renderedProgress) < 0.001){
    renderedProgress = target;
  }
  // Snap estético: si estamos cerca de los extremos, forzar cierre/apertura
  if(renderedProgress < 0.02) renderedProgress = 0;
  if(renderedProgress > 0.985) renderedProgress = 1;
  setOverlayProgress(renderedProgress);
  if(renderedProgress !== target){
    rafId = requestAnimationFrame(smoothStep);
  } else {
    rafId = null;
  }
}

function setOverlayProgress(p){
  if(!overlay) return;
  const clamped = Math.min(1, Math.max(0, p));
  // Solo sincronizamos virtualProgress cuando NO estamos capturando scroll virtual,
  // así wheel/touch conservan su objetivo independiente.
  if(!captureVirtualScroll){
    virtualProgress = clamped;
  }
  const translate = (1 - clamped) * 100; // % desde abajo
  // Mientras NO esté completo, forzamos modo sincronizado (sin transición) para evitar "lag" perceptible.
  if(clamped < 1 && !overlayShown && overlay && !overlay.classList.contains('sync-progress')){
    overlay.classList.add('sync-progress');
  }
  overlay.style.transform = `translateY(${translate}%)`;
  // Ajuste de variables para animar desplazamiento de la frase entre 50% y 100%
  overlay.dataset.progress = (clamped*100).toFixed(1);
  // Interpolar 0..1 desde 50% (0.5) a 100% (1.0) para mover la frase hacia arriba
  const t = Math.min(1, Math.max(0, (clamped - 0.5) / 0.5));
  overlay.style.setProperty('--halfToFull', t.toFixed(3));
  // Escala de la tagline al ir de 0% a 50%: 1 -> 0.7 (grow-in inverso)
  const taglineScale = clamped <= 0.5 ? (1 - (clamped/0.5)*0.3) : 0.7; // 0%:1, 50%:.7
  document.documentElement.style.setProperty('--taglineScroll', taglineScale.toFixed(3));
  // Estado "half" (frase intermedia) cuando ~50%
  // Mantener visible la vista intermedia (frase) mientras el progreso está entre 48% y < 99%,
  // y ocultarla antes de activar el 100% para evitar solape con estado activo
  const halfOn = clamped >= 0.48 && clamped < 0.99; 

  // Si bajamos de 1 tras haber estado activo, asegurar que se quite .active
  if(clamped < 1 && overlay.classList.contains('active')){
    overlay.classList.remove('active');
  }
  const fullOn = clamped >= 0.99; // al 100%
  overlay.classList.toggle('half', halfOn);
  const mid = document.getElementById('midPhrase');
  if(mid){
    // Mostrar la frase tanto en el estado intermedio como en el 100%
    mid.setAttribute('aria-hidden', (halfOn || fullOn) ? 'false' : 'true');
  }
  // Accesibilidad: sincronizar aria-hidden de la frase del 50%
  const halfEl = document.querySelector('.half-quote');
  if(halfEl){
    // Ocultar al 100% y mostrar sólo durante el estado intermedio
    halfEl.setAttribute('aria-hidden', (halfOn && !fullOn) ? 'false' : 'true');
  }
  // Half-quote: visibilidad controlada por CSS (body.half-state)
  const colRow = document.querySelector('.collections-row');
  if(colRow){
    // Las colecciones se ocultan sólo en el estado intermedio; al 100% se muestran junto con la frase
    colRow.setAttribute('aria-hidden', halfOn ? 'true' : 'false');
  }
  // Ocultar hero y la barra de progreso global cuando está la vista intermedia
  if(halfOn){
    document.body.classList.add('half-state');
    if(heroRoot){ heroRoot.style.transition = 'opacity 220ms ease'; heroRoot.style.opacity = '0'; }
    if(progressWidget){ progressWidget.style.display = 'none'; }
  } else {
    document.body.classList.remove('half-state');
    if(heroRoot && !overlayShown){ heroRoot.style.opacity = ''; }
    if(progressWidget){ progressWidget.style.display = ''; }
  }
  // Bordes redondeados mientras sube; al 100% se vuelven rectos
  if(!overlayShown){
    if(clamped < 1){
      overlay.style.borderRadius = '34px 34px 0 0';
    } else {
      overlay.style.borderRadius = '0';
    }
  }
  // Actualizar barra
  if(progressWidget){
    if(clamped>0 && clamped<1){
      progressWidget.classList.add('visible');
    } else if(clamped>=1){
      setTimeout(()=>progressWidget.classList.remove('visible'),600);
    } else {
      progressWidget.classList.remove('visible');
    }
    if(progressFill){
      progressFill.style.height = `${clamped*100}%`;
    }
    if(progressLabel){
      progressLabel.textContent = `${Math.round(clamped*100)}%`;
    }
  }
  // Aplicar fade / blur al hero (0 -> sin cambio, 1 -> más oculto)
  // Eliminamos el desenfoque del hero durante el scroll progresivo.
  // Si quisieras también evitar cualquier cambio de opacidad, simplemente no lo modificamos.
  // (Antes se aplicaba blur y reducción de opacidad conforme avanzaba el overlay.)
  if(heroRoot){
    heroRoot.style.filter = '';// aseguramos que no haya blur residual
    heroRoot.style.opacity = '';// mantiene opacidad original
  }
  if(clamped >= 1 && !overlayShown){
    overlayShown = true;
  // Ya llegado al 100%, podemos quitar sync-progress para permitir animaciones internas sin bloqueo
  overlay.classList.remove('sync-progress');
    overlay.classList.add('active');
    overlay.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
    // Señal global para estilos al 100%
    document.body.classList.add('overlay-active');
  // Al estar activo dejamos de capturar el scroll global para que funcione el scroll interno del overlay
  captureVirtualScroll = false; // se reactivará si el usuario inicia gesto de retroceso
  overlay.style.borderRadius = '0';
    overlay.querySelectorAll('.frame-card').forEach(el=>io.observe(el));
    // Asegurar que el estado intermedio quede desactivado
    overlay.classList.remove('half');
    document.body.classList.remove('half-state');
    // Habilitar foco en colecciones al 100%
    setCollectionsFocusable(true);
  }

  // (Auto-hide removido: ahora se controla manualmente en el primer gesto de usuario)
  // Si por algún motivo overlayShown es falso (ej. cierre) aseguramos captura activa
  if(!overlayShown && !captureVirtualScroll){
    captureVirtualScroll = true;
  }

  // Si dejamos el 100% (p.ej., retrocedemos al 50%), quitar foco habilitado
  if(clamped < 1){
    setCollectionsFocusable(false);
  }
}

function showOverlayInstant(){
  setOverlayProgress(1);
}
function hideOverlay(){
  if(overlayShown){
    overlayShown = false;
    overlay.removeAttribute('data-progress');
    overlay.classList.remove('active');
    document.body.style.overflow='';
    document.body.classList.remove('overlay-active');
    overlay.style.transform = 'translateY(100%)';
    if(heroRoot){
      heroRoot.style.filter='';
      heroRoot.style.opacity='';
    }
    window.scrollTo({top:0,behavior:'smooth'});
  // Reiniciamos captura para volver a usar scroll virtual
  captureVirtualScroll = true;
  virtualProgress = 0;
  renderedProgress = 0; // reinicia easing
  stepIndex = 0; // permite que el primer scroll vuelva a llevar al 50%
  setOverlayProgress(0);
  overlay.style.borderRadius = '34px 34px 0 0';
    // Restaurar "EVERY DAY" para que el flujo 0→50% pueda repetirse
    if(everyImg){
      // Limpia animaciones previas y re-habilita puntero
      everyImg.style.animation = '';
      everyImg.style.pointerEvents = '';
      // Reaparece suavemente
      everyImg.style.transition = 'opacity 220ms cubic-bezier(.22,.61,.36,1), transform 220ms cubic-bezier(.22,.61,.36,1)';
      everyImg.style.opacity = '1';
      everyImg.style.transform = 'translateY(0) scaleX(var(--everyday-scale-x))';
      // Marca estado como visible para permitir que hideEveryday() vuelva a actuar en el siguiente gesto
      everydayHidden = false;
      hideInProgress = false;
    }
  }
}

// Detectar primer scroll fuerte hacia abajo
let lastY = window.scrollY;
// Eliminamos efecto de scroll nativo: no se necesita listener 'scroll' para progreso

// También permitir activar por gesto wheel fuerte sin desplazamiento (ej. en top con wheel)
if (overlay) window.addEventListener('wheel', (e)=>{
  // En modo captura virtual: usamos la rueda para modificar el progreso SIN desplazar la página.
  if(captureVirtualScroll && !overlayShown){
    e.preventDefault(); // bloquea scroll físico
    const delta = e.deltaY;
    // Sólo reaccionamos a gestos suficientemente claros (>4 abs) para evitar micro movimientos de touchpad
    if(Math.abs(delta) < 4) return;
    if(delta > 0){
      // Avanzar
      if(stepIndex === 0){
        // Avanza al 50% con micro-easing suave
        goToHalfSmooth();
        return; // evitar iniciar el loop de easing estándar en este gesto
      } else if(stepIndex === 1){
        virtualProgress = 1;
        stepIndex = 2;
        requestSmoothUpdate();
      }
    } else { // delta < 0, retroceder
      if(stepIndex === 1){
        virtualProgress = 0;
        stepIndex = 0;
        requestSmoothUpdate();
      } else if(stepIndex === 2){
        // (Opcional) permitir retroceder al estado parcial si aún no se activó overlayShown (pero ya se activa al 100%)
        // Si quieres cerrar con scroll arriba, descomenta siguiente bloque:
        // hideOverlay(); stepIndex = 1; virtualProgress = partialProgress; requestSmoothUpdate();
      }
    }
  } else if(overlayShown){
    // Ya en 100%: gesto hacia arriba vuelve a 50%
    if(e.deltaY < 0){
      e.preventDefault();
      goBackToHalfSmooth();
    }
  }
}, {passive:false});

// En estado 100%, forzar que un solo scroll hacia arriba (sobre el overlay) vuelva a 50%
// Capturamos en fase de captura para evitar que el scroll interno consuma el gesto
overlay?.addEventListener('wheel', (e)=>{
  if(overlayShown && e.deltaY < 0){
    e.preventDefault();
    e.stopPropagation();
    goBackToHalfSmooth();
  }
}, {passive:false, capture:true});

// Soporte teclado (flechas / PageUp / PageDown / Space) mientras aún no está activo
window.addEventListener('keydown', (e)=>{
  // Si el foco está en un campo editable (inputs, textareas, contenteditable), no interceptar
  const t = e.target;
  const ae = document.activeElement;
  if (
    (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) ||
    (ae && (ae.tagName === 'INPUT' || ae.tagName === 'TEXTAREA' || ae.isContentEditable))
  ) {
    return; // permitir escribir espacios y demás teclas en formularios
  }
  // Si estamos en 100%, permitir volver a 50% con ArrowUp/PageUp
  if(overlayShown){
    if(e.key==='ArrowUp' || e.key==='PageUp'){
      e.preventDefault();
      goBackToHalfSmooth();
    }
    return;
  }
  if(!captureVirtualScroll) return;
  const stepSmall = 0.04;
  const stepBig = 0.15;
  let used = false;
  switch(e.key){
    case 'ArrowDown': virtualProgress += stepSmall; used=true; break;
    case 'ArrowUp': virtualProgress -= stepSmall; used=true; break;
    case 'PageDown': virtualProgress += stepBig; used=true; break;
    case 'PageUp': virtualProgress -= stepBig; used=true; break;
    case ' ': virtualProgress += stepSmall; used=true; break; // Space avanza
  }
  if(used){
    e.preventDefault();
    if(stepIndex === 0){
      goToHalfSmooth();
      return; // no iniciar smoothing estándar en este gesto
    } else if(stepIndex === 1){
      virtualProgress = 1; stepIndex = 2;
    }
    requestSmoothUpdate();
  }
});

// Soporte tactil (touch) para móviles
let touchStartY = null;
window.addEventListener('touchstart', (e)=>{
  touchStartY = e.touches[0].clientY;
}, {passive:true});
window.addEventListener('touchmove', (e)=>{
  if(touchStartY === null) return;
  const currentY = e.touches[0].clientY;
  const delta = touchStartY - currentY; // gesto arriba -> positivo
  // Si estamos en 100%, un swipe hacia abajo vuelve a 50%
  if(overlay && overlayShown){
    if(delta < -10){
      e.preventDefault();
      goBackToHalfSmooth();
      touchStartY = currentY;
    }
    return;
  }
  if(overlay && !overlayShown && captureVirtualScroll){
    e.preventDefault();
    if(Math.abs(delta) > 10){
      if(delta > 0){ // swipe up avanza
        if(stepIndex === 0){ goToHalfSmooth(); return; }
        else if(stepIndex === 1){virtualProgress = 1; stepIndex = 2; requestSmoothUpdate();}
      } else { // swipe down retrocede
        if(stepIndex === 1){virtualProgress = 0; stepIndex = 0; requestSmoothUpdate();}
      }
      touchStartY = currentY; // reinicia para siguientes gestos
    }
  }
}, {passive:false});
window.addEventListener('touchend', ()=>{touchStartY=null;}, {passive:true});

// Cerrar overlay con botón o tecla ESC
overlay?.querySelector('.close-products')?.addEventListener('click', hideOverlay);
document.querySelector('.open-products-btn')?.addEventListener('click', showOverlayInstant);
window.addEventListener('keydown', (e)=>{
  if(e.key==='Escape') hideOverlay();
});

// Abrir con tecla Enter cuando foco en body y no mostrado aún (accesibilidad rápida)
window.addEventListener('keydown', (e)=>{
  if(!overlayShown && (e.key==='Enter' && e.metaKey)) showOverlayInstant();
});

// Exponer helpers para consola
window.__catalog = {showOverlay:showOverlayInstant, hideOverlay, setOverlayProgress};

// Inicializar posición overlay si manual
if(manualProgressMode && overlay){
  overlay.style.transform = 'translateY(100%)';
  overlay.style.borderRadius = '34px 34px 0 0';
}

// --- Navegación de colecciones (al 100%) ---
function getCollectionTarget(card){
  try{
    const txt = card.querySelector('.meta')?.textContent || '';
    const year = (txt.match(/20\d{2}/) || [null])[0];
    const url = 'productos.html' + (year ? (`?c=${encodeURIComponent(year)}`) : '');
    return url;
  } catch { return 'productos.html'; }
}
// Habilitar/deshabilitar foco/ARIA cuando está al 100%
function setCollectionsFocusable(enabled){
  const cards = overlay?.querySelectorAll('.collections-row .col-card');
  if(!cards) return;
  cards.forEach(card=>{
    if(enabled){
      card.setAttribute('tabindex','0');
      card.setAttribute('role','link');
      card.setAttribute('aria-label', (card.querySelector('.meta')?.textContent?.trim() || 'Open collection'));
    } else {
      card.removeAttribute('tabindex');
      card.removeAttribute('role');
      card.removeAttribute('aria-label');
    }
  });
}
// Event delegation: click/keyboard
overlay?.querySelector('.collections-row')?.addEventListener('click', (e)=>{
  const card = e.target.closest('.col-card');
  if(!card) return;
  if(!overlayShown) return; // solo navegar al 100%
  const url = getCollectionTarget(card);
  window.location.href = url;
});
overlay?.querySelector('.collections-row')?.addEventListener('keydown', (e)=>{
  if(e.key !== 'Enter' && e.key !== ' ') return;
  const card = e.target.closest('.col-card');
  if(!card) return;
  if(!overlayShown) return;
  e.preventDefault();
  const url = getCollectionTarget(card);
  window.location.href = url;
});

// --- Hero menu active indicator ---
document.querySelectorAll('.hero-menu a').forEach(a=>{
  a.addEventListener('click', (ev)=>{
    const href = (a.getAttribute('href') || '').trim();
    const isHash = href === '' || href === '#' || href.startsWith('#') || href.startsWith('javascript:');
    if (!isHash) {
      // Es un enlace real (p.ej. productos.html): permitir navegación
      return;
    }
    // Para enlaces ancla: sólo demo de indicador
    ev.preventDefault();
    document.querySelectorAll('.hero-menu a').forEach(x=>{
      x.classList.remove('active');
      x.removeAttribute('aria-current');
    });
    a.classList.add('active');
    a.setAttribute('aria-current','page');
  });
});

// --- Sync newsletter input width with links row ---
function syncNewsletterWidth(){
  const hlinks = document.querySelector('.hero-newsletter .hlinks');
  const hform = document.querySelector('.hero-newsletter .hform');
  if(!hlinks || !hform) return;
  const rect = hlinks.getBoundingClientRect();
  // Fudge +8px to account for subpixel and padding perception
  const target = Math.max(260, Math.ceil(rect.width) + 8);
  hform.style.width = `${target}px`;
  // tras ajustar el ancho, re-alinear el reproductor bajo el input
  alignPlayerToEmailCenter();
}
// Run after fonts load for accurate text metrics
if('fonts' in document && document.fonts?.ready){
  document.fonts.ready.then(syncNewsletterWidth);
} else {
  window.addEventListener('load', syncNewsletterWidth, {once:true});
}
// Recompute on resize
let _nwTimer;
window.addEventListener('resize', ()=>{
  clearTimeout(_nwTimer);
  _nwTimer = setTimeout(syncNewsletterWidth, 120);
});
// Initial call as fallback
syncNewsletterWidth();

// Alinear el reproductor al centro del input "YOUR EMAIL"
function alignPlayerToEmailCenter(nudgePx = -8){ // nudge negativo = mover un poquito a la izquierda
  const heroUI = document.querySelector('.hero-ui');
  const hform = document.querySelector('.hero-newsletter .hform');
  const player = document.querySelector('.hero-player');
  if(!heroUI || !hform || !player || getComputedStyle(hform).display === 'none') return;
  const uiRect = heroUI.getBoundingClientRect();
  const formRect = hform.getBoundingClientRect();
  const playerRect = player.getBoundingClientRect();
  const formCenter = formRect.left + formRect.width/2;
  const desiredLeft = Math.round(formCenter - (playerRect.width/2) - uiRect.left + nudgePx);
  // Fijar por left y anular right para que el inline style domine
  player.style.left = desiredLeft + 'px';
  player.style.right = 'auto';
}
// Ejecutar al cargar y en resize
window.addEventListener('load', ()=> alignPlayerToEmailCenter(), {once:true});
window.addEventListener('resize', ()=>{
  clearTimeout(_nwTimer);
  _nwTimer = setTimeout(()=>{ syncNewsletterWidth(); alignPlayerToEmailCenter(); }, 120);
});

// --- Hero Music Player ---
(function initHeroPlayer(){
  const audio = document.getElementById('heroAudio');
  const playBtn = document.querySelector('.hero-player .album-art .play-btn');
  const prevBtn = document.querySelector('.hero-player .album-art .prev-btn');
  const nextBtn = document.querySelector('.hero-player .album-art .next-btn');
  const muteBtn = document.querySelector('.hero-player .album-art .mute-btn');
  const fill = document.querySelector('.hero-player .album-art .progress-fill');
  if(!audio || !playBtn || !fill) return;
  let playing = false;
  let suppressAutoUnmute = false; // si el usuario manipula mute, no forzar desmuteo automático
  // Intentar autoplay al cargar: si falla, activar mute y volver a intentar
  function tryAutoPlay(){
    // Algunos navegadores permiten autoplay sólo si está muteado
    const attempt = audio.play();
    if(attempt && typeof attempt.then === 'function'){
      attempt.catch(()=>{
        audio.muted = true;
        muteBtn?.setAttribute('aria-pressed','true');
        if(muteBtn) muteBtn.textContent = '🔇';
        return audio.play().catch(()=>{});
      });
    }
  }
  // Si el atributo autoplay está presente, intentar al iniciar
  if(audio.hasAttribute('autoplay')){
    // Con atributo muted en HTML, esto debería reproducir en silencio
    tryAutoPlay();
    // Reintentar cuando la pestaña se vuelve visible (algunos navegadores bloquean en segundo plano)
    document.addEventListener('visibilitychange', ()=>{
      if(document.visibilityState === 'visible' && audio.paused){
        tryAutoPlay();
      }
    });
    // Reintento adicional tras DOMContentLoaded
    window.addEventListener('DOMContentLoaded', ()=>{
      if(audio.paused){ tryAutoPlay(); }
    });
  }
  function toggle(){
    if(playing){ audio.pause(); }
    else { audio.play().catch(()=>{}); }
  }
  playBtn.addEventListener('click', toggle);
  prevBtn?.addEventListener('click', (e)=>{ e.preventDefault(); audio.currentTime = 0; });
  nextBtn?.addEventListener('click', (e)=>{ e.preventDefault(); audio.currentTime = Math.max(0, audio.currentTime + 10); });
  // Mute / Unmute
  muteBtn?.addEventListener('click', (e)=>{
    e.preventDefault();
    audio.muted = !audio.muted;
    // El usuario tomó una decisión explícita, no auto-desmutear
    suppressAutoUnmute = true;
    if(audio.muted){
      muteBtn.textContent = '🔇';
      muteBtn.setAttribute('aria-label','Activar sonido');
      muteBtn.setAttribute('aria-pressed','true');
    } else {
      muteBtn.textContent = '🔊';
      muteBtn.setAttribute('aria-label','Silenciar');
      muteBtn.setAttribute('aria-pressed','false');
    }
    // Si el usuario activa sonido y no está reproduciendo, intenta reproducir
    if(!audio.muted && !playing){ audio.play().catch(()=>{}); }
  });
  audio.addEventListener('play', ()=>{ playing = true; playBtn.textContent = '⏸'; });
  audio.addEventListener('pause', ()=>{ playing = false; playBtn.textContent = '▶'; });
  audio.addEventListener('timeupdate', ()=>{
    if(!audio.duration) return;
    const p = Math.min(100, Math.max(0, (audio.currentTime / audio.duration) * 100));
    fill.style.width = p + '%';
  });
  // Estado inicial del botón de mute
  if(muteBtn){
    if(audio.muted){
      muteBtn.textContent = '🔇';
      muteBtn.setAttribute('aria-pressed','true');
    } else {
      muteBtn.textContent = '🔊';
      muteBtn.setAttribute('aria-pressed','false');
    }
  }

  // Auto-desmuteo: al primer movimiento del cursor tras cargar
  function setupAutoUnmuteOnPointerMove(){
    const handler = ()=>{
      if(!suppressAutoUnmute && audio.muted){
        audio.muted = false;
        if(muteBtn){ muteBtn.textContent = '🔊'; muteBtn.setAttribute('aria-pressed','false'); }
      }
      if(audio.paused){ audio.play().catch(()=>{}); }
      cleanup();
    };
    const cleanup = ()=>{
      window.removeEventListener('mousemove', handler);
      window.removeEventListener('pointermove', handler);
    };
    window.addEventListener('mousemove', handler, {once:false});
    window.addEventListener('pointermove', handler, {once:false});
  }
  setupAutoUnmuteOnPointerMove();
  // Al primer gesto de usuario sobre play, si estaba muteado por política y el usuario quiere sonido, desmutear
  playBtn.addEventListener('click', ()=>{
    if(audio.muted){
      audio.muted = false;
      if(muteBtn){ muteBtn.textContent = '🔊'; muteBtn.setAttribute('aria-pressed','false'); }
    }
  }, {once:false});
  // Si el autoplay quedó bloqueado, enganchar el primer gesto global (scroll/touch/keydown) para iniciar en silencio
  function kickOnFirstGesture(){
    const kick = ()=>{
      if(audio.paused){ audio.play().catch(()=>{}); }
      window.removeEventListener('wheel', kick, {passive:false});
      window.removeEventListener('touchstart', kick, {passive:true});
      window.removeEventListener('keydown', kick);
      document.removeEventListener('pointerdown', kick);
    };
    window.addEventListener('wheel', kick, {passive:false});
    window.addEventListener('touchstart', kick, {passive:true});
    window.addEventListener('keydown', kick);
    document.addEventListener('pointerdown', kick);
  }
  if(audio.paused){ kickOnFirstGesture(); }
})();

// --- Secuencia inicial del hero ---
const bodyEl = document.body;
const bgWord = document.querySelector('.bg-word, .bg-word-img img');
if(bodyEl.classList.contains('intro-seq') && bgWord){
  // Cuando termine la animación de la palabra, activar siguiente fase
  bgWord.addEventListener('animationend', ()=>{
    bodyEl.classList.add('hero-done');
    // Activar efecto flotante/glow de EVERYDAY tras pequeña pausa
  // Animación continua ahora declarada directamente en CSS; no se necesita añadir clase.
    // Iniciar observer después para evitar revelar antes de tiempo
    document.querySelectorAll('.frame-card').forEach(el=>io.observe(el));
  }, {once:true});
  // Fallback por si la animación no dispara
  setTimeout(()=>{
    if(!bodyEl.classList.contains('hero-done')){
      bodyEl.classList.add('hero-done');
  // Sin necesidad de añadir clase para iniciar efecto.
      bgWord.style.opacity='.18';
      bgWord.style.transform='translate(-50%, 8%)';
      document.querySelectorAll('.frame-card').forEach(el=>io.observe(el));
    }
  }, 2200);
} else {
  // fallback
  document.querySelectorAll('.frame-card').forEach(el=>io.observe(el));
}

// --- Prevención de restauración de scroll al recargar ---
// El comportamiento que veías (el overlay avanzando un % cada vez que recargabas)
// ocurre porque el navegador recuerda la posición de scroll previa (scroll restoration)
// y nuestra lógica interpreta ese scrollY inicial > 0 como progreso.
// Forzamos scrollRestoration manual y reseteamos el progreso.
if('scrollRestoration' in history){
  history.scrollRestoration = 'manual';
}
window.addEventListener('DOMContentLoaded', ()=>{
  window.scrollTo(0,0);
  // Aseguramos que el overlay arranca al 0%
  if(!overlayShown){
    setOverlayProgress(0);
  }
});
