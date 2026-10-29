-- =====================================================
-- Cursos con contenido real (OPCIONAL)
-- 5 cursos publicados, uno por categoría, con módulos, lecciones (hechas de secciones),
-- cuestionarios, una tarea y un aviso cada uno.
--
-- Se corre UNA sola vez, después de database.sql:
--   mysql -u root -p < cursos_contenido.sql
--
-- Agrega 2 docentes nuevos (contraseña: 123456):
--   mariana@cursonet.com   (Diseño e Inglés)
--   jorge@cursonet.com     (Finanzas y Astronomía)
-- El curso de Python es de docente@cursonet.com (si existe; si no, de Mariana).
-- =====================================================

USE cursos;

SET @pass = '$2y$12$.8p7b4VPzhaKQXQAxVDLqeEH/HfN7TCgu7o6WILrzO31YpWsKZhJW';   -- "123456"

INSERT IGNORE INTO usuarios (nombre, email, password, rol) VALUES
    ('Mariana Ortega', 'mariana@cursonet.com', @pass, 'docente'),
    ('Jorge Salinas',  'jorge@cursonet.com',   @pass, 'docente');

SET @mariana = (SELECT id FROM usuarios WHERE email = 'mariana@cursonet.com');
SET @jorge   = (SELECT id FROM usuarios WHERE email = 'jorge@cursonet.com');
SET @carlos  = COALESCE((SELECT id FROM usuarios WHERE email = 'docente@cursonet.com'), @mariana);


-- =====================================================
-- CURSO 1: Python para principiantes (Programación)
-- =====================================================
INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion, estado) VALUES
    (@carlos, (SELECT id FROM categorias WHERE nombre = 'Programación'),
     'Python para principiantes',
     'Aprende a programar desde cero con Python, uno de los lenguajes más usados del mundo. Escribirás tus primeros programas, tomarás decisiones con if, repetirás tareas con ciclos y crearás tus propias funciones.',
     'publicado');
SET @c1 = LAST_INSERT_ID();

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c1, 'Primeros pasos', 1);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, '¿Qué es Python?', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Python es un lenguaje de programación creado por Guido van Rossum y publicado por primera vez en 1991. Su idea principal es que el código se lea casi como si fuera inglés, por eso es ideal para empezar.', 1),
    (@leccion, 'texto', '¿Dónde se usa?',
     '- Páginas web (Instagram y Pinterest usan Python en sus servidores).\n- Ciencia de datos e inteligencia artificial.\n- Automatizar tareas repetitivas, como renombrar cientos de archivos.', 2),
    (@leccion, 'texto', 'Cómo instalarlo',
     'Descarga Python desde python.org e instálalo. Después abre el programa IDLE, que viene incluido, y ya puedes escribir código.', 3),
    (@leccion, 'nota', 'Importante en Windows',
     'Durante la instalación marca la casilla "Add Python to PATH". Si no la marcas, la computadora no encontrará Python desde la terminal.', 4),
    (@leccion, 'enlace', 'Descargar Python',
     'https://www.python.org/downloads/', 5);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Tu primer programa: print e input', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'La instrucción print muestra un mensaje en la pantalla:', 1),
    (@leccion, 'codigo', 'Python',
     'print("¡Hola, mundo!")', 2),
    (@leccion, 'texto', NULL,
     'La instrucción input le hace una pregunta al usuario y guarda lo que escribe en una variable:', 3),
    (@leccion, 'codigo', 'Python',
     'nombre = input("¿Cómo te llamas? ")\nprint("Mucho gusto,", nombre)', 4),
    (@leccion, 'texto', NULL,
     'Si escribes Ana, el programa responde: Mucho gusto, Ana', 5),
    (@leccion, 'nota', 'Error común',
     'Los textos siempre van entre comillas. Si olvidas cerrar una comilla, Python mostrará un error de sintaxis (SyntaxError).', 6);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Variables y tipos de datos', 3);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una variable es una cajita con nombre donde guardas un dato:', 1),
    (@leccion, 'codigo', 'Python',
     'edad = 16\nprecio = 25.50\nnombre = "Luis"\nes_alumno = True', 2),
    (@leccion, 'texto', 'Tipos de datos más comunes',
     '- int: números enteros (16, -3, 2026).\n- float: números con decimales (25.50, 3.1416).\n- str: textos, siempre entre comillas ("Hola").\n- bool: verdadero o falso (True o False).\n\nPuedes preguntar el tipo de una variable con type(edad).', 3),
    (@leccion, 'nota', 'Cuidado con input',
     'input siempre regresa un texto (str), aunque el usuario escriba un número. Para hacer cuentas conviértelo con int().', 4),
    (@leccion, 'codigo', 'Python',
     'edad = int(input("¿Cuántos años tienes? "))\nprint("El próximo año tendrás", edad + 1)', 5),
    (@leccion, 'enlace', 'Tutorial oficial: introducción a Python',
     'https://docs.python.org/es/3/tutorial/introduction.html', 6);
