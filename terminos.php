<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Términos y condiciones del servicio | ' . BRAND_NAME;
$pageDescription = 'Condiciones de contratación y uso de las licencias de ' . BRAND_NAME . '.';
$extraCss = ['assets/css/portal.css', 'assets/css/legal.css'];
include __DIR__ . '/includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <article class="legal">
      <span class="eyebrow"><span class="dot"></span> Información legal</span>
      <h1>Términos y condiciones del servicio</h1>
      <p class="legal-date">Última actualización: <?= fmt_date(LEGAL_UPDATED) ?></p>

      <nav class="legal-nav">
        <a href="<?= url('terminos.php') ?>" class="on">Términos y condiciones</a>
        <a href="<?= url('privacidad.php') ?>">Política de privacidad</a>
        <a href="<?= url('reembolsos.php') ?>">Política de reembolso</a>
      </nav>

      <?php include __DIR__ . '/includes/legal_identity.php'; ?>

      <h2>1. Objeto</h2>
      <p>Estos términos regulan la contratación y el uso de las licencias de <?= e(BRAND_NAME) ?>, una plataforma web con
        inteligencia artificial para que las instituciones educativas planeen clases, creen materiales, evalúen y hagan
        seguimiento del aprendizaje. Al comprar una licencia o usar la plataforma, el cliente acepta estos términos.</p>

      <h2>2. Definiciones</h2>
      <ul>
        <li><strong>Cliente:</strong> la institución educativa o la persona que contrata la licencia y figura como titular.</li>
        <li><strong>Licencia:</strong> el derecho de uso de la plataforma por un plazo determinado, con un número máximo de
          usuarios y de sedes según el plan contratado.</li>
        <li><strong>Usuario:</strong> la persona (docente, directivo, coordinador u otro perfil) que el cliente autoriza a
          usar la plataforma dentro de los cupos de su licencia.</li>
        <li><strong>Portal de clientes:</strong> el área privada donde el cliente consulta su licencia y gestiona sus usuarios.</li>
      </ul>

      <h2>3. Licencias y planes</h2>
      <p>Las características, el número de usuarios, las sedes y el plazo de cada plan son los que se publican en la
        <a href="<?= url('index.php#precios') ?>">página de precios</a> en el momento de la compra. La licencia es personal
        del cliente y no se puede revender, sublicenciar ni cederse a terceros sin autorización escrita.</p>
      <p>El cliente puede añadir, modificar o retirar usuarios en cualquier momento desde el portal, siempre que no supere
        los cupos de su plan. Cada usuario debe ser una persona identificada; no se permite compartir una misma cuenta entre
        varias personas.</p>

      <h2>4. Precios, pago y activación</h2>
      <p>Los precios se publican en pesos colombianos (COP) e incluyen el valor total a pagar por el periodo contratado.
        <?= e(BRAND_NAME) ?> no incluye, no recauda ni asume impuestos, tasas o políticas tributarias de países de
        Latinoamérica; si la legislación del país del cliente exige algún impuesto, retención o declaración sobre la compra,
        su cumplimiento corresponde al cliente.</p>
      <p>El pago se realiza a través de la pasarela de pago habilitada en el sitio. <?= e(BRAND_NAME) ?> no recibe ni
        almacena los datos de la tarjeta del cliente. La licencia se activa una vez confirmado el pago, y el cliente lo verá
        reflejado en su portal. Si el pago no se confirma, el pedido queda pendiente y puede reintentarse o cancelarse.</p>

      <h2>5. Duración y renovación</h2>
      <p>La licencia tiene la vigencia indicada en el plan (por defecto, 12 meses) contada desde su activación. No hay
        renovación automática ni cobros recurrentes: al vencer, el cliente decide si contrata un nuevo periodo. Antes del
        vencimiento le avisaremos al correo registrado.</p>

      <h2>6. Obligaciones del cliente</h2>
      <ul>
        <li>Facilitar información veraz y mantenerla actualizada, incluidos los datos de la institución.</li>
        <li>Custodiar sus credenciales y las de sus usuarios, y comunicarnos cualquier acceso no autorizado.</li>
        <li>Usar la plataforma conforme a la ley y a estos términos, y responder por el uso que hagan sus usuarios.</li>
        <li>Obtener, cuando corresponda, las autorizaciones necesarias de las familias o de los titulares de los datos que
          cargue en la plataforma.</li>
      </ul>

      <h2>7. Uso aceptable</h2>
      <p>No está permitido: intentar vulnerar la seguridad de la plataforma, realizar ingeniería inversa, extraer
        masivamente contenidos, revender el acceso, subir contenidos ilícitos o que infrinjan derechos de terceros, ni usar
        el servicio para generar material discriminatorio, violento o inapropiado para el contexto escolar. El
        incumplimiento puede dar lugar a la suspensión de la licencia.</p>

      <h2>8. Contenidos y propiedad intelectual</h2>
      <p>La plataforma, su software, su marca y sus materiales base son propiedad de <?= e(BRAND_NAME) ?> o de sus
        licenciantes. El cliente conserva la titularidad de los contenidos que crea o carga, y nos autoriza a tratarlos en la
        medida necesaria para prestar el servicio. Los materiales generados con las herramientas de IA pueden usarse con
        fines educativos dentro de la institución; el cliente es responsable de revisar su exactitud y pertinencia antes de
        usarlos en el aula.</p>

      <h2>9. Inteligencia artificial</h2>
      <p>Las funciones de IA producen propuestas y borradores que requieren revisión docente. <?= e(BRAND_NAME) ?> no
        garantiza que los resultados sean exactos, completos ni adecuados para todos los contextos, y no sustituyen el
        criterio profesional del equipo educativo.</p>

      <h2>10. Disponibilidad y soporte</h2>
      <p>Trabajamos para mantener el servicio disponible de forma continua, pero puede haber interrupciones por
        mantenimiento, actualizaciones o causas ajenas a nuestro control. El soporte se presta por correo electrónico y
        WhatsApp en días hábiles, con los alcances que incluya el plan contratado.</p>

      <h2>11. Protección de datos</h2>
      <p>El tratamiento de datos personales se rige por nuestra <a href="<?= url('privacidad.php') ?>">Política de
        privacidad</a>. Respecto de los datos de estudiantes y miembros de la comunidad educativa que la institución cargue
        en la plataforma, el cliente actúa como responsable del tratamiento y <?= e(BRAND_NAME) ?> como encargado.</p>

      <h2>12. Reembolsos y cancelación</h2>
      <p>Las devoluciones se rigen por nuestra <a href="<?= url('reembolsos.php') ?>">Política de reembolso</a>. El cliente
        puede dejar de usar el servicio en cualquier momento; también podemos suspender o terminar la licencia si se
        incumplen estos términos, previo aviso cuando sea posible.</p>

      <h2>13. Responsabilidad</h2>
      <p>Prestamos el servicio con diligencia profesional. En la medida permitida por la ley, nuestra responsabilidad total
        frente al cliente se limita al importe pagado por la licencia en los 12 meses anteriores al hecho que la origine. No
        respondemos por daños indirectos, pérdida de datos imputable al cliente, lucro cesante ni por el uso que el cliente
        haga de los materiales generados. Nada de lo anterior limita los derechos que la ley reconoce a los consumidores.</p>

      <h2>14. Fuerza mayor</h2>
      <p>Ninguna de las partes responde por incumplimientos derivados de hechos fuera de su control razonable, como fallos
        generalizados de internet, de los proveedores de alojamiento, catástrofes o decisiones de autoridades.</p>

      <h2>15. Modificaciones</h2>
      <p>Podemos actualizar estos términos para reflejar cambios en el servicio o en la normativa. Publicaremos la nueva
        versión en esta página con su fecha de actualización y, si el cambio es relevante, avisaremos al correo registrado.
        Las condiciones aplicables a una compra son las vigentes en el momento de realizarla.</p>

      <h2>16. Ley aplicable y resolución de conflictos</h2>
      <p>Estos términos se rigen por la legislación española. Para los clientes que actúen como consumidores, serán
        competentes los juzgados de su domicilio cuando así lo disponga la normativa de consumo; en los demás casos, los
        juzgados correspondientes al domicilio del titular del sitio. Antes de acudir a la vía judicial, invitamos a
        escribirnos para buscar una solución.</p>

      <h2>17. Contacto</h2>
      <p>Para cualquier duda sobre estos términos: <a href="mailto:<?= e(LEGAL_EMAIL) ?>"><?= e(LEGAL_EMAIL) ?></a>.</p>
    </article>
  </div>
</main>
<?php include __DIR__ . '/includes/site_footer.php'; ?>
