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
          <li><a href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp ventas</a></li>
          <li><a href="#">Términos y condiciones</a></li>
          <li><a href="#">Política de privacidad</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(BRAND_NAME) ?>. Todos los derechos reservados.</span>
      <span>Comercio electrónico internacional operado desde <?= e(SELLER_COUNTRY) ?> · Pagos seguros con tarjeta, PSE y Efecty</span>
    </div>
  </div>
</footer>
<a class="wa-float" href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>?text=<?= rawurlencode('Hola, quiero información sobre las licencias de ' . BRAND_NAME) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-.9 1.2-.3.2-.6.1a8.2 8.2 0 01-4-3.5c-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 00-.9.4 3.7 3.7 0 00-1.1 2.7 6.4 6.4 0 001.3 3.4 14.6 14.6 0 005.6 4.9c2.1.9 2.9 1 3.9.8a3.3 3.3 0 002.2-1.5 2.7 2.7 0 00.2-1.5c-.1-.2-.3-.2-.6-.4zM12 21.8a9.8 9.8 0 01-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1112 21.8zM12 0a12 12 0 00-10.3 18L0 24l6.2-1.6A12 12 0 1012 0z"/></svg>
</a>
<script src="<?= url('assets/js/main.js') ?>?v=1"></script>
</body>
</html>