SET @t_variables = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 3);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c1, 'Decisiones y repeticiones', 2);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Tomar decisiones con if', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Con if el programa elige qué hacer según una condición:', 1),
    (@leccion, 'codigo', 'Python',
     'calificacion = int(input("Tu calificación: "))\n\nif calificacion >= 90:\n    print("¡Excelente!")\nelif calificacion >= 60:\n    print("Aprobaste")\nelse:\n    print("Necesitas estudiar más")', 2),
    (@leccion, 'texto', 'Cómo se lee',
     '- if: "si pasa esto...".\n- elif: "si no, pero si pasa esto otro...".\n- else: "en cualquier otro caso".\n\nComparadores: == (igual), != (diferente), >, <, >=, <=.', 3),
    (@leccion, 'nota', 'La sangría importa',
     'Después de la condición van dos puntos (:) y las instrucciones de adentro llevan 4 espacios al inicio. Así Python sabe qué le pertenece al if. Si la sangría está mal, verás un IndentationError.', 4);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Repetir con for y while', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', 'El ciclo for',
     'Repite algo un número de veces:', 1),
    (@leccion, 'codigo', 'Python',
     'for numero in range(1, 6):\n    print(numero)', 2),
    (@leccion, 'texto', NULL,
     'Esto imprime 1, 2, 3, 4 y 5: range(1, 6) llega hasta el 5, el último número no se incluye. También puedes recorrer una lista:', 3),
    (@leccion, 'codigo', 'Python',
     'frutas = ["manzana", "pera", "uva"]\nfor fruta in frutas:\n    print("Me gusta la", fruta)', 4),
    (@leccion, 'texto', 'El ciclo while',
     'Repite mientras una condición sea verdadera:', 5),
    (@leccion, 'codigo', 'Python',
     'clave = ""\nwhile clave != "1234":\n    clave = input("Escribe la clave: ")\nprint("¡Acceso permitido!")', 6),
    (@leccion, 'nota', 'Cuidado con los ciclos infinitos',
     'Si la condición del while nunca se vuelve falsa, el programa no se detiene. Para cortarlo presiona Ctrl + C.', 7);
SET @t_ciclos = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 2);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c1, 'Funciones', 3);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Crear tus propias funciones', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una función es un bloque de código con nombre que puedes usar muchas veces. Se crea con def:', 1),
    (@leccion, 'codigo', 'Python',
     'def saludar(nombre):\n    print("Hola,", nombre)\n\nsaludar("Ana")\nsaludar("Luis")', 2),
    (@leccion, 'texto', 'Funciones que regresan un resultado',
     'Con return la función entrega un valor que puedes guardar:', 3),
    (@leccion, 'codigo', 'Python',
     'def area_rectangulo(base, altura):\n    return base * altura\n\nresultado = area_rectangulo(5, 3)\nprint(resultado)   # muestra 15', 4),
    (@leccion, 'texto', '¿Por qué usar funciones?',
     '- No repites el mismo código.\n- Si hay un error, lo corriges en un solo lugar.\n- El programa queda dividido en partes fáciles de entender.', 5),
    (@leccion, 'nota', 'Comentarios',
     'Todo lo que va después de # es un comentario: Python lo ignora y sirve para explicar el código a otras personas (o a ti mismo en el futuro).', 6),
    (@leccion, 'enlace', 'Tutorial oficial: control de flujo y funciones',
     'https://docs.python.org/es/3/tutorial/controlflow.html', 7);

INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t_variables, '¿Qué tipo de dato es "Hola"?', 'int', 'str', 'bool', 2),
    (@t_variables, '¿Qué tipo de dato regresa siempre input()?', 'Un texto (str)', 'Un número entero (int)', 'Un número decimal (float)', 1),
    (@t_variables, '¿Cuál de estos valores es un bool?', '"True"', '1.0', 'True', 3),
    (@t_ciclos, '¿Qué números imprime for n in range(1, 4): print(n)?', '1, 2, 3 y 4', '1, 2 y 3', '0, 1, 2 y 3', 2),
    (@t_ciclos, '¿Qué ciclo conviene cuando no sabes cuántas veces se repetirá algo?', 'for', 'while', 'if', 2);

INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@c1, 'Calculadora de promedio',
     'Escribe un programa que pida 3 calificaciones con input, calcule el promedio y diga si el alumno aprobó (promedio de 60 o más) o no.\n\nEntrega el archivo .py (puedes comprimirlo en .zip) o pega tu código en la respuesta.',
     DATE_ADD(CURDATE(), INTERVAL 10 DAY));

INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@c1, '¡Bienvenidos a Python!', 'Antes de empezar instalen Python desde python.org (lección 1). Si algo no les funciona, pregunten en el foro: ahí lo resolvemos entre todos.');


-- =====================================================
-- CURSO 2: Fundamentos de diseño gráfico (Diseño)
-- =====================================================
INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion, estado) VALUES
    (@mariana, (SELECT id FROM categorias WHERE nombre = 'Diseño'),
     'Fundamentos de diseño gráfico',
     'Las bases para que tus carteles, presentaciones y redes sociales se vean profesionales: cómo combinar colores, elegir tipografías y acomodar los elementos en el espacio.',
     'publicado');
SET @c2 = LAST_INSERT_ID();

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c2, 'El color', 1);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Teoría del color', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Existen dos formas de mezclar colores:\n\n- Modelo RGB (rojo, verde, azul): es la mezcla de luz que usan las pantallas. Si juntas los tres al máximo obtienes blanco. Se llama mezcla aditiva.\n- Modelo CMYK (cian, magenta, amarillo y negro): es la mezcla de tintas de las impresoras. Mientras más tinta, más oscuro. Se llama mezcla sustractiva.\n\nRegla práctica: si tu diseño se verá en pantalla, trabaja en RGB; si se va a imprimir, en CMYK.\n\nEl círculo cromático acomoda los colores en una rueda. Los colores que quedan opuestos (como azul y naranja) se llaman complementarios: juntos producen mucho contraste y llaman la atención.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Paletas de color y contraste', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una paleta es el pequeño grupo de colores que usarás en todo tu diseño. Algunas combinaciones que funcionan:\n\n- Monocromática: tonos claros y oscuros de un mismo color. Elegante y tranquila.\n- Análoga: colores vecinos en el círculo (amarillo, naranja, rojo). Armoniosa.\n- Complementaria: dos colores opuestos. Llamativa; úsala con medida.\n\nLa regla 60-30-10 ayuda a repartirlos: 60 % color principal (fondo), 30 % color secundario y 10 % color de acento para botones o detalles importantes.\n\nContraste: el texto debe leerse sin esfuerzo. Las pautas de accesibilidad web (WCAG) piden una relación de contraste mínima de 4.5 a 1 para texto normal. Puedes revisarlo con un verificador de contraste.', 1),
    (@leccion, 'enlace', 'Verificador de contraste (WebAIM)', 'https://webaim.org/resources/contrastchecker/', 2);
