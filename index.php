<?php
require_once __DIR__ . '/includes/bootstrap.php';

$features = [
    ['key' => 'libros', 'tab' => 'Libros de texto', 'title' => 'Libros y guías listos para cada grado',
     'text' => 'Contenidos organizados por área, grado y periodo, alineados con los estándares básicos de competencias y los DBA. El docente parte de un material sólido y lo ajusta a su grupo en minutos.',
     'points' => ['Secuencias didácticas por periodo académico', 'Lecturas, ejercicios y retos por nivel', 'Versión impresa, digital o mixta'],
     'art' => 'books'],
    ['key' => 'planeacion', 'tab' => 'Planeación', 'title' => 'Planeación de clases en minutos, no en fines de semana',
     'text' => 'Describe el tema y el grado; la IA propone objetivos, momentos de la clase, actividades y recursos. Todo queda editable y se guarda en el plan de área.',
     'points' => ['Planes semanales y por periodo', 'Actividades diferenciadas por ritmo de aprendizaje', 'Exporta a Word o PDF con el formato de tu institución'],
     'art' => 'plan'],
    ['key' => 'clases', 'tab' => 'Clases', 'title' => 'Clases dinámicas que capturan la atención',
     'text' => 'Presentaciones, preguntas interactivas y dinámicas de juego que se proyectan en el aula o se comparten con los estudiantes desde cualquier dispositivo.',
     'points' => ['Presentaciones generadas desde tu planeación', 'Preguntas en vivo y tableros de participación', 'Funciona con conexión limitada'],
     'art' => 'class'],
    ['key' => 'accesibilidad', 'tab' => 'Accesibilidad', 'title' => 'Materiales para que nadie se quede atrás',
     'text' => 'Adapta cualquier contenido para estudiantes con necesidades educativas diversas: lectura fácil, apoyos visuales, audio y ajustes razonables según el PIAR.',
     'points' => ['Versión de lectura fácil con un clic', 'Apoyos visuales y audio narrado', 'Sugerencias de ajustes razonables'],
     'art' => 'access'],
    ['key' => 'evaluaciones', 'tab' => 'Evaluaciones', 'title' => 'Evaluaciones tipo prueba de Estado',
     'text' => 'Crea quices, talleres y simulacros con preguntas de selección múltiple y abiertas, con niveles de dificultad y competencias asociadas.',
     'points' => ['Bancos de preguntas por competencia', 'Simulacros con estructura de prueba de Estado', 'Rúbricas automáticas para preguntas abiertas'],
     'art' => 'exam'],
    ['key' => 'calificacion', 'tab' => 'Calificación', 'title' => 'Corrección asistida con retroalimentación',
     'text' => 'Escanea o sube las respuestas y recibe una propuesta de calificación con comentarios para cada estudiante. El docente revisa y aprueba.',
     'points' => ['Corrección de respuestas abiertas y manuscritas', 'Retroalimentación personalizada', 'Notas listas para el sistema académico'],
     'art' => 'grade'],
    ['key' => 'recuperacion', 'tab' => 'Recuperación', 'title' => 'Planes de refuerzo para cada estudiante',
     'text' => 'Con base en los resultados, el sistema identifica qué aprendizajes faltan y genera actividades de nivelación a la medida de cada estudiante.',
     'points' => ['Diagnóstico automático por competencia', 'Actividades de nivelación personalizadas', 'Seguimiento del avance en el tiempo'],
     'art' => 'recover'],
    ['key' => 'comunicacion', 'tab' => 'Comunicación', 'title' => 'Familias informadas, sin papeleo',
     'text' => 'Boletines, circulares y reportes de avance que llegan a las familias con un lenguaje claro, en el canal que prefieran.',
     'points' => ['Boletines de desempeño comprensibles', 'Circulares y mensajes programados', 'Resumen semanal para acudientes'],
     'art' => 'comms'],
];

