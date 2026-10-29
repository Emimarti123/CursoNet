-- =====================================================
-- Actualización de CursoNet (versión 2)
-- Agrega las tablas y columnas de las funciones nuevas
-- SIN borrar los usuarios ni los cursos que ya existen.
--
-- Solo se corre UNA vez, en una base que ya tenía la versión 1:
--   mysql -u root -p < actualizar.sql
--
-- (Si vas a instalar desde cero, usa database.sql: ya trae todo.)
-- =====================================================

USE cursos;

-- Foto de perfil (guarda el nombre del archivo, ej. "fotos/a1b2c3.jpg")
ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) NULL AFTER rol;

-- -----------------------------------------------------
-- CATEGORÍAS de cursos (Programación, Diseño...)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
);

INSERT IGNORE INTO categorias (nombre) VALUES
    ('Programación'), ('Diseño'), ('Idiomas'), ('Negocios'), ('Ciencias');

-- Cada curso puede tener una categoría (o ninguna)
-- ON DELETE SET NULL: si se borra la categoría, el curso se queda "sin categoría"
ALTER TABLE cursos ADD COLUMN categoria_id INT NULL AFTER docente_id,
    ADD FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

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
-- PREGUNTAS: cuestionario de opción múltiple de un tema
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS preguntas (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    tema_id  INT NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    opcion1  VARCHAR(150) NOT NULL,
    opcion2  VARCHAR(150) NOT NULL,
    opcion3  VARCHAR(150) NOT NULL,
    correcta TINYINT NOT NULL,                            -- 1, 2 o 3
    FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE CASCADE
);

-- Último resultado de cada alumno en el cuestionario de un tema
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