SET @t_paletas = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 2);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c2, 'Tipografía', 2);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Familias tipográficas', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Las letras se agrupan en familias según su forma:\n\n- Serif (con remates): tienen pequeños "piecitos" en las puntas, como Times New Roman o Georgia. Transmiten tradición y seriedad.\n- Sans serif (sin remates): líneas limpias, como Arial, Helvetica o Inter. Se ven modernas y se leen bien en pantalla.\n- Monoespaciadas: todas las letras miden lo mismo de ancho, como Courier. Se usan para código.\n- Script o manuscritas: imitan la escritura a mano. Solo para títulos cortos, nunca para párrafos.\n\nRegla de oro: usa máximo dos familias por diseño, una para títulos y otra para el texto. Puedes descargar tipografías gratuitas en Google Fonts.', 1),
    (@leccion, 'enlace', 'Google Fonts: tipografías gratuitas', 'https://fonts.google.com/', 2);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Jerarquía visual', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'La jerarquía visual le dice al ojo qué leer primero. Se logra con:\n\n- Tamaño: lo más importante, más grande.\n- Peso: negritas para destacar.\n- Color: un color de acento atrae la mirada.\n- Posición: leemos de arriba a abajo y de izquierda a derecha.\n\nEjemplo de un cartel:\n1. Título grande y en negritas: el nombre del evento.\n2. Subtítulo mediano: fecha y lugar.\n3. Texto pequeño: detalles e información de contacto.\n\nPrueba: aléjate de tu diseño o entrecierra los ojos. Lo primero que distingas debe ser lo más importante.', 1);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c2, 'Composición', 3);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Alineación y retícula', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una retícula es una cuadrícula invisible que sirve de guía para acomodar todo. Gracias a ella los elementos quedan alineados y el diseño se ve ordenado.\n\nConsejos:\n- Elige una alineación (izquierda, centro o derecha) y respétala. Mezclar varias hace que el diseño se vea descuidado.\n- Alinea los bordes de imágenes y textos entre sí.\n- La regla de los tercios: divide el espacio en 3 x 3 y coloca lo importante en las líneas o sus cruces. Es la misma regla que usa la cámara de tu celular.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El espacio en blanco', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'El espacio en blanco (o espacio negativo) es el área vacía alrededor de los elementos. No tiene que ser blanco: es cualquier espacio sin contenido.\n\n¿Por qué es importante?\n- Deja respirar al diseño y lo hace más fácil de leer.\n- Agrupa: los elementos cercanos se perciben como relacionados (ley de proximidad).\n- Transmite elegancia: las marcas de lujo usan mucho espacio vacío.\n\nError común: llenar cada rincón. Si dudas, quita un elemento en lugar de agregar otro.', 1);

INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t_paletas, 'En la regla 60-30-10, ¿qué porcentaje corresponde al color de acento?', '60 %', '30 %', '10 %', 3),
    (@t_paletas, '¿Qué tipo de paleta usa colores vecinos en el círculo cromático?', 'Análoga', 'Complementaria', 'Monocromática', 1),
    (@t_paletas, '¿Qué modelo de color usan las pantallas?', 'CMYK', 'RGB', 'Pantone', 2);

INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@c2, 'Diseña un cartel',
     'Diseña un cartel para un evento de tu escuela (puedes usar Canva, Figma o cualquier programa). Aplica una paleta de máximo 3 colores, dos tipografías y una jerarquía clara.\n\nSube la imagen en JPG o PNG y explica en la respuesta qué paleta elegiste y por qué.',
     DATE_ADD(CURDATE(), INTERVAL 14 DAY));

INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@c2, 'Herramientas gratuitas', 'Para las prácticas pueden usar Canva o Figma; los dos tienen plan gratuito y funcionan desde el navegador.');


-- =====================================================
-- CURSO 3: Inglés básico para viajar (Idiomas)
-- =====================================================
INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion, estado) VALUES
    (@mariana, (SELECT id FROM categorias WHERE nombre = 'Idiomas'),
     'Inglés básico para viajar',
     'Las frases que necesitas para tu primer viaje: presentarte, moverte por el aeropuerto, llegar al hotel, pedir comida y preguntar direcciones.',
     'publicado');
