<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Política de privacidad | ' . BRAND_NAME;
$pageDescription = 'Cómo trata ' . BRAND_NAME . ' los datos personales de sus clientes y usuarios.';
$extraCss = ['assets/css/portal.css', 'assets/css/legal.css'];
include __DIR__ . '/includes/site_header.php';
?>
<main class="page-soft">
  <div class="container">
    <article class="legal">
      <span class="eyebrow"><span class="dot"></span> Información legal</span>
      <h1>Política de privacidad</h1>
      <p class="legal-date">Última actualización: <?= fmt_date(LEGAL_UPDATED) ?></p>

      <nav class="legal-nav">
        <a href="<?= url('terminos.php') ?>">Términos y condiciones</a>
        <a href="<?= url('privacidad.php') ?>" class="on">Política de privacidad</a>
        <a href="<?= url('reembolsos.php') ?>">Política de reembolso</a>
      </nav>

      <?php include __DIR__ . '/includes/legal_identity.php'; ?>

      <h2>1. Quién trata sus datos</h2>
      <p>El responsable del tratamiento de los datos recogidos en este sitio es el titular identificado arriba. Para
        cualquier asunto de privacidad puede escribirnos a
        <a href="mailto:<?= e(LEGAL_EMAIL) ?>"><?= e(LEGAL_EMAIL) ?></a>.</p>

      <h2>2. Qué datos recogemos</h2>
      <ul>
        <li><strong>Datos de la cuenta:</strong> nombre, correo electrónico, teléfono y contraseña (guardada siempre
          cifrada, nunca en texto legible).</li>
        <li><strong>Datos de la institución:</strong> nombre o razón social, documento de identificación fiscal y ciudad,
          necesarios para formalizar la compra.</li>
        <li><strong>Usuarios de la licencia:</strong> nombre, correo, rol y sede de las personas que el cliente autoriza a
          usar la plataforma.</li>
        <li><strong>Datos de los pedidos:</strong> plan contratado, importe, referencia, estado y fecha del pago.</li>
        <li><strong>Datos técnicos:</strong> dirección IP, fecha y hora de acceso y registros de errores, con fines de
          seguridad y diagnóstico.</li>
      </ul>
      <p><strong>No recibimos ni almacenamos datos de tarjetas de crédito o débito.</strong> El pago se realiza
        íntegramente en la pasarela de pago, que actúa como responsable de esos datos.</p>

      <h2>3. Para qué los usamos y con qué base legal</h2>
      <ul>
        <li><strong>Prestar el servicio y gestionar la licencia</strong> (ejecución del contrato): crear la cuenta, activar
          la licencia, dar soporte y atender la relación comercial.</li>
        <li><strong>Gestionar los cobros y la contabilidad</strong> (ejecución del contrato y obligación legal).</li>
        <li><strong>Seguridad del servicio</strong> (interés legítimo): prevenir accesos indebidos, fraude y abuso.</li>
        <li><strong>Comunicaciones sobre su licencia</strong> (ejecución del contrato): avisos de pago, activación y
          vencimiento.</li>
        <li><strong>Comunicaciones comerciales</strong> (consentimiento): solo si nos lo autoriza, y puede retirarlo cuando
          quiera.</li>
      </ul>

      <h2>4. Datos de estudiantes y de la comunidad educativa</h2>
      <p>Cuando una institución carga en la plataforma datos de estudiantes, familias u otros miembros de su comunidad, la
        institución es la responsable del tratamiento y <?= e(BRAND_NAME) ?> actúa como encargado: tratamos esos datos
        únicamente siguiendo sus instrucciones y para prestarle el servicio. No los usamos para fines propios ni los
        cedemos. La institución debe contar con las autorizaciones que exija la normativa de su país.</p>

      <h2>5. Quién puede acceder a sus datos</h2>
      <p>Solo nuestro personal autorizado y los proveedores que necesitamos para operar, que actúan como encargados y están
        sujetos a obligaciones de confidencialidad:</p>
      <ul>
        <li>Proveedor de alojamiento e infraestructura del sitio.</li>
        <li>Pasarela de pago, para procesar el cobro.</li>
        <li>Proveedores de correo electrónico y de soporte.</li>
      </ul>
      <p>No vendemos ni alquilamos datos personales. Solo los comunicamos a autoridades cuando exista una obligación legal.</p>

      <h2>6. Transferencias internacionales</h2>
      <p>Algunos proveedores pueden estar ubicados fuera del Espacio Económico Europeo. En esos casos nos apoyamos en las
        garantías previstas por la normativa, como las cláusulas contractuales tipo de la Comisión Europea o una decisión de
        adecuación.</p>

      <h2>7. Cuánto tiempo los conservamos</h2>
      <ul>
        <li>Datos de la cuenta y de la licencia: mientras la relación esté vigente.</li>
        <li>Datos de facturación y de pedidos: el plazo que exija la normativa mercantil y fiscal aplicable.</li>
        <li>Registros técnicos y de seguridad: por periodos cortos, salvo que deban conservarse para investigar un incidente.</li>
      </ul>
      <p>Después los eliminamos o los anonimizamos.</p>

      <h2>8. Sus derechos</h2>
      <p>Puede solicitar en cualquier momento el acceso a sus datos, su rectificación o supresión, la limitación u oposición
        al tratamiento, y la portabilidad. También puede retirar el consentimiento que nos haya dado. Para ejercerlos,
        escriba a <a href="mailto:<?= e(LEGAL_EMAIL) ?>"><?= e(LEGAL_EMAIL) ?></a> indicando el derecho que desea ejercer.
        Responderemos en los plazos legales.</p>
      <p>Si considera que no hemos atendido correctamente su solicitud, puede reclamar ante la autoridad de protección de
        datos competente; en España, la Agencia Española de Protección de Datos (<a href="https://www.aepd.es"
        target="_blank" rel="noopener">aepd.es</a>).</p>

      <h2>9. Seguridad</h2>
      <p>Aplicamos medidas técnicas y organizativas para proteger la información: cifrado del tráfico mediante HTTPS,
        contraseñas almacenadas con algoritmos de cifrado irreversible, control de accesos por roles, copias de seguridad y
        registro de eventos. Si ocurriera una brecha que afecte a sus datos, se lo notificaríamos conforme a la normativa.</p>

      <h2>10. Cookies y almacenamiento local</h2>
      <p>Este sitio usa una única cookie técnica de sesión, necesaria para mantener la sesión iniciada en el portal y para
        proteger los formularios. No requiere consentimiento porque no sirve para publicidad ni para analítica, y se elimina
        al cerrar la sesión. No usamos cookies de terceros con fines publicitarios.</p>

      <h2>11. Menores de edad</h2>
      <p>Las cuentas del portal están dirigidas a personal de instituciones educativas, mayores de edad. No recogemos datos
        de menores directamente a través de este sitio.</p>

      <h2>12. Cambios en esta política</h2>
      <p>Si actualizamos esta política, publicaremos la nueva versión en esta página con su fecha. Si el cambio afecta de
        forma relevante al tratamiento, se lo comunicaremos al correo registrado.</p>
    </article>
  </div>
</main>
<?php include __DIR__ . '/includes/site_footer.php'; ?>
