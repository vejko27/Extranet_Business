# Acceso a Smart Business

Los estilos de la interfaz se encuentran en `Estilos_CSS` en la raíz del proyecto: `login.css` para el inicio de sesión y `portal.css` para las páginas internas.
Los archivos JavaScript se encuentran en `Desarrollos_JS` en la raíz del proyecto.

## Configuración local de MySQL

1. Crea `C:\xampp\private\smart-business-db.php` fuera de `htdocs` y limita el acceso a ese archivo al administrador del equipo y a la cuenta que ejecuta Apache. No compartas ni subas este archivo al repositorio.
2. Escribe la configuración siguiente, sustituyendo el usuario y la contraseña por las credenciales de MySQL. La base de datos `extranet_business` debe existir. Durante la instalación el usuario necesita permisos para crear tablas, consultar e insertar datos; durante el funcionamiento necesita SELECT, INSERT y UPDATE en las tablas de la aplicación.

   ```php
   <?php
   return [
       'host' => '127.0.0.1',
       'database' => 'extranet_business',
       'username' => 'USUARIO_MYSQL',
       'password' => 'CONTRASEÑA_MYSQL',
   ];
   ```

3. En PowerShell, desde la raíz del proyecto, ejecuta:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\PHP\crear-administrador.ps1
   ```

   Introduce dos veces, sin compartirla en el chat, la contraseña inicial del usuario `vcanova`. Debe tener al menos 8 y como máximo 72 bytes en UTF-8. El instalador crea las tablas `usuarios`, `roles`, `modulos` y `permisos` si no existen; no modifica ni reemplaza un usuario `vcanova` existente. El rol inicial Administrador tiene acceso completo y el rol Usuario parte sin permisos.

4. Abre `http://localhost/Extranet_Business/` e inicia sesión con `vcanova` y la contraseña elegida. Después de la instalación, reduce los permisos de la cuenta de MySQL a los necesarios para el funcionamiento.

## Seguridad y alcance

Las contraseñas de los usuarios se guardan como hashes; la contraseña de MySQL permanece en el archivo privado. El inicio de sesión usa sesiones y tokens CSRF. La sesión termina al cerrar el navegador o después de dos horas de inactividad. El formulario no incluye recuperación de contraseña ni sesiones persistentes.

Los administradores pueden crear roles, configurar permisos y gestionar usuarios. Los demás roles empiezan sin permisos; solo pueden acceder a los módulos y acciones autorizados. Solo un administrador puede asignar otros roles o modificar sus permisos.

El portal muestra en el menú lateral y en los accesos rápidos únicamente las opciones disponibles para el usuario. En escritorio se puede reducir el menú lateral; en móviles se adapta al ancho de pantalla.

El módulo Clientes permite registrar y editar clientes desde Área Comercial. El certificado se selecciona entre OEA, BASC e ISO 2800; los valores anteriores se conservan al editar. Su estado se selecciona manualmente. Con el permiso «Eliminar» de Clientes se puede archivar un cliente y restaurarlo desde «Clientes archivados»; sus datos no se eliminan. El administrador puede asignar los permisos de Clientes desde Roles y permisos; los demás roles no reciben acceso automáticamente.

En una instalación existente, ejecuta `C:\xampp\php\php.exe .\PHP\actualizar-clientes.php` desde la raíz del proyecto para crear o actualizar la tabla y registrar el módulo sin modificar los datos existentes. Se requieren permisos de creación y modificación de tablas e inserción. En una instalación nueva, el instalador inicial crea el módulo.

HTTP es apropiado solo para pruebas locales: antes de publicar o usar la extranet en una red, configura HTTPS, protección frente a intentos repetidos de acceso a nivel de servidor y permisos restringidos para la configuración y la base de datos. El bloqueo actual tras cinco intentos fallidos solo afecta a la sesión del navegador y no sustituye una protección contra fuerza bruta. No hay recuperación automática de contraseñas.