SET @c3 = LAST_INSERT_ID();

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c3, 'Saludos y presentaciones', 1);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Saludar y despedirse', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Saludos:\n- Hello / Hi = Hola (Hi es más informal).\n- Good morning = Buenos días (hasta el mediodía).\n- Good afternoon = Buenas tardes.\n- Good evening = Buenas noches (al llegar a un lugar).\n\nDespedidas:\n- Goodbye / Bye = Adiós.\n- See you later = Nos vemos luego.\n- Good night = Buenas noches (solo al despedirse o ir a dormir).\n\nPresentarte:\n- My name is Ana. = Me llamo Ana.\n- I am from Mexico. = Soy de México.\n- Nice to meet you. = Mucho gusto.\n\nPara preguntar: What is your name? (¿Cómo te llamas?) y Where are you from? (¿De dónde eres?).', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El verbo to be', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'To be significa "ser" o "estar". Es el verbo más importante del inglés:\n\n- I am = yo soy / estoy\n- You are = tú eres / estás\n- He is = él es / está\n- She is = ella es / está\n- It is = eso es / está\n- We are = nosotros somos / estamos\n- They are = ellos son / están\n\nEjemplos:\n- I am a student. = Soy estudiante.\n- She is tired. = Ella está cansada.\n- We are in London. = Estamos en Londres.\n\nEn la conversación se usan contracciones: I am = I''m, you are = you''re, he is = he''s.\n\nPara negar se agrega not: I am not hungry (no tengo hambre).', 1);
SET @t_tobe = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 2);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c3, 'Aeropuerto y hotel', 2);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'En el aeropuerto', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Vocabulario:\n- Passport = pasaporte\n- Boarding pass = pase de abordar\n- Gate = puerta de abordaje\n- Flight = vuelo\n- Luggage / baggage = equipaje\n- Delayed = retrasado\n\nFrases útiles:\n- Where is gate 12? = ¿Dónde está la puerta 12?\n- Is the flight on time? = ¿El vuelo va a tiempo?\n- I have one suitcase. = Tengo una maleta.\n\nEn migración te pueden preguntar:\n- What is the purpose of your visit? (¿Cuál es el motivo de tu visita?) Respuesta: Tourism / Vacation.\n- How long will you stay? (¿Cuánto tiempo te quedarás?) Respuesta: One week.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Llegar al hotel', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Al llegar a la recepción (front desk):\n\n- Hi, I have a reservation under the name García. = Hola, tengo una reservación a nombre de García.\n- What time is check-out? = ¿A qué hora es la salida?\n- Is breakfast included? = ¿El desayuno está incluido?\n- What is the Wi-Fi password? = ¿Cuál es la contraseña del wifi?\n\nSi hay un problema:\n- The air conditioning does not work. = El aire acondicionado no funciona.\n- Could I have more towels, please? = ¿Me podría dar más toallas, por favor?\n\nTip: please (por favor) y thank you (gracias) abren muchas puertas.', 1);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c3, 'Comida y direcciones', 3);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Pedir en un restaurante', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Frases para pedir:\n- A table for two, please. = Una mesa para dos, por favor.\n- Can I see the menu? = ¿Puedo ver el menú?\n- I would like a hamburger. = Quisiera una hamburguesa.\n- Can I have some water? = ¿Me da agua?\n- The check, please. = La cuenta, por favor.\n\nEl mesero puede preguntar:\n- Are you ready to order? = ¿Listos para ordenar?\n- For here or to go? = ¿Para comer aquí o para llevar?\n\nEn Estados Unidos se acostumbra dejar entre 15 % y 20 % de propina (tip).', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Preguntar direcciones', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Para preguntar:\n- Excuse me, where is the train station? = Disculpe, ¿dónde está la estación de tren?\n- How do I get to the museum? = ¿Cómo llego al museo?\n- Is it far? = ¿Está lejos?\n\nRespuestas que puedes escuchar:\n- Go straight. = Siga derecho.\n- Turn left / turn right. = Gire a la izquierda / a la derecha.\n- It is next to the bank. = Está junto al banco.\n- It is across from the park. = Está enfrente del parque.\n- It is two blocks away. = Está a dos cuadras.\n\nSi no entiendes: Could you repeat that more slowly, please? (¿Podría repetirlo más despacio, por favor?).', 1);

INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t_tobe, 'Completa: She ___ my sister.', 'am', 'is', 'are', 2),
    (@t_tobe, 'Completa: We ___ in Canada.', 'are', 'is', 'am', 1),
    (@t_tobe, '¿Cuál es la contracción correcta de "I am"?', 'Im', 'I''s', 'I''m', 3);

INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@c3, 'Preséntate en inglés',
     'Escribe una presentación de 5 a 8 oraciones en inglés: tu nombre, de dónde eres, cuántos años tienes (I am 16 years old) y a qué país te gustaría viajar.\n\nUsa al menos tres veces el verbo to be.',
     DATE_ADD(CURDATE(), INTERVAL 7 DAY));

INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@c3, 'Practiquen en voz alta', 'Lean cada frase en voz alta al menos tres veces. Escuchar y repetir es la forma más rápida de perder el miedo a hablar.');


-- =====================================================
-- CURSO 4: Finanzas personales (Negocios)
-- =====================================================
INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion, estado) VALUES
    (@jorge, (SELECT id FROM categorias WHERE nombre = 'Negocios'),
     'Finanzas personales',
     'Aprende a organizar tu dinero: hacer un presupuesto, ahorrar para emergencias, entender el interés compuesto y usar el crédito sin endeudarte.',
     'publicado');
