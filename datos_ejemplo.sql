-- =====================================================
-- Datos de ejemplo (OPCIONAL), para probar o presentar.
-- Primero corre database.sql y después este:
--   mysql -u root -p < datos_ejemplo.sql
--
-- Todos estos usuarios tienen la contraseña: 123456
--   director@cursonet.com   (Director)
--   docente@cursonet.com    (Docente)
--   alumno@cursonet.com     (Alumno, con avance en el curso)
--   alumna@cursonet.com     (Alumno)
-- =====================================================

USE cursos;

SET @pass = '$2y$12$.8p7b4VPzhaKQXQAxVDLqeEH/HfN7TCgu7o6WILrzO31YpWsKZhJW';

INSERT IGNORE INTO usuarios (nombre, email, password, rol) VALUES
    ('Daniela Ruiz',   'director@cursonet.com', @pass, 'director'),
    ('Carlos Méndez',  'docente@cursonet.com',  @pass, 'docente'),
    ('Luis Torres',    'alumno@cursonet.com',   @pass, 'alumno'),
    ('Ana López',      'alumna@cursonet.com',   @pass, 'alumno');

-- Guardamos los ids en "variables" (@nombre) para usarlos abajo
SET @docente = (SELECT id FROM usuarios WHERE email = 'docente@cursonet.com');
SET @luis    = (SELECT id FROM usuarios WHERE email = 'alumno@cursonet.com');
SET @ana     = (SELECT id FROM usuarios WHERE email = 'alumna@cursonet.com');

-- ---------- Curso 1 (publicado, con contenido) ----------
INSERT INTO cursos (docente_id, titulo, descripcion, estado) VALUES
    (@docente, 'Desarrollo web desde cero',
     'Aprende a crear tus primeras páginas web con HTML y CSS, paso a paso y sin conocimientos previos.',
     'publicado');
SET @curso = LAST_INSERT_ID();   -- el id del curso que acabamos de crear

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@curso, 'Introducción a la web', 1);
SET @b1 = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b1, '¿Qué es una página web?', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una página web es un documento que se ve en el navegador.\n\nEstá hecha principalmente con tres lenguajes:\n- HTML: el contenido (textos, imágenes, botones).\n- CSS: el diseño (colores, tamaños, posiciones).\n- JavaScript: el comportamiento (qué pasa al hacer clic).', 1),
    (@leccion, 'enlace', 'Guía para empezar con la web (MDN)', 'https://developer.mozilla.org/es/docs/Learn/Getting_started_with_the_web', 2);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b1, '¿Cómo funciona Internet?', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Cuando escribes una dirección, tu navegador le pide la página a un servidor.\n\nEl servidor responde con el HTML y el navegador lo dibuja en tu pantalla.', 1);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@curso, 'HTML básico', 2);
SET @b2 = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b2, 'Etiquetas y elementos', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'HTML usa etiquetas para marcar cada parte del contenido.\n\nPor ejemplo, <h1> es un título y <p> es un párrafo.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b2, 'Tu primera página', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Abre un editor de texto, escribe un título y un párrafo con las etiquetas que aprendiste, guárdalo como index.html y ábrelo en el navegador.', 1);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@curso, 'Estilos con CSS', 3);
SET @b3 = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b3, 'Colores y tipografía', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Con CSS puedes cambiar el color del texto (color), el fondo (background) y el tipo de letra (font-family).', 1);

-- Inscripciones y avance de ejemplo
INSERT INTO inscripciones (alumno_id, curso_id) VALUES (@luis, @curso), (@ana, @curso);
INSERT INTO temas_completados (alumno_id, tema_id)
    SELECT @luis, t.id FROM temas t WHERE t.bloque_id = @b1;   -- Luis terminó el bloque 1

INSERT INTO comentarios (curso_id, usuario_id, mensaje) VALUES
    (@curso, @luis,    '¿Para el tema 2 necesito instalar algún programa?'),
    (@curso, @docente, 'No, Luis: con el bloc de notas y tu navegador es suficiente.');

-- ---------- Curso 2 (borrador, todavía sin contenido) ----------
INSERT INTO cursos (docente_id, titulo, descripcion, estado) VALUES
    (@docente, 'Marketing digital', 'Conceptos básicos para promocionar un negocio en internet.', 'borrador');

-- =====================================================
-- Datos de las funciones nuevas (versión 2)
-- =====================================================

-- Categorías de los cursos
UPDATE cursos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Programación') WHERE id = @curso;
UPDATE cursos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Negocios')     WHERE id = LAST_INSERT_ID();

-- Cuestionario de la primera lección
SET @t1 = (SELECT id FROM temas WHERE bloque_id = @b1 ORDER BY orden LIMIT 1);
INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t1, '¿Qué lenguaje se encarga del diseño (colores y tamaños)?', 'HTML', 'CSS', 'JavaScript', 2),
    (@t1, '¿Dónde se ve una página web?', 'En el navegador', 'En la calculadora', 'En el bloc de notas', 1);

-- Una tarea: Luis ya la entregó y está calificada; Ana todavía no
INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@curso, 'Tu primera página',
     'Crea un archivo index.html con un título (h1) y dos párrafos sobre ti. Sube el archivo o pega el código en la respuesta.',
     DATE_ADD(CURDATE(), INTERVAL 7 DAY));
SET @tarea = LAST_INSERT_ID();
INSERT INTO entregas (tarea_id, alumno_id, texto, calificacion, comentario) VALUES
    (@tarea, @luis, '<h1>Hola, soy Luis</h1>\n<p>Me gusta programar.</p>\n<p>Estoy aprendiendo HTML.</p>', 90,
     '¡Muy bien! Solo te faltó cerrar una etiqueta.');

-- Un aviso del docente
INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@curso, '¡Bienvenidos al curso!', 'Revisen el módulo 1 esta semana y no olviden entregar la tarea "Tu primera página".');

-- Una reseña de Ana
INSERT INTO resenas (alumno_id, curso_id, estrellas, comentario) VALUES
    (@ana, @curso, 5, 'Muy claro y fácil de seguir. ¡Lo recomiendo!');

-- Notificaciones de ejemplo
INSERT INTO notificaciones (usuario_id, mensaje, enlace) VALUES
    (@luis, 'Tu tarea Tu primera página fue calificada: 90.', CONCAT('tarea.php?id=', @tarea)),
    (@ana,  'Nuevo aviso en Desarrollo web desde cero: ¡Bienvenidos al curso!', CONCAT('avisos.php?curso=', @curso));