$faqs = [
    ['¿Qué incluye cada licencia?', 'Ambas licencias incluyen todas las herramientas de planeación, clases, evaluación, calificación, recuperación y comunicación durante 12 meses. La diferencia está en la cantidad de usuarios y sedes: la Licencia Escuela cubre hasta 60 usuarios en una sede y la Licencia por Volumen hasta 250 usuarios en un máximo de 5 sedes, además de beneficios como Studio y acompañamiento dedicado.'],
    ['¿Cómo se realiza el pago?', 'El pago se realiza en nuestra pasarela segura. Puedes pagar con tarjeta de crédito o débito, PSE (débito desde tu cuenta bancaria) o en efectivo en puntos Efecty. Al aprobarse el pago, tu licencia se activa automáticamente.'],
    ['¿Qué pasa después de pagar?', 'Te redirigimos al Portal de Clientes, donde verás tu licencia, su código, la vigencia y los cupos disponibles. Desde allí agregas a los docentes y directivos que usarán la plataforma.'],
    ['¿Puedo agregar o cambiar usuarios después?', 'Sí. Desde el portal puedes agregar, editar o retirar usuarios en cualquier momento, siempre que no superes el número de cupos de tu licencia. También puedes cargar varios usuarios a la vez.'],
    ['¿Desde dónde se realiza la venta?', BRAND_NAME . ' es un comercio electrónico internacional establecido en ' . SELLER_COUNTRY . '. Las licencias son productos digitales que se venden en línea a instituciones de distintos países.'],
    ['¿El precio tiene cargos adicionales?', 'No. El precio publicado es el valor final a pagar por la licencia anual. ' . BRAND_NAME . ' no incluye, no recauda ni asume impuestos o políticas tributarias de países de Latinoamérica; si la legislación de tu país exige algún impuesto, retención o declaración sobre la compra, su cumplimiento corresponde al comprador.'],
    ['¿Qué recibo como comprobante?', 'En el Portal de Clientes encuentras el detalle de cada compra: referencia del pedido, fecha, valor pagado y referencia de pago.'],
    ['¿Necesito instalar algo?', 'No. La plataforma funciona desde el navegador en computadores, tabletas y celulares. Solo necesitas conexión a internet.'],
    ['¿Qué pasa con los datos de mis estudiantes?', 'Los datos se tratan conforme a la Ley 1581 de 2012 de protección de datos personales. La institución es dueña de su información y puede solicitar su exportación o eliminación.'],
];

include __DIR__ . '/includes/site_header.php';
?>