SET @c4 = LAST_INSERT_ID();

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c4, 'Organiza tu dinero', 1);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Ingresos y gastos', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Los ingresos son el dinero que te entra: sueldo, domingo, ventas o becas. Los gastos son el dinero que sale.\n\nHay dos tipos de gastos:\n- Fijos: cuestan lo mismo cada mes (renta, internet, transporte).\n- Variables: cambian (comida fuera, ropa, salidas).\n\nEl primer paso para mejorar tus finanzas es saber en qué se va tu dinero. Durante un mes anota todos tus gastos, aunque sean de 10 pesos: en una libreta, una hoja de cálculo o una app.\n\nAl final del mes súmalos por categoría. Casi siempre aparecen "gastos hormiga": compras pequeñas que juntas suman mucho, como refrescos, botanas o suscripciones que no usas.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El presupuesto 50/30/20', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Un presupuesto es un plan de cómo usarás tu dinero antes de gastarlo. Una regla sencilla es la 50/30/20:\n\n- 50 % para necesidades: comida, transporte, servicios, renta.\n- 30 % para gustos: salidas, ropa de moda, entretenimiento.\n- 20 % para ahorro o para pagar deudas.\n\nEjemplo con un ingreso de 10,000 pesos al mes:\n- Necesidades: 5,000\n- Gustos: 3,000\n- Ahorro: 2,000\n\nTruco: aparta el ahorro en cuanto recibas tu dinero, no "lo que sobre" al final del mes, porque casi nunca sobra.', 1);
SET @t_presupuesto = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 2);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c4, 'Ahorro y crédito', 2);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El fondo de emergencia', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Un fondo de emergencia es dinero guardado solo para imprevistos: una enfermedad, perder el trabajo o una reparación urgente.\n\n¿Cuánto? Lo recomendado es juntar entre 3 y 6 meses de tus gastos básicos. Si gastas 6,000 pesos al mes en necesidades, tu meta sería entre 18,000 y 36,000 pesos.\n\nConsejos:\n- Guárdalo en una cuenta separada para no gastarlo por accidente.\n- Debe estar disponible rápido, así que no lo inviertas en algo que tarde meses en devolverse.\n- Empieza con una meta pequeña, por ejemplo 1,000 pesos, y ve subiendo.\n- Si lo usas, vuelve a llenarlo antes de ahorrar para otras metas.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El interés compuesto', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'El interés compuesto es ganar intereses sobre tus intereses. Con el tiempo, tu dinero crece cada vez más rápido.\n\nEjemplo: inviertes 10,000 pesos al 10 % anual y no retiras nada.\n- Año 1: 10,000 + 1,000 = 11,000\n- Año 2: 11,000 + 1,100 = 12,100\n- Año 3: 12,100 + 1,210 = 13,310\n\nCada año el 10 % se calcula sobre una cantidad mayor. Por eso empezar joven es una gran ventaja: el tiempo hace la mayor parte del trabajo.\n\nCuidado: el interés compuesto también funciona en tu contra con las deudas. Una deuda que no pagas crece de la misma manera.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Usar bien la tarjeta de crédito', 3);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una tarjeta de crédito es un préstamo: el banco paga por ti y luego tú le pagas al banco.\n\nConceptos clave:\n- Fecha de corte: el día en que se suman tus compras del mes.\n- Fecha límite de pago: el último día para pagar sin recargos.\n- Pago mínimo: la cantidad más pequeña que puedes pagar. Si solo pagas el mínimo, el resto genera intereses muy altos y la deuda puede durar años.\n- Pago para no generar intereses: si pagas esta cantidad completa, el crédito no te cuesta nada.\n\nReglas de oro:\n1. Úsala solo para compras que podrías pagar en efectivo.\n2. Paga siempre el total antes de la fecha límite.\n3. No uses más del 30 % de tu límite de crédito.', 1);
SET @t_credito = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 3);

INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t_presupuesto, 'Con la regla 50/30/20 y un ingreso de 8,000, ¿cuánto se ahorra?', '1,600', '2,400', '4,000', 1),
    (@t_presupuesto, '¿En qué parte del presupuesto va pagar la renta?', 'Gustos', 'Ahorro', 'Necesidades', 3),
    (@t_credito, '¿Qué pasa si solo haces el pago mínimo de tu tarjeta?', 'No pasa nada', 'El resto de la deuda genera intereses', 'El banco te regala el resto', 2),
    (@t_credito, '¿Qué pago evita que tu tarjeta te cobre intereses?', 'El pago mínimo', 'El pago para no generar intereses', 'Pagar después de la fecha límite', 2);

INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@c4, 'Mi presupuesto mensual',
     'Haz tu presupuesto de un mes con la regla 50/30/20. Usa tu ingreso real (domingo, beca o sueldo) o inventa uno.\n\nIncluye: ingreso total, lista de gastos de necesidades y gustos, y cuánto ahorrarás. Puedes entregar una hoja de Excel o escribirlo en la respuesta.',
     DATE_ADD(CURDATE(), INTERVAL 10 DAY));

INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@c4, 'Reto de la semana', 'Anoten todos sus gastos durante 7 días, por pequeños que sean. En el foro compartan cuál fue su "gasto hormiga" más grande.');


-- =====================================================
-- CURSO 5: Introducción a la astronomía (Ciencias)
-- =====================================================
INSERT INTO cursos (docente_id, categoria_id, titulo, descripcion, estado) VALUES
    (@jorge, (SELECT id FROM categorias WHERE nombre = 'Ciencias'),
     'Introducción a la astronomía',
     'Un recorrido desde nuestro Sol hasta las galaxias más lejanas. Conocerás los planetas, cómo nacen y mueren las estrellas, y cómo observar el cielo nocturno sin telescopio.',
     'publicado');
SET @c5 = LAST_INSERT_ID();

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c5, 'El sistema solar', 1);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'El Sol, nuestra estrella', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'El Sol es una estrella: una enorme esfera de gas, sobre todo hidrógeno y helio. Se formó hace unos 4,600 millones de años.\n\nDatos importantes:\n- Contiene cerca del 99.8 % de toda la masa del sistema solar.\n- Su superficie está a unos 5,500 °C.\n- En su centro, el hidrógeno se convierte en helio mediante fusión nuclear. Ese proceso libera la energía que nos llega como luz y calor.\n- Su luz tarda unos 8 minutos en llegar a la Tierra.\n\nPrecaución: nunca mires directamente al Sol, ni siquiera con lentes oscuros. Puede dañar tu vista de forma permanente.', 1),
    (@leccion, 'enlace', 'El sistema solar según la NASA', 'https://science.nasa.gov/solar-system/', 2);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Los planetas', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'El sistema solar tiene 8 planetas. En orden desde el Sol: Mercurio, Venus, Tierra, Marte, Júpiter, Saturno, Urano y Neptuno.\n\nSe dividen en dos grupos:\n- Rocosos (los 4 primeros): pequeños y de superficie sólida.\n- Gigantes (los 4 últimos): Júpiter y Saturno son gigantes gaseosos; Urano y Neptuno, gigantes helados.\n\nCuriosidades:\n- Júpiter es el más grande: dentro cabrían más de 1,300 Tierras.\n- Venus es el más caliente (unos 465 °C) aunque Mercurio está más cerca del Sol, porque su atmósfera atrapa el calor.\n- Saturno no es el único con anillos: los cuatro gigantes tienen, pero los suyos son los más visibles.\n\nPlutón fue considerado planeta hasta 2006; hoy se clasifica como planeta enano.', 1);
SET @t_planetas = (SELECT id FROM temas WHERE bloque_id = @b AND orden = 2);

INSERT INTO bloques (curso_id, titulo, orden) VALUES (@c5, 'Estrellas y galaxias', 2);
SET @b = LAST_INSERT_ID();
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'La vida de una estrella', 1);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Las estrellas nacen en nubes de gas y polvo llamadas nebulosas. La gravedad junta el material hasta que el centro se calienta tanto que empieza la fusión nuclear: así nace una estrella.\n\nSu final depende de su masa:\n- Estrellas como el Sol: cuando se les acaba el hidrógeno se inflan y se vuelven gigantes rojas. Después expulsan sus capas externas y queda una enana blanca, pequeña y muy caliente.\n- Estrellas mucho más grandes que el Sol: terminan en una explosión enorme llamada supernova. Lo que queda puede ser una estrella de neutrones o un agujero negro.\n\nEl color indica la temperatura: las estrellas azules son las más calientes y las rojas, las más frías.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Galaxias y la Vía Láctea', 2);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'Una galaxia es un conjunto gigantesco de estrellas, gas y polvo que se mantienen unidos por la gravedad.\n\nNuestra galaxia es la Vía Láctea:\n- Es una galaxia espiral barrada.\n- Mide unos 100,000 años luz de diámetro.\n- Tiene entre 100,000 y 400,000 millones de estrellas.\n- El sistema solar está en uno de sus brazos, lejos del centro.\n\nUn año luz es la distancia que recorre la luz en un año: unos 9.46 billones de kilómetros.\n\nLa galaxia grande más cercana es Andrómeda, a unos 2.5 millones de años luz. Desde un lugar muy oscuro se alcanza a ver a simple vista como una manchita borrosa.', 1);
INSERT INTO temas (bloque_id, titulo, orden) VALUES (@b, 'Cómo observar el cielo', 3);
SET @leccion = LAST_INSERT_ID();
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden) VALUES
    (@leccion, 'texto', NULL,
     'No necesitas telescopio para empezar:\n\n1. Busca un lugar oscuro, lejos de las luces de la ciudad.\n2. Deja que tus ojos se acostumbren a la oscuridad entre 20 y 30 minutos. No mires el celular: su luz blanca reinicia la adaptación. Si necesitas luz, usa una linterna roja.\n3. Usa un mapa del cielo. La app gratuita Stellarium te muestra qué estrellas y planetas hay sobre ti.\n\nQué buscar:\n- La Luna: con unos binoculares verás sus cráteres.\n- Planetas: brillan con luz fija, casi sin parpadear. Venus y Júpiter son los más brillantes.\n- Constelaciones: Orión es fácil de reconocer en invierno por las tres estrellas en línea de su cinturón.', 1),
    (@leccion, 'enlace', 'Stellarium: mapa del cielo en el navegador', 'https://stellarium-web.org/', 2);

