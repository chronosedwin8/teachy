(function () {
  'use strict';
  var P = window.PAY;
  if (!P) return;

  var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
  var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };
  var errorBox = $('#payError');
  var loading = $('#payLoading');
  var fmt = function (n) { return P.money.symbol + Math.round(n).toLocaleString('es-CO') + ' ' + P.money.currency; };

  function showError(msg) {
    errorBox.textContent = msg;
    errorBox.hidden = false;
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  function clearError() { errorBox.hidden = true; }
  function busy(on, text) {
    loading.hidden = !on;
    if (text) $('#payLoadingText').textContent = text;
    $$('.pay-btn').forEach(function (b) { b.disabled = on; });
  }

  // ---------------- Pestañas de medios de pago
  $$('.method-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      clearError();
      $$('.method-tab').forEach(function (t) { t.classList.toggle('active', t === tab); });
      $$('.method-panel').forEach(function (p) { p.classList.toggle('active', p.dataset.method === tab.dataset.method); });
    });
  });

  // PSE: persona jurídica no requiere apellidos
  $$('input[name="entity_type"]').forEach(function (r) {
    r.addEventListener('change', function () {
      var company = r.value === 'association' && r.checked;
      $('#pse_last').closest('.field').style.display = company ? 'none' : '';
      $('label[for="pse_first"]').textContent = company ? 'Razón social' : 'Nombres';
    });
  });

  // ---------------- Envío al servidor
  function post(data) {
    var body = new URLSearchParams();
    body.append('csrf', P.csrf);
    body.append('ref', P.ref);
    body.append('device_id', window.mpDeviceId || window.MP_DEVICE_SESSION_ID || '');
    Object.keys(data).forEach(function (k) { if (data[k] !== undefined && data[k] !== null) body.append(k, data[k]); });
    return fetch(P.process, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return { status: 'error', message: 'Respuesta inesperada del servidor.' }; }); });
  }

  function handle(res) {
    switch (res.status) {
      case 'approved':
        busy(true, '¡Pago aprobado! Activando tu licencia…');
        window.location.href = res.redirect;
        break;
      case 'redirect':
        busy(true, 'Te estamos llevando a tu banco…');
        window.location.href = res.url;
        break;
      case 'voucher':
        busy(true, 'Generando tu recibo…');
        window.location.href = res.redirect;
        break;
      case 'challenge':
        busy(false);
        openChallenge(res.url, res.creq);
        break;
      case 'in_process':
        busy(true, res.message);
        setTimeout(function () { window.location.href = res.redirect; }, 3500);
        break;
      default:
        busy(false);
        showError(res.message || 'No pudimos procesar el pago.');
    }
  }

  function formData(form) {
    var out = {};
    new FormData(form).forEach(function (v, k) { out[k] = v; });
    return out;
  }

  function requireFields(form) {
    var missing = $$('[required]', form).filter(function (el) { return el.offsetParent !== null && !String(el.value).trim(); });
    if (missing.length) { missing[0].focus(); showError('Completa los campos obligatorios.'); return false; }
    return true;
  }

  ['pse', 'efecty'].forEach(function (method) {
    var form = $('#form-' + method);
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      clearError();
      if (!requireFields(form)) return;
      busy(true, method === 'pse' ? 'Conectando con PSE…' : 'Generando tu recibo…');
      var data = formData(form);
      data.method = method;
      post(data).then(handle).catch(function () { busy(false); showError('Error de conexión. Intenta de nuevo.'); });
    });
  });

  // ---------------- Tarjeta
  var cardForm = $('#form-card');
  var holder = $('#cardholder');
  holder.addEventListener('input', function () {
    holder.value = holder.value.toUpperCase();
    $('#cvName').textContent = holder.value || 'NOMBRE DEL TITULAR';
  });
  $('#cvName').textContent = holder.value || 'NOMBRE DEL TITULAR';

  var card = { paymentMethodId: null, paymentTypeId: null, issuerId: null };
  var mp = null;

  if (P.live && window.MercadoPago) {
    mp = new window.MercadoPago(P.publicKey, { locale: 'es-CO' });
    var style = { height: '100%', fontSize: '16px', fontFamily: 'Quicksand, "Segoe UI", sans-serif', fontWeight: '600', color: '#0F151A', placeholderColor: '#ADC0CC' };
    var fields = {
      cardNumber: mp.fields.create('cardNumber', { placeholder: '0000 0000 0000 0000', style: style }).mount('cardNumber'),
      expirationDate: mp.fields.create('expirationDate', { placeholder: 'MM/AA', style: style }).mount('expirationDate'),
      securityCode: mp.fields.create('securityCode', { placeholder: '123', style: style }).mount('securityCode'),
    };
    Object.keys(fields).forEach(function (id) {
      var box = document.getElementById(id);
      fields[id].on('focus', function () { box.classList.add('focus'); });
      fields[id].on('blur', function () { box.classList.remove('focus'); });
      fields[id].on('validityChange', function (e) { box.classList.toggle('invalid', e.errorMessages && e.errorMessages.length > 0); });
    });

    fields.cardNumber.on('binChange', function (e) {
      var bin = e.bin;
      if (!bin) {
        card.paymentMethodId = null;
        $('#cvBrand').hidden = true;
        $('#cardVisual').className = 'card-visual';
        return;
      }
      $('.cv-number').textContent = bin.slice(0, 4) + ' ' + bin.slice(4, 6) + '•• •••• ••••';
      mp.getPaymentMethods({ bin: bin }).then(function (r) {
        var pm = r.results && r.results[0];
        if (!pm) { showError('No reconocemos esta tarjeta. Verifica el número.'); return; }
        clearError();
        card.paymentMethodId = pm.id;
        card.paymentTypeId = pm.payment_type_id;
        card.issuerId = pm.issuer ? pm.issuer.id : null;
        var img = $('#cvBrand');
        img.src = pm.secure_thumbnail || pm.thumbnail;
        img.alt = pm.name;
        img.hidden = false;
        $('#cardVisual').className = 'card-visual brand-' + pm.id;
        if (pm.settings && pm.settings[0]) {
          fields.cardNumber.update({ settings: pm.settings[0].card_number });
          fields.securityCode.update({ settings: pm.settings[0].security_code });
        }
        loadInstallments(bin, pm.payment_type_id);
      }).catch(function () {});
    });
  }

  function loadInstallments(bin, typeId) {
    var select = $('#installments');
    var field = $('#installmentsField');
    if (typeId !== 'credit_card') {
      field.style.display = 'none';
      select.innerHTML = '<option value="1">1</option>';
      return;
    }
    field.style.display = '';
    mp.getInstallments({ amount: String(P.amount), bin: bin, paymentTypeId: typeId }).then(function (r) {
      var costs = r && r[0] ? r[0].payer_costs : [];
      if (r && r[0] && r[0].issuer) card.issuerId = r[0].issuer.id;
      if (!costs.length) return;
      select.innerHTML = '';
      costs.forEach(function (c) {
        var o = document.createElement('option');
        o.value = c.installments;
        o.textContent = c.recommended_message || (c.installments + ' cuota(s)');
        select.appendChild(o);
      });
      $('#installmentsHint').textContent = 'Los intereses de las cuotas los define tu banco.';
    }).catch(function () {});
  }

  cardForm.addEventListener('submit', function (ev) {
    ev.preventDefault();
    clearError();
    if (!requireFields(cardForm)) return;
    var data = formData(cardForm);
    data.method = 'card';

    if (!P.live) {
      busy(true);
      post(data).then(handle).catch(function () { busy(false); showError('Error de conexión.'); });
      return;
    }
    if (!mp) { showError('No se pudo cargar el módulo de pago seguro. Recarga la página.'); return; }
    if (!card.paymentMethodId) { showError('Ingresa un número de tarjeta válido.'); return; }

    busy(true, 'Validando tu tarjeta…');
    mp.fields.createCardToken({
      cardholderName: data.cardholder,
      identificationType: data.id_type,
      identificationNumber: data.id_number,
    }).then(function (token) {
      busy(true, 'Procesando tu pago…');
      data.token = token.id;
      data.payment_method_id = card.paymentMethodId;
      data.payment_type_id = card.paymentTypeId;
      if (card.issuerId) data.issuer_id = card.issuerId;
      return post(data).then(handle);
    }).catch(function () {
      busy(false);
      showError('Revisa el número, la fecha de vencimiento y el código de seguridad de la tarjeta.');
    });
  });

  // ---------------- 3D Secure
  var pollTimer = null;
  function openChallenge(url, creq) {
    var overlay = $('#tdsOverlay');
    overlay.hidden = false;
    var f = $('#tdsForm');
    f.action = url;
    $('#tdsCreq').value = creq;
    f.submit();
    var started = Date.now();
    pollTimer = setInterval(function () {
      if (Date.now() - started > 6 * 60 * 1000) { closeChallenge('La verificación tardó demasiado. Intenta de nuevo.'); return; }
      checkStatus();
    }, 5000);
  }
  function closeChallenge(msg) {
    clearInterval(pollTimer);
    $('#tdsOverlay').hidden = true;
    if (msg) showError(msg);
  }
  function checkStatus() {
    return fetch(P.status, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (s) {
      if (s.status === 'approved') { closeChallenge(); handle({ status: 'approved', redirect: s.redirect }); }
      else if (s.status === 'rejected' || s.status === 'cancelled') { closeChallenge(s.message || 'El banco no aprobó el pago.'); }
    }).catch(function () {});
  }
  window.addEventListener('message', function (e) {
    if (e.data && e.data.status === 'COMPLETE') {
      $('#tdsOverlay').hidden = true;
      busy(true, 'Confirmando con tu banco…');
      setTimeout(checkStatus, 1500);
      setTimeout(function () { busy(false); }, 12000);
    }
  });
})();