<!-- ================= HERO ================= -->
<section class="hero">
  <div class="container hero-grid">
    <div class="reveal">
      <span class="eyebrow"><span class="dot"></span> Inteligencia artificial para la educación</span>
      <h1>Un solo sistema para <span class="hl">enseñar mejor</span> y <span class="hl-under">aprender más</span></h1>
      <p class="hero-lead">Planeación, clases, evaluaciones y seguimiento conectados en una plataforma con IA pensada para docentes, directivos, estudiantes y familias.</p>
      <div class="hero-ctas">
        <a class="btn btn-primary" href="#precios">Ver licencias
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <a class="btn btn-outline" href="#sistema">Conocer la plataforma</a>
      </div>
      <div class="audience" aria-label="Perfiles">
        <a href="#ecosistema"><span class="ico ico-blue">✎</span>Docentes</a>
        <a href="#ecosistema"><span class="ico ico-teal">▦</span>Directivos</a>
        <a href="#ecosistema"><span class="ico ico-sun">★</span>Estudiantes</a>
        <a href="#ecosistema"><span class="ico ico-rose">♥</span>Familias</a>
      </div>
    </div>

    <div class="hero-visual reveal">
      <svg class="orbit" viewBox="0 0 600 600" fill="none" aria-hidden="true">
        <ellipse cx="300" cy="300" rx="280" ry="110" stroke="#D2DEFF" stroke-width="2"/>
        <ellipse cx="300" cy="300" rx="280" ry="110" stroke="#C6E9E7" stroke-width="2" transform="rotate(60 300 300)"/>
        <ellipse cx="300" cy="300" rx="280" ry="110" stroke="#FFE080" stroke-width="2" transform="rotate(-60 300 300)"/>
        <circle cx="580" cy="300" r="9" fill="#5880F1"/>
        <circle cx="160" cy="58" r="7" fill="#77C7C1"/>
        <circle cx="160" cy="542" r="7" fill="#FFC744"/>
      </svg>
      <div class="mock">
        <div class="mock-bar"><i></i><i></i><i></i><span><?= e(strtolower(BRAND_NAME)) ?>.co/planeacion</span></div>
        <div class="mock-body">
          <div class="mock-side">
            <div><b></b>Inicio</div>
            <div class="on"><b></b>Planeación</div>
            <div><b></b>Clases</div>
            <div><b></b>Evaluaciones</div>
            <div><b></b>Calificación</div>
            <div><b></b>Reportes</div>
          </div>
          <div class="mock-main">
            <div class="mock-title">Ciencias Naturales · Grado 7°</div>
            <div class="mock-sub">Periodo 2 · Semana 5</div>
            <div class="mock-prompt"><span class="spark">✦</span>Crea una clase sobre el ciclo del agua con una práctica sencilla…</div>
            <div class="mock-cards">
              <div class="mock-card" style="background:var(--lav-3)">Actividades<strong>6</strong></div>
              <div class="mock-card" style="background:var(--teal-4)">Duración<strong>55 min</strong></div>
            </div>
            <div class="mock-lines"><span></span><span></span><span></span></div>
            <div class="bars"><i style="height:40%"></i><i style="height:65%"></i><i style="height:50%"></i><i style="height:85%"></i><i style="height:60%"></i><i style="height:95%"></i><i style="height:72%"></i></div>
          </div>
        </div>
      </div>
      <div class="float-card fc-1"><span class="badge-ico" style="background:var(--teal-3)">✓</span><div>Evaluación calificada<small>32 estudiantes · 4 min</small></div></div>
      <div class="float-card fc-2"><span class="badge-ico" style="background:var(--sun-2)">⚡</span><div>Planeación lista<small>Generada con IA</small></div></div>
    </div>
  </div>
</section>

<!-- ================= FRANJA ================= -->
<section class="trust">
  <div class="container trust-grid">
    <div class="trust-item reveal"><span class="num">8</span><p>módulos integrados en<br>una sola plataforma</p></div>
    <div class="trust-item reveal"><span class="num">4</span><p>perfiles conectados: docentes,<br>directivos, estudiantes y familias</p></div>
    <div class="trust-item reveal"><span class="num">-70%</span><p>de tiempo estimado en<br>planeación y corrección</p></div>
    <div class="trust-item reveal"><span class="num">24/7</span><p>disponible desde cualquier<br>dispositivo con navegador</p></div>
  </div>
</section>

