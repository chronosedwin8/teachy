<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Política de reembolso | ' . BRAND_NAME;
$pageDescription = 'Condiciones para solicitar la devolución del pago de una licencia de ' . BRAND_NAME . '.';
$extraCss = ['assets/css/portal.css', 'assets/css/legal.css'];
include __DIR__ . '/includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <article class="legal">
      <span class="eyebrow"><span class="dot"></span> Información legal</span>
      <h1>Política de reembolso</h1>
      <p class="legal-date">Última actualización: <?= fmt_date(LEGAL_UPDATED) ?></p>

      <nav class="legal-nav">
        <a href="<?= url('terminos.php') ?>">Términos y condiciones</a>
        <a href="<?= url('privacidad.php') ?>">Política de privacidad</a>
        <a href="<?= url('reembolsos.php') ?>" class="on">Política de reembolso</a>
      </nav>

      <?php include __DIR__ . '/includes/legal_identity.php'; ?>

      <h2>1. Plazo para solicitar la devolución</h2>
      <p>Puede solicitar la devolución del importe pagado dentro de los <strong><?= (int) REFUND_DAYS ?> días naturales</strong>
        siguientes a la activación de su licencia, sin necesidad de justificar el motivo. Si la licencia aún no se ha
        activado porque el pago está en verificación, basta con pedirnos la anulación del pedido.</p>

      <h2>2. Cómo solicitarla</h2>
      <p>Escríbanos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> o por
        <a href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp</a> indicando:</p>
      <ul>
        <li>La referencia del pedido (aparece en su portal, en "Pedidos y pagos").</li>
        <li>El correo con el que compró la licencia.</li>
        <li>Si lo desea, el motivo: nos ayuda a mejorar, pero no es obligatorio dentro del plazo indicado.</li>
      </ul>
      <p>Confirmamos la recepción de la solicitud en un plazo máximo de 2 días hábiles.</p>

      <h2>3. Plazos y forma de la devolución</h2>
      <p>Una vez aprobada la solicitud, devolvemos el importe por el mismo medio de pago que usó, dentro de los
        <?= (int) REFUND_DAYS ?> días naturales siguientes. El tiempo que tarde en reflejarse en su cuenta o tarjeta depende
        de su banco o del medio de pago utilizado. La devolución no tiene ningún coste para usted.</p>
      <p>Al tramitarse la devolución, la licencia queda desactivada y sus usuarios pierden el acceso a la plataforma.</p>

      <h2>4. Casos en los que no procede la devolución</h2>
      <ul>
        <li>Solicitudes presentadas después de los <?= (int) REFUND_DAYS ?> días naturales desde la activación.</li>
        <li>Licencias con un uso sustancial del servicio, entendido como la generación de materiales o evaluaciones de forma
          intensiva, o la activación de la mayor parte de los usuarios incluidos en el plan.</li>
        <li>Licencias suspendidas o terminadas por incumplimiento de los
          <a href="<?= url('terminos.php') ?>">términos y condiciones</a>, en particular por uso indebido de la plataforma.</li>
        <li>Importes correspondientes a servicios adicionales ya prestados, como capacitaciones presenciales realizadas o
          materiales personalizados ya entregados.</li>
      </ul>
      <p>Si su caso no encaja exactamente en estos supuestos, escríbanos: estudiamos cada solicitud de forma individual.</p>

      <h2>5. Devoluciones parciales</h2>
      <p>Fuera del plazo indicado, las licencias no se devuelven de forma proporcional al tiempo no usado, salvo que exista
        una interrupción prolongada del servicio imputable a nosotros. En ese caso le ofreceremos la ampliación de la
        vigencia o la devolución de la parte proporcional afectada, a su elección.</p>

      <h2>6. Cobros duplicados o erróneos</h2>
      <p>Si detecta un cobro duplicado, un importe distinto al publicado o un pago que no reconoce, avísenos y lo
        devolveremos íntegramente, sin que corra ningún plazo. Le pedimos que nos escriba antes de abrir una disputa con su
        banco: así lo resolvemos más rápido.</p>

      <h2>7. Renovaciones</h2>
      <p>No aplicamos cobros automáticos ni renovaciones recurrentes: cada periodo se contrata y se paga de forma expresa,
        por lo que no existen cargos sorpresa por renovación.</p>

      <h2>8. Derecho de desistimiento de consumidores</h2>
      <p>Si contrata como consumidor y le resulta aplicable la normativa europea de consumo, dispone del derecho de
        desistimiento de 14 días naturales desde la celebración del contrato, en los términos del Real Decreto Legislativo
        1/2007. Al tratarse de contenido digital de ejecución inmediata, al activar la licencia usted solicita el inicio
        inmediato de la prestación; aun así, respetamos el plazo de <?= (int) REFUND_DAYS ?> días descrito en el punto 1, que
        es igual o más favorable.</p>

      <h2>9. Contacto</h2>
      <p>Atención al cliente: <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> ·
        <a href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp</a>.
        Respondemos en días hábiles.</p>
    </article>
  </div>
</main>
<?php include __DIR__ . '/includes/site_footer.php'; ?>
