# CursoNet

Plataforma para crear y tomar cursos en línea (PHP + MySQL).

## Cómo instalarlo

1. Crear la base de datos:
   ```
   mysql -u root -p < database.sql
   ```
   **¿Ya tenías una versión anterior instalada?** En vez de eso corre, una sola vez y en orden,
   solo los que te falten (no borran tus usuarios ni cursos):
   - de la versión 1 a la 2: `mysql -u root -p < actualizar.sql`
   - de la versión 2 a la 3: `mysql -u root -p < actualizar2.sql`
2. (Opcional) Cargar datos de ejemplo para probar:
   ```
   mysql -u root -p < datos_ejemplo.sql
   ```
   (Opcional) Cargar 5 cursos completos con contenido real (Python, diseño, inglés,
   finanzas y astronomía), con cuestionarios, tareas y avisos:
   ```
   mysql -u root -p < cursos_contenido.sql
   ```
3. Revisar el usuario y la contraseña de MySQL en `conexion.php`.
4. Desde la carpeta del proyecto, encender el servidor:
   ```
   php -S localhost:8000
   ```
5. Abrir http://localhost:8000/login.php

## Usuarios para entrar

| Rol         | Correo                | Contraseña |
|-------------|-----------------------|------------|
| Super Admin | admin@cursonet.com    | admin123   |
| Director    | director@cursonet.com | 123456     |
| Docente     | docente@cursonet.com  | 123456     |
| Alumno      | alumno@cursonet.com   | 123456     |
| Docente     | mariana@cursonet.com  | 123456     |
| Docente     | jorge@cursonet.com    | 123456     |

(Director, Carlos y los alumnos existen si se cargó `datos_ejemplo.sql`; Mariana y Jorge, si se cargó `cursos_contenido.sql`.)

## Qué puede hacer cada rol

- **Super Admin:** crear usuarios, cambiar roles, activar/desactivar cuentas, ver y eliminar cursos,
  administrar categorías, ver gráficas y descargar reportes en Excel.
- **Director:** ver todos los cursos, el avance de los alumnos, gráficas y reportes en Excel.
- **Docente:** crear cursos (con categoría), organizarlos en módulos y lecciones, armar cada lección
  con secciones (texto, código, video, imagen, enlace, nota), agregar un cuestionario al final,
  revisar con la vista previa, dejar tareas y calificarlas, publicar avisos y ver el avance y los
  resultados de cuestionarios de sus alumnos.
- **Alumno:** buscar y filtrar cursos, guardar favoritos, inscribirse, estudiar los temas, contestar
  cuestionarios, entregar tareas, dejar reseñas con estrellas y obtener su constancia al terminar.
- **Todos:** foro por curso, notificaciones (campanita) y "Mi perfil" para cambiar foto, nombre y contraseña.

## Cómo se organiza un curso

```
Curso
 └─ Módulo 1            (en la base de datos: tabla "bloques")
     └─ Lección 1.1     (en la base de datos: tabla "temas")
         ├─ Secciones   texto · código · video · imagen · enlace · nota importante
         └─ Cuestionario final (opcional, de 2 a 4 opciones por pregunta)
```

El docente trabaja en pestañas: **Contenido · Tareas · Avisos · Alumnos · Foro · Ajustes**.
Antes de publicar, la lista "Antes de publicar" revisa que no haya módulos ni lecciones vacíos.

## Diseño

Estilo "papel crema + tinta violeta": fondo crema `#fffcf7`, texto y bordes `#0d0129`,
botones amarillos `#fae59b` con sombra dura, bandas verde azulado `#19615c`, esquinas rectas
y dibujos hechos solo con líneas. Todo está en `css/estilos.css`.

## Archivos

| Archivo | Qué hace |
|---|---|
| `conexion.php` | Conecta con la base de datos |
| `includes/proteger.php` | Revisa que haya sesión iniciada |
| `includes/funciones.php` | Herramientas compartidas (consultas, avance, etc.) |
| `includes/arriba.php` / `abajo.php` | Menú y estructura de las páginas privadas |
| `includes/arriba_publico.php` / `abajo_publico.php` | Estructura del login y registro |
| `css/estilos.css` | Colores y diseño |
| `login.php`, `registro.php`, `logout.php` | Entrar, crear cuenta, salir |
| `panel.php` | Inicio (distinto para cada rol) |
| `usuarios.php` | Gestión de usuarios (Super Admin) |
| `cursos.php`, `reporte.php` | Cursos y avance (Super Admin, Director) |
| `docente_cursos.php` | Lista de cursos del docente y crear uno nuevo |
| `editar_curso.php` | Módulos y lecciones (pestaña Contenido) y Ajustes del curso |
| `editar_tema.php` | Editor de una lección: título, secciones y cuestionario |
| `includes/curso_docente.php` | Encabezado y pestañas del curso para el docente |
| `catalogo.php`, `mis_cursos.php`, `curso.php`, `tema.php` | Tomar cursos (Alumno); `tema.php` también es la vista previa del docente |
| `foro.php` | Foro de cada curso |
| `perfil.php` | Foto, datos y contraseña del usuario |
| `tareas.php`, `tarea.php` | Lista de tareas; entregar (alumno) y calificar (docente) |
| `avisos.php` | Avisos del docente a su curso |
| `constancia.php` | Constancia imprimible al terminar un curso |
| `notificaciones.php` | La campanita: avisos de tareas, calificaciones, etc. |
| `exportar.php` | Descarga reportes en Excel (.csv) |
| `includes/globo.php` | Mensajes que aparecen arriba y se van solos |
| `actualizar.sql`, `actualizar2.sql` | Actualizan una base vieja (v1 → v2 → v3) |
| `cursos_contenido.sql` | 5 cursos de ejemplo con contenido real |
| `subidas/` | Fotos de perfil y archivos de tareas (máx. 2 MB) |
| `img/favicon.svg` | Ícono de la pestaña (el logo) |
