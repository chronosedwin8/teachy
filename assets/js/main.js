(function () {
  'use strict';

  // Navegación: sombra al hacer scroll y menú móvil
  var nav = document.getElementById('nav');
  var toggle = document.getElementById('navToggle');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    nav.querySelectorAll('.nav-links a').forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); });
    });
  }

  // Pestañas del sistema integrado (con rotación automática hasta que el usuario interactúa)
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab[data-tab]'));
  var autoTimer = null;
  function activate(tab) {
    tabs.forEach(function (t) {
      var on = t === tab;
      t.classList.toggle('active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      var panel = document.getElementById('panel-' + t.dataset.tab);
      if (panel) panel.classList.toggle('active', on);
    });
  }
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () { clearInterval(autoTimer); activate(tab); });
  });
  if (tabs.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var section = document.getElementById('sistema');
    var visible = false;
    if ('IntersectionObserver' in window && section) {
      new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; }, { threshold: .3 }).observe(section);
    }
    autoTimer = setInterval(function () {
      if (!visible) return;
      var i = tabs.findIndex(function (t) { return t.classList.contains('active'); });
      activate(tabs[(i + 1) % tabs.length]);
    }, 6000);
  }

  // Preguntas frecuentes (acordeón)
  document.querySelectorAll('.faq-item').forEach(function (item) {
    var q = item.querySelector('.faq-q');
    var a = item.querySelector('.faq-a');
    q.addEventListener('click', function () {
      var open = !item.classList.contains('open');
      item.classList.toggle('open', open);
      q.setAttribute('aria-expanded', open ? 'true' : 'false');
      a.style.maxHeight = open ? a.scrollHeight + 'px' : '0';
    });
  });

  // Aparición progresiva de elementos
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); }
      });
    }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  // Confirmaciones en formularios (portal)
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  // Copiar al portapapeles
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () { var o = btn.textContent; btn.textContent = '¡Copiado!'; setTimeout(function () { btn.textContent = o; }, 1500); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done); else done();
    });
  });

  // Mostrar/ocultar paneles simples (portal)
  document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.getAttribute('data-toggle'));
      if (target) target.hidden = !target.hidden;
    });
  });
})();