INSERT INTO preguntas (tema_id, pregunta, opcion1, opcion2, opcion3, correcta) VALUES
    (@t_planetas, '¿Cuál es el planeta más grande del sistema solar?', 'Saturno', 'Júpiter', 'Neptuno', 2),
    (@t_planetas, '¿Cuál es el planeta más caliente?', 'Mercurio', 'Marte', 'Venus', 3),
    (@t_planetas, '¿Cuántos planetas tiene el sistema solar?', '8', '9', '10', 1);

INSERT INTO tareas (curso_id, titulo, instrucciones, fecha_limite) VALUES
    (@c5, 'Diario de observación',
     'Observa el cielo nocturno dos noches distintas, siguiendo los consejos de la lección "Cómo observar el cielo".\n\nAnota: fecha, hora, lugar, cómo estaba el cielo (despejado o nublado) y qué lograste identificar (la Luna, algún planeta, una constelación). Si puedes, agrega una foto o un dibujo.',
     DATE_ADD(CURDATE(), INTERVAL 14 DAY));

INSERT INTO avisos (curso_id, titulo, mensaje) VALUES
    (@c5, 'Noche de observación', 'Revisen el pronóstico del clima antes de la tarea: necesitan una noche despejada. Las noches cercanas a luna nueva son las mejores porque el cielo está más oscuro.');


-- =====================================================
-- Actividad de ejemplo (solo si existen los alumnos de prueba
-- de datos_ejemplo.sql). Fechas en meses distintos para que
-- la gráfica de "Inscripciones por mes" tenga datos.
-- =====================================================
INSERT INTO inscripciones (alumno_id, curso_id, fecha)
    SELECT u.id, x.curso, DATE_SUB(NOW(), INTERVAL x.meses MONTH)
    FROM usuarios u
    JOIN (SELECT 'alumno@cursonet.com' AS email, @c1 AS curso, 4 AS meses
          UNION ALL SELECT 'alumno@cursonet.com', @c5, 2
          UNION ALL SELECT 'alumna@cursonet.com', @c2, 3
          UNION ALL SELECT 'alumna@cursonet.com', @c3, 1
          UNION ALL SELECT 'alumna@cursonet.com', @c4, 0) x ON x.email = u.email;

INSERT INTO resenas (alumno_id, curso_id, estrellas, comentario)
    SELECT u.id, x.curso, x.estrellas, x.comentario
    FROM usuarios u
    JOIN (SELECT 'alumno@cursonet.com' AS email, @c1 AS curso, 5 AS estrellas,
                 'Por fin entendí los ciclos. Los ejemplos son muy claros.' AS comentario
          UNION ALL SELECT 'alumno@cursonet.com', @c5, 4, 'Me encantó el tema de las estrellas. Ya encontré a Orión.'
          UNION ALL SELECT 'alumna@cursonet.com', @c2, 5, 'La regla 60-30-10 me cambió la forma de hacer mis presentaciones.'
          UNION ALL SELECT 'alumna@cursonet.com', @c3, 4, 'Muy útil para mi viaje. Me gustaría más práctica de pronunciación.') x
      ON x.email = u.email;
