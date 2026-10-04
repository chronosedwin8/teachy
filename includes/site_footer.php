<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="logo" href="<?= url('index.php') ?>"><?php include __DIR__ . '/logo.php'; ?><span><?= e(BRAND_NAME) ?></span></a>
        <p style="margin-top:16px"><?= e(BRAND_TAGLINE) ?>. Planeación, clases, evaluación y seguimiento en un solo lugar.</p>
        <div class="socials">
          <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="#323E47" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="#323E47"/></svg></a>
          <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="#323E47"><path d="M4.98 3.5a2.5 2.5 0 11-.01 5 2.5 2.5 0 01.01-5zM3 9h4v12H3zM9 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05C20.6 8.65 21 11.3 21 14.7V21h-4v-5.6c0-1.34-.03-3.06-1.86-3.06-1.87 0-2.14 1.46-2.14 2.96V21H9z"/></svg></a>
          <a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="#323E47"><path d="M23 7.2a3 3 0 00-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 001 7.2 31 31 0 00.5 12 31 31 0 001 16.8a3 3 0 002.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 002.1-2.1 31 31 0 00.5-4.8 31 31 0 00-.5-4.8zM9.8 15.1V8.9l5.7 3.1z"/></svg></a>
        </div>
      </div>
      <div>
        <h4>Producto</h4>
        <ul>
          <li><a href="<?= url('index.php#sistema') ?>">Sistema integrado</a></li>
          <li><a href="<?= url('index.php#ecosistema') ?>">Ecosistema</a></li>
          <li><a href="<?= url('index.php#studio') ?>">Studio</a></li>
          <li><a href="<?= url('index.php#precios') ?>">Precios</a></li>
        </ul>
      </div>
      <div>
        <h4>Clientes</h4>
        <ul>
          <li><a href="<?= url('portal/login.php') ?>">Portal de clientes</a></li>
          <li><a href="<?= url('checkout.php?plan=escuela') ?>">Licencia Escuela</a></li>
          <li><a href="<?= url('checkout.php?plan=volumen') ?>">Licencia por Volumen</a></li>
          <li><a href="<?= url('index.php#faq') ?>">Preguntas frecuentes</a></li>
        </ul>
      </div>
      <div>
        <h4>Contacto</h4>
        <ul>
          <li><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></li>
          <li><a href="<?= url('terminos.php') ?>">Términos y condiciones</a></li>
          <li><a href="<?= url('privacidad.php') ?>">Política de privacidad</a></li>
          <li><a href="<?= url('reembolsos.php') ?>">Política de reembolso</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(BRAND_NAME) ?>. Todos los derechos reservados.</span>
      <span>Comercio electrónico internacional operado desde <?= e(SELLER_COUNTRY) ?> · Pagos seguros con tarjeta, PSE y Efecty</span>
    </div>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>?v=1"></script>
</body>
</html>
