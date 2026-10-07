<?php
/**
 * Colegio8 — Preguntas frecuentes (página pública).
 *
 * Contenido informativo; las preguntas concretas del módulo de
 * inscripciones y biblioteca se gestionan por canales institucionales
 * (datos de contacto en configuracion).
 */
require __DIR__ . '/../app/core.php';

$preguntas = [
    'Inscripciones' => [
        ['¿Cómo me inscribo?',
         'Creá una cuenta de postulante, completá los datos del alumno y del
          tutor y adjuntá las fotos del DNI (alumno y tutor). La secretaría
          revisa la documentación y, si está bien, aprueba la solicitud.'],
        ['¿Qué pasa si mi solicitud es rechazada?',
         'La secretaría indica el motivo. Podés corregir los datos o reemplazar
          la documentación y volver a presentar la solicitud.'],
        ['¿En qué curso queda mi hijo si no hay cupo?',
         'El sistema lo suma a la lista de espera del curso elegido. Si a
          futuro hay un cupo, la secretaría puede asignarlo manualmente.'],
    ],
    'Biblioteca' => [
        ['¿Cómo entro a la biblioteca?',
         'Alumnos inscriptos, tutores y profesores ingresan con su usuario y
          contraseña. Si sos alumno, tu cuenta es la misma de la postulación.'],
        ['¿Cuánto dura un préstamo?',
         'Por defecto 15 días desde la fecha de préstamo. La fecha límite se
          indica al registrar el préstamo.'],
        ['¿Qué pasa si no devuelvo un libro a tiempo?',
         'El préstamo se marca como vencido y la biblioteca puede registrar
          una amonestación. Devolvé el libro para regularizar tu situación.'],
    ],
];

render('publico/frecuentes', [
    'titulo'    => 'Preguntas frecuentes',
    'rolPanel'  => 'publico',
    'preguntas' => $preguntas,
]);