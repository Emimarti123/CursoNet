-- =====================================================
-- Base de datos de CursoNet
-- Para crearla en otra computadora:
--   mysql -u root -p < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS cursos CHARACTER SET utf8mb4;
USE cursos;

-- -----------------------------------------------------
-- USUARIOS: todas las personas que usan la plataforma
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,              -- no se puede repetir
    password   VARCHAR(255) NOT NULL,                     -- guardada encriptada
    rol        ENUM('super_admin', 'director', 'docente', 'alumno') NOT NULL DEFAULT 'alumno',
    foto       VARCHAR(255) NULL,                         -- foto de perfil (opcional)
    activo    TINYINT(1) NOT NULL DEFAULT 1,             -- 1 = activo, 0 = desactivado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- CATEGORÍAS de cursos (Programación, Diseño...)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
);

INSERT IGNORE INTO categorias (nombre) VALUES
    ('Programación'), ('Diseño'), ('Idiomas'), ('Negocios'), ('Ciencias');

-- -----------------------------------------------------
-- CURSOS: cada curso lo crea un docente
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    docente_id   INT NOT NULL,                            -- quién lo creó
    categoria_id INT NULL,                                -- opcional
    titulo       VARCHAR(150) NOT NULL,
    descripcion  TEXT,
    estado       ENUM('borrador', 'publicado') NOT NULL DEFAULT 'borrador',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id)   REFERENCES usuarios(id),
    -- si se borra la categoría, el curso se queda "sin categoría"
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Nombres: en pantalla un curso se divide en MÓDULOS, cada módulo en
-- LECCIONES y cada lección en SECCIONES. En la base de datos los
-- módulos se guardan en la tabla "bloques" y las lecciones en "temas".
-- -----------------------------------------------------

-- -----------------------------------------------------
-- BLOQUES (módulos): partes grandes de un curso (Módulo 1, Módulo 2...)
-- Si se borra el curso, se borran sus módulos (CASCADE)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS bloques (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo   VARCHAR(150) NOT NULL,
    orden    INT NOT NULL DEFAULT 1,                      -- para acomodarlos
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- TEMAS (lecciones): dentro de un módulo (Lección 1.1, 1.2...)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS temas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    bloque_id INT NOT NULL,
    titulo    VARCHAR(150) NOT NULL,
    orden     INT NOT NULL DEFAULT 1,
    FOREIGN KEY (bloque_id) REFERENCES bloques(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- SECCIONES: las partes que forman una lección, en orden
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS secciones (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tema_id   INT NOT NULL,                               -- la lección a la que pertenece
    tipo      ENUM('texto', 'codigo', 'video', 'imagen', 'enlace', 'nota') NOT NULL,
    titulo    VARCHAR(150) NULL,                          -- subtítulo, texto del enlace o pie de imagen
    contenido TEXT NOT NULL,                              -- el texto, el código, la dirección (URL) o el archivo
    orden     INT NOT NULL DEFAULT 1,
    FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- INSCRIPCIONES: qué alumno está en qué curso
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS inscripciones (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    curso_id  INT NOT NULL,
    fecha     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (alumno_id, curso_id),                         -- no inscribirse dos veces
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id)  REFERENCES cursos(id)   ON DELETE CASCADE
);

-- -----------------------------------------------------
-- TEMAS_COMPLETADOS: qué lecciones terminó cada alumno
-- (con esto se calcula el % de avance)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS temas_completados (
    alumno_id INT NOT NULL,
    tema_id   INT NOT NULL,
    fecha     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alumno_id, tema_id),                     -- cada tema se completa una vez
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tema_id)   REFERENCES temas(id)    ON DELETE CASCADE
);

-- -----------------------------------------------------
-- COMENTARIOS: mensajes del foro de cada curso
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS comentarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    curso_id   INT NOT NULL,
    usuario_id INT NOT NULL,                              -- quién lo escribió
    mensaje    TEXT NOT NULL,
    fecha      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id)   REFERENCES cursos(id)   ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- RESEÑAS: estrellas (1 a 5) y comentario de un alumno sobre un curso
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS resenas (
    alumno_id  INT NOT NULL,
    curso_id   INT NOT NULL,
    estrellas  TINYINT NOT NULL,
    comentario TEXT,
    fecha      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alumno_id, curso_id),                    -- una reseña por alumno y curso
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id)  REFERENCES cursos(id)   ON DELETE CASCADE
);

-- -----------------------------------------------------
-- FAVORITOS: cursos que un alumno guardó para después
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS favoritos (
    alumno_id INT NOT NULL,
    curso_id  INT NOT NULL,
    PRIMARY KEY (alumno_id, curso_id),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id)  REFERENCES cursos(id)   ON DELETE CASCADE
);

-- -----------------------------------------------------
-- PREGUNTAS: cuestionario de opción múltiple al final de una lección
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS preguntas (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    tema_id  INT NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    opcion1  VARCHAR(150) NOT NULL,
    opcion2  VARCHAR(150) NOT NULL,
    opcion3  VARCHAR(150) NULL,                           -- las opciones 3 y 4 son opcionales
    opcion4  VARCHAR(150) NULL,
    correcta TINYINT NOT NULL,                            -- 1, 2, 3 o 4
    orden    INT NOT NULL DEFAULT 1,
    FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE CASCADE
);

-- Último resultado de cada alumno en el cuestionario de una lección
CREATE TABLE IF NOT EXISTS intentos (
    alumno_id INT NOT NULL,
    tema_id   INT NOT NULL,
    aciertos  INT NOT NULL,
    total     INT NOT NULL,
    fecha     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (alumno_id, tema_id),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tema_id)   REFERENCES temas(id)    ON DELETE CASCADE
);

-- -----------------------------------------------------
-- TAREAS que el docente deja en un curso
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tareas (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    curso_id      INT NOT NULL,
    titulo        VARCHAR(150) NOT NULL,
    instrucciones TEXT,
    fecha_limite  DATE NULL,                              -- opcional
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
);

-- ENTREGAS: lo que manda cada alumno, y su calificación
CREATE TABLE IF NOT EXISTS entregas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id     INT NOT NULL,
    alumno_id    INT NOT NULL,
    texto        TEXT,
    archivo      VARCHAR(255) NULL,                       -- ej. "entregas/a1b2c3.pdf"
    calificacion TINYINT NULL,                            -- de 0 a 100; NULL = sin calificar
    comentario   TEXT,                                    -- retroalimentación del docente
    fecha        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tarea_id, alumno_id),                         -- una entrega por alumno
    FOREIGN KEY (tarea_id)  REFERENCES tareas(id)   ON DELETE CASCADE,
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- AVISOS que el docente publica para sus alumnos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS avisos (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo   VARCHAR(150) NOT NULL,
    mensaje  TEXT NOT NULL,
    fecha    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- NOTIFICACIONES (la campanita)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS notificaciones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,                              -- a quién le llega
    mensaje    VARCHAR(255) NOT NULL,
    enlace     VARCHAR(255),                              -- a dónde lleva al hacer clic
    leida      TINYINT(1) NOT NULL DEFAULT 0,
    fecha      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Super Admin inicial (para poder entrar la primera vez)
-- Email: admin@cursonet.com   Contraseña: admin123
-- -----------------------------------------------------
INSERT IGNORE INTO usuarios (nombre, email, password, rol)
VALUES ('Administrador', 'admin@cursonet.com',
        '$2y$12$9WnKLGls6r7LqfnfeRiF/.Qq2EjVhhHSMMk70urLfY8vDCrEiuWJu', 'super_admin');