<!-- ================= SISTEMA INTEGRADO ================= -->
<section class="section system" id="sistema">
  <div class="container center">
    <span class="eyebrow reveal"><span class="dot"></span> Sistema integrado</span>
    <h2 class="section-title reveal">Todo el ciclo de aprendizaje, <span class="hl">conectado</span></h2>
    <p class="section-lead reveal">Desde el libro de texto hasta el boletín para las familias: cada herramienta alimenta a la siguiente para que el trabajo del aula fluya sin duplicar esfuerzos.</p>

    <div class="tabs reveal" role="tablist">
      <?php foreach ($features as $i => $f): ?>
        <button class="tab<?= $i === 0 ? ' active' : '' ?>" role="tab" data-tab="<?= e($f['key']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
          <span class="n">0<?= $i + 1 ?></span><?= e($f['tab']) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="container">
    <?php foreach ($features as $i => $f): ?>
      <div class="panel panel-grid<?= $i === 0 ? ' active' : '' ?>" id="panel-<?= e($f['key']) ?>" role="tabpanel">
        <div>
          <span class="chip chip-blue">0<?= $i + 1 ?> · <?= e($f['tab']) ?></span>
          <h3 style="margin-top:14px"><?= e($f['title']) ?></h3>
          <p><?= e($f['text']) ?></p>
          <ul class="checks">
            <?php foreach ($f['points'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <div class="panel-art">
          <?php include __DIR__ . '/includes/feature_art.php'; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ================= ECOSISTEMA ================= -->
<section class="section eco" id="ecosistema">
  <div class="container">
    <div class="center">
      <span class="eyebrow reveal"><span class="dot"></span> Ecosistema</span>
      <h2 class="section-title reveal">Cada persona de la comunidad educativa tiene su espacio</h2>
      <p class="section-lead reveal">Una misma información, presentada como la necesita cada quien.</p>
    </div>
    <div class="eco-grid">
      <article class="eco-card c-blue reveal" id="docentes">
        <div class="role-ico" style="background:var(--lav)"><svg viewBox="0 0 24 24" fill="none" stroke="#3C60D1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></div>
        <h3>Docentes</h3>
        <p>Menos tiempo en tareas repetitivas y más tiempo para acompañar a los estudiantes.</p>
        <ul>
          <li><span>→</span>Planeaciones, guías y presentaciones con IA</li>
          <li><span>→</span>Evaluaciones y rúbricas en segundos</li>
          <li><span>→</span>Corrección asistida con retroalimentación</li>
        </ul>
      </article>
      <article class="eco-card c-teal reveal" id="directivos">
        <div class="role-ico" style="background:var(--teal-3)"><svg viewBox="0 0 24 24" fill="none" stroke="#008F87" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg></div>
        <h3>Directivos</h3>
        <p>Visibilidad real del aprendizaje para tomar decisiones con datos.</p>
        <ul>
          <li><span>→</span>Indicadores por grado, área y sede</li>
          <li><span>→</span>Alertas tempranas de bajo desempeño</li>
          <li><span>→</span>Reducción de costos en materiales impresos</li>
        </ul>
      </article>
      <article class="eco-card c-sun reveal" id="estudiantes">
        <div class="role-ico" style="background:var(--sun-2)"><svg viewBox="0 0 24 24" fill="none" stroke="#8a6100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg></div>
        <h3>Estudiantes</h3>
        <p>Un tutor disponible siempre, que se adapta a su ritmo.</p>
        <ul>
          <li><span>→</span>Tutor con IA que explica paso a paso</li>
          <li><span>→</span>Retos, medallas y progreso visible</li>
          <li><span>→</span>Práctica personalizada para reforzar</li>
        </ul>
      </article>
      <article class="eco-card c-rose reveal" id="familias">
        <div class="role-ico" style="background:var(--rose)"><svg viewBox="0 0 24 24" fill="none" stroke="#9b3434" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 000-7.8z"/></svg></div>
        <h3>Familias</h3>
        <p>Acompañar el proceso escolar sin complicaciones.</p>
        <ul>
          <li><span>→</span>Boletines claros y fáciles de entender</li>
          <li><span>→</span>Avisos y circulares en el celular</li>
          <li><span>→</span>Recomendaciones para apoyar en casa</li>
        </ul>
      </article>
    </div>
  </div>
</section>

<!-- ================= STUDIO ================= -->
<section class="section studio" id="studio">
  <div class="container studio-grid">
    <div class="reveal">
      <span class="eyebrow"><span class="dot" style="background:var(--sun)"></span> <?= e(BRAND_NAME) ?> Studio</span>
      <h2 class="section-title">Los libros de tu institución, diseñados a tu medida</h2>
      <p class="section-lead">Crea textos escolares propios con el modelo pedagógico, la identidad y el contexto de tu institución, por una fracción de lo que cuesta un texto comercial.</p>
      <div class="studio-points">
        <div class="studio-point"><b>Tu modelo pedagógico</b><span>Contenidos alineados con el PEI y el plan de estudios.</span></div>
        <div class="studio-point"><b>Tu identidad</b><span>Portadas, colores y logos de la institución.</span></div>
        <div class="studio-point"><b>Impreso o digital</b><span>Elige el formato o combina ambos.</span></div>
        <div class="studio-point"><b>Actualizable</b><span>Ajusta el contenido cada año sin reimprimir todo.</span></div>
      </div>
      <a class="btn btn-white" href="#precios">Incluido en Licencia por Volumen</a>
    </div>
    <div class="books reveal" aria-hidden="true">
      <div class="book book-1"><div><small>Matemáticas</small><h4>Pensamiento numérico 5°</h4></div><div class="shape"></div></div>
      <div class="book book-2"><div><small>Ciencias</small><h4>Explorando la vida 7°</h4></div><div class="shape"></div></div>
      <div class="book book-3"><div><small>Lenguaje</small><h4>Leer y crear 3°</h4></div><div class="shape"></div></div>
    </div>
  </div>
</section>

<!-- ================= CÓMO FUNCIONA ================= -->
<section class="section">
  <div class="container">
    <div class="center">
      <span class="eyebrow reveal"><span class="dot"></span> Cómo funciona</span>
      <h2 class="section-title reveal">Activa tu institución en tres pasos</h2>
    </div>
    <div class="steps">
      <div class="step reveal"><h3>Elige tu licencia</h3><p>Selecciona la Licencia Escuela o la Licencia por Volumen según el tamaño de tu institución.</p></div>
      <div class="step reveal"><h3>Paga en línea</h3><p>Tarjeta, PSE o efectivo. Tu licencia se activa automáticamente al aprobarse el pago.</p></div>
      <div class="step reveal"><h3>Agrega a tu equipo</h3><p>Desde el Portal de Clientes registras a los docentes y directivos que usarán la plataforma.</p></div>
    </div>
  </div>
</section>

<!-- ================= PRECIOS ================= -->
<section class="section pricing" id="precios">
  <div class="container">
    <div class="center">
      <span class="eyebrow reveal"><span class="dot"></span> Precios</span>
      <h2 class="section-title reveal">Licencias anuales para toda tu institución</h2>
      <p class="section-lead reveal">Un pago único por 12 meses, sin costos ocultos. Incluye todas las herramientas, actualizaciones y soporte.</p>
    </div>

    <div class="price-grid">
      <?php foreach (plans() as $code => $p): $featured = $code === 'volumen'; ?>
        <div class="price-card<?= $featured ? ' featured' : '' ?> reveal">
          <?php if ($featured): ?><span class="ribbon">Mejor valor por usuario</span><?php endif; ?>
          <div class="plan-ico" style="background:<?= $featured ? 'var(--lav)' : 'var(--teal-3)' ?>">
            <?php if ($featured): ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="#3C60D1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/><path d="M1 21V12l4-2M23 21V12l-4-2"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="#008F87" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12M10 21v-5h4v5"/></svg>
            <?php endif; ?>
          </div>
          <h3><?= e($p['name']) ?></h3>
          <p class="plan-desc"><?= e($p['description']) ?></p>
          <div class="price"><span class="amount">$<?= number_format($p['price'], 0, ',', '.') ?></span><span class="cur">COP / año</span></div>
          <div class="price-note">Equivale a <?= money(round($p['price'] / $p['seats'])) ?> por usuario al año</div>
          <ul class="checks">
            <?php foreach ($p['features'] as $feat): ?><li><?= e($feat) ?></li><?php endforeach; ?>
          </ul>
          <a class="btn <?= $featured ? 'btn-blue' : 'btn-primary' ?> btn-block" href="<?= url('checkout.php?plan=' . $code) ?>">Comprar <?= e($p['name']) ?></a>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="secure-note reveal">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
      Pago 100% seguro y cifrado · <span class="mp-badge">Tarjetas</span> <span class="mp-badge">PSE</span> <span class="mp-badge">Efecty</span>
    </div>
    <div class="reveal"><?php include __DIR__ . '/includes/intl_notice.php'; ?></div>

    <?php $pe = plan('escuela'); $pv = plan('volumen'); ?>
    <div class="compare reveal">
      <table>
        <thead><tr><th>Comparativo</th><th>Escuela</th><th>Volumen</th></tr></thead>
        <tbody>
          <tr><td>Usuarios incluidos</td><td><?= $pe['seats'] ?></td><td><?= $pv['seats'] ?></td></tr>
          <tr><td>Sedes / instituciones</td><td><?= $pe['campuses'] ?></td><td>Hasta <?= $pv['campuses'] ?></td></tr>
          <tr><td>Vigencia</td><td><?= $pe['months'] ?> meses</td><td><?= $pv['months'] ?> meses</td></tr>
          <tr><td>Planeación, clases y evaluaciones con IA</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>Corrección asistida y reportes</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>Accesibilidad e inclusión</td><td class="yes">✓</td><td class="yes">✓</td></tr>
          <tr><td>Panel de indicadores multisede</td><td class="no">—</td><td class="yes">✓</td></tr>
          <tr><td>Studio: libros personalizados</td><td class="no">—</td><td class="yes">✓</td></tr>
          <tr><td>Gerente de cuenta dedicado</td><td class="no">—</td><td class="yes">✓</td></tr>
          <tr><td>Portal de clientes y gestión de usuarios</td><td class="yes">✓</td><td class="yes">✓</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ================= FAQ ================= -->
<section class="section" id="faq">
  <div class="container">
    <div class="center">
      <span class="eyebrow reveal"><span class="dot"></span> Preguntas frecuentes</span>
      <h2 class="section-title reveal">Resolvemos tus dudas</h2>
    </div>
    <div class="faq-list">
      <?php foreach ($faqs as $i => [$q, $a]): ?>
        <div class="faq-item reveal">
          <button class="faq-q" aria-expanded="false" aria-controls="faq-<?= $i ?>"><?= e($q) ?><span class="pm">+</span></button>
          <div class="faq-a" id="faq-<?= $i ?>"><div><?= e($a) ?></div></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Perfiles -->
    <div class="center" style="margin-top:110px">
      <h2 class="section-title reveal">¿Quién eres en la comunidad educativa?</h2>
    </div>
    <div class="profiles">
      <a class="profile p-blue reveal" href="#docentes"><b>Soy docente</b><span>Planea, enseña y evalúa con ayuda de la IA.</span><em>Ver más →</em></a>
      <a class="profile p-teal reveal" href="#directivos"><b>Soy directivo</b><span>Gestiona tu institución con datos claros.</span><em>Ver más →</em></a>
      <a class="profile p-sun reveal" href="#estudiantes"><b>Soy estudiante</b><span>Aprende a tu ritmo con un tutor siempre disponible.</span><em>Ver más →</em></a>
      <a class="profile p-rose reveal" href="#familias"><b>Soy familia</b><span>Acompaña el aprendizaje desde casa.</span><em>Ver más →</em></a>
    </div>

    <div class="cta-band reveal">
      <div>
        <h2>Lleva la inteligencia artificial a tu institución este año</h2>
        <p>Activa tu licencia hoy y empieza a planear tus clases en minutos.</p>
      </div>
      <div class="btns">
        <a class="btn btn-white" href="#precios">Ver precios</a>
        <a class="btn btn-blue" href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener">Hablar con ventas</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/site_footer.php'; ?>
