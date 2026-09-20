<?php
/** Ilustración de cada módulo del sistema integrado. Usa $f del bucle de index.php. */
switch ($f['art']):
case 'books': ?>
  <div class="art-head"><b>Biblioteca · Grado 5°</b><span class="chip chip-teal">Alineado a DBA</span></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">📘</span><div class="grow">Matemáticas<small>Unidad 3 · Fracciones equivalentes</small></div><span class="chip chip-blue">12 guías</span></div>
  <div class="art-row"><span class="sq" style="background:var(--teal-3)">🌿</span><div class="grow">Ciencias Naturales<small>Unidad 2 · Ecosistemas de Colombia</small></div><span class="chip chip-teal">9 guías</span></div>
  <div class="art-row"><span class="sq" style="background:var(--sun-2)">✏️</span><div class="grow">Lenguaje<small>Unidad 4 · Textos argumentativos</small></div><span class="chip chip-sun">11 guías</span></div>
  <div class="art-row"><span class="sq" style="background:var(--rose)">🌎</span><div class="grow">Ciencias Sociales<small>Unidad 1 · Regiones naturales</small></div><span class="chip chip-rose">8 guías</span></div>
<?php break; case 'plan': ?>
  <div class="art-head"><b>Plan de clase · 55 min</b><span class="chip chip-blue">✦ Generado con IA</span></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">1</span><div class="grow">Exploración<small>Pregunta detonante y saberes previos · 10 min</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--teal-3)">2</span><div class="grow">Estructuración<small>Explicación guiada con ejemplos · 20 min</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--sun-2)">3</span><div class="grow">Práctica<small>Trabajo en parejas con material concreto · 15 min</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--rose)">4</span><div class="grow">Cierre<small>Ticket de salida y reflexión · 10 min</small></div></div>
<?php break; case 'class': ?>
  <div class="art-head"><b>Pregunta en vivo</b><span class="chip chip-rose">● En curso</span></div>
  <p style="font-weight:700;color:var(--ink);margin-bottom:16px">¿Cuál es el principal motor del ciclo del agua?</p>
  <?php foreach ([['El viento', 12], ['La energía del Sol', 68], ['La gravedad', 14], ['Las plantas', 6]] as [$opt, $pct]): ?>
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;font-size:.88rem;font-weight:600;margin-bottom:6px"><span><?= $opt ?></span><span><?= $pct ?>%</span></div>
      <div class="progress"><i style="width:<?= $pct ?>%<?= $pct > 50 ? ';background:var(--teal)' : '' ?>"></i></div>
    </div>
  <?php endforeach; ?>
  <small class="muted">28 de 31 estudiantes respondieron</small>
<?php break; case 'access': ?>
  <div class="art-head"><b>Adaptar material</b><span class="chip chip-teal">Inclusión</span></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">Aa</span><div class="grow">Lectura fácil<small>Frases cortas y vocabulario sencillo</small></div><span class="chip chip-teal">Activo</span></div>
  <div class="art-row"><span class="sq" style="background:var(--sun-2)">🖼️</span><div class="grow">Apoyos visuales<small>Pictogramas en cada instrucción</small></div><span class="chip chip-teal">Activo</span></div>
  <div class="art-row"><span class="sq" style="background:var(--teal-3)">🔊</span><div class="grow">Audio narrado<small>Lectura en voz alta del contenido</small></div><span class="chip chip-blue">Opcional</span></div>
  <div class="art-row"><span class="sq" style="background:var(--rose)">📋</span><div class="grow">Ajustes razonables<small>Sugerencias según el PIAR</small></div><span class="chip chip-blue">Opcional</span></div>
<?php break; case 'exam': ?>
  <div class="art-head"><b>Simulacro · Matemáticas 11°</b><span class="chip chip-sun">40 preguntas</span></div>
  <?php foreach ([['Razonamiento', 'chip-blue', 14], ['Comunicación', 'chip-teal', 12], ['Resolución de problemas', 'chip-sun', 14]] as [$c, $cls, $n]): ?>
    <div class="art-row"><div class="grow"><?= $c ?><small>Dificultad mixta · baja, media y alta</small></div><span class="chip <?= $cls ?>"><?= $n ?> ítems</span></div>
  <?php endforeach; ?>
  <div class="art-row"><div class="grow">Rúbrica para preguntas abiertas<small>4 niveles de desempeño</small></div><span class="chip chip-rose">Auto</span></div>
<?php break; case 'grade': ?>
  <div class="art-head"><b>Corrección · Taller 3</b><span class="chip chip-teal">32 / 32</span></div>
  <?php foreach ([['Valentina R.', 4.6, 92], ['Samuel O.', 3.8, 76], ['Mariana C.', 4.2, 84], ['Juan David P.', 2.9, 58]] as [$n, $g, $w]): ?>
    <div class="art-row"><span class="sq" style="background:var(--lav)"><?= mb_substr($n, 0, 1) ?></span><div class="grow"><?= $n ?><div class="progress" style="margin-top:6px"><i style="width:<?= $w ?>%<?= $g < 3 ? ';background:#E57373' : '' ?>"></i></div></div><b><?= number_format($g, 1) ?></b></div>
  <?php endforeach; ?>
<?php break; case 'recover': ?>
  <div class="art-head"><b>Plan de nivelación</b><span class="chip chip-rose">5 estudiantes</span></div>
  <div class="art-row"><span class="sq" style="background:var(--rose)">!</span><div class="grow">Proporcionalidad<small>Aprendizaje por reforzar · 5 estudiantes</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">1</span><div class="grow">Video corto + ejemplo resuelto<small>Asignado automáticamente</small></div><span class="chip chip-teal">✓</span></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">2</span><div class="grow">8 ejercicios graduados<small>Con pistas paso a paso</small></div><span class="chip chip-blue">En curso</span></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">3</span><div class="grow">Mini evaluación de verificación<small>Al completar la práctica</small></div><span class="chip chip-sun">Pendiente</span></div>
<?php break; case 'comms': ?>
  <div class="art-head"><b>Boletín para acudientes</b><span class="chip chip-blue">Periodo 2</span></div>
  <div class="art-row" style="align-items:flex-start"><span class="sq" style="background:var(--sun-2)">★</span><div class="grow">Tomás avanzó en comprensión lectora<small>Pasó de nivel básico a alto. Recomendamos leer 15 minutos diarios en casa.</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--teal-3)">✉</span><div class="grow">Circular: salida pedagógica<small>Enviada a 214 familias · 96% leída</small></div></div>
  <div class="art-row"><span class="sq" style="background:var(--lav)">📅</span><div class="grow">Entrega de informes<small>Viernes 7:00 a. m. · Confirmado</small></div></div>
<?php break; endswitch;
