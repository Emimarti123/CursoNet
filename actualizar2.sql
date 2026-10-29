-- =====================================================
-- Actualización de CursoNet (versión 2 -> versión 3)
-- Las lecciones ahora están hechas de SECCIONES
-- (texto, código, video, imagen, enlace, nota importante)
-- y los cuestionarios aceptan de 2 a 4 opciones.
--
-- Solo se corre UNA vez, en una base que ya tenía la versión 2:
--   mysql -u root -p < actualizar2.sql
-- (Si venías de la versión 1, primero corre actualizar.sql.)
-- =====================================================

USE cursos;

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

-- Pasamos el contenido que ya existía a secciones, sin perder nada:
-- el texto de cada lección se vuelve una sección de texto...
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden)
    SELECT id, 'texto', NULL, contenido, 1 FROM temas
    WHERE TRIM(COALESCE(contenido, '')) <> '';

-- ...y su "material extra" se vuelve una sección de enlace
INSERT INTO secciones (tema_id, tipo, titulo, contenido, orden)
    SELECT id, 'enlace', 'Material extra', enlace, 2 FROM temas
    WHERE TRIM(COALESCE(enlace, '')) <> '';

-- Las columnas viejas ya no se usan
ALTER TABLE temas DROP COLUMN contenido, DROP COLUMN enlace;

-- -----------------------------------------------------
-- PREGUNTAS: ahora de 2 a 4 opciones y con orden
-- -----------------------------------------------------
ALTER TABLE preguntas
    MODIFY opcion3 VARCHAR(150) NULL,
    ADD COLUMN opcion4 VARCHAR(150) NULL AFTER opcion3,
    ADD COLUMN orden INT NOT NULL DEFAULT 1;

-- Numeramos las preguntas que ya existían (1, 2, 3...) dentro de cada lección
UPDATE preguntas p
JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY tema_id ORDER BY id) AS n FROM preguntas) x ON p.id = x.id
SET p.orden = x.n;
