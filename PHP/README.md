# Acceso a Smart Business

Los estilos de la interfaz se encuentran en `Estilos_CSS` en la raíz del proyecto: `login.css` para el inicio de sesión y `portal.css` para las páginas internas.
Los archivos JavaScript se encuentran en `Desarrollos_JS` en la raíz del proyecto.

## Requisitos y prueba local

- PHP 8.0 o posterior con la extensión `pdo_mysql`.
- MySQL o MariaDB y una base de datos llamada `extranet_business`.
- Apache o Nginx con PHP para servirla en producción. La raíz pública del sitio debe apuntar a la carpeta del proyecto para que `index.php`, `PHP/`, `Estilos_CSS/`, `Desarrollos_JS/` y `Logo/` queden en las rutas esperadas.

Para una prueba local en Windows o Linux, abre una terminal en la raíz del proyecto y ejecuta:

```text
php -S 127.0.0.1:8000
```

Visita `http://127.0.0.1:8000/`. El servidor integrado de PHP es solo para desarrollo y demostraciones locales; no lo uses como servidor de producción.

## Configuración local de MySQL

1. Crea el archivo de configuración fuera del directorio público del servidor web y limita su acceso al administrador y a la cuenta que ejecuta PHP. No compartas ni subas este archivo al repositorio. Por defecto, en XAMPP se busca `C:\xampp\private\smart-business-db.php`; en Linux se busca `/etc/smart-business/smart-business-db.php`. Para usar otra ubicación, define `SMART_BUSINESS_DB_CONFIG` con la ruta absoluta del archivo.
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

3. Crea el usuario administrador desde la raíz del proyecto:

   ```powershell
   powershell -ExecutionPolicy Bypass -File .\PHP\crear-administrador.ps1
   ```

   Si usas una ubicación distinta de la predeterminada en AlmaLinux con PHP-FPM, configura la variable en el pool (por ejemplo, en `/etc/php-fpm.d/www.conf`) y reinicia PHP-FPM. Para ejecutar comandos PHP desde la terminal con esa ubicación personalizada, exporta la misma variable antes del comando:

   ```ini
   env[SMART_BUSINESS_DB_CONFIG] = /etc/smart-business/smart-business-db.php
   ```

   ```bash
   export SMART_BUSINESS_DB_CONFIG=/etc/smart-business/smart-business-db.php
   ```

   Para el instalador, desde la raíz del proyecto, ejecuta:

   ```bash
   read -r -s -p "Contraseña inicial de vcanova: " ADMIN_PASSWORD
   printf '\n'
   printf '%s\n' "$ADMIN_PASSWORD" | php PHP/install-admin.php
   unset ADMIN_PASSWORD
   ```

   En Windows, el script solicita dos veces la contraseña para confirmarla; en AlmaLinux, el comando la solicita una vez. No la compartas. Debe tener entre 8 y 72 bytes en UTF-8. El instalador crea las tablas `usuarios`, `roles`, `modulos` y `permisos` si no existen; no modifica ni reemplaza un usuario `vcanova` existente. El rol inicial Administrador tiene acceso completo y el rol Usuario parte sin permisos.

4. Abre la URL que corresponda a la raíz pública configurada en Apache/Nginx e inicia sesión con `vcanova` y la contraseña elegida. Después de la instalación, reduce los permisos de la cuenta de MySQL a los necesarios para el funcionamiento.

## Seguridad y alcance

Las contraseñas de los usuarios se guardan como hashes; la contraseña de MySQL permanece en el archivo privado. El inicio de sesión usa sesiones y tokens CSRF. La sesión termina al cerrar el navegador o después de dos horas de inactividad. El formulario no incluye recuperación de contraseña ni sesiones persistentes.

Los administradores pueden crear roles, configurar permisos y gestionar usuarios. Los demás roles empiezan sin permisos; solo pueden acceder a los módulos y acciones autorizados. Solo un administrador puede asignar otros roles o modificar sus permisos.

El portal muestra en el menú lateral y en los accesos rápidos únicamente las opciones disponibles para el usuario. En escritorio se puede reducir el menú lateral; en móviles se adapta al ancho de pantalla.

El módulo Clientes permite registrar y editar clientes desde Área Comercial. El buscador filtra los clientes activos por razón social, RUC, certificado y año de registro; requiere JavaScript. Se guarda la fecha de los nuevos clientes; los anteriores aparecen en «Sin fecha» porque su fecha de registro no se conoce. El certificado se selecciona entre OEA, BASC, ISO 2800 y No-Tiene; los valores anteriores se conservan al editar. Su estado se selecciona manualmente. Con el permiso «Eliminar» de Clientes se puede archivar un cliente y restaurarlo desde «Clientes archivados»; sus datos no se eliminan. El administrador puede asignar los permisos de Clientes desde Roles y permisos; los demás roles no reciben acceso automáticamente.

En BASC → Comercial, la matriz muestra los mismos clientes activos cuyo estado es «Homologado», independientemente de su certificado. El rubro y las cinco respuestas se guardan por cliente y no se rellenan automáticamente con datos supuestos. El puntaje cuenta los «Sí», incluso con respuestas pendientes: 0 queda sin clasificar, 1–2 es «NO CRÍTICO» y 3–5 es «CRÍTICO». La matriz señala cuando faltan respuestas. Si el cliente deja de estar homologado o se archiva, desaparece de la matriz; su evaluación se conserva por si vuelve a estar activo y homologado. El administrador puede asignar los permisos independientes «Ver» y «Editar» del módulo «BASC Comercial» desde Roles y permisos; los demás roles no reciben acceso automáticamente.

El código, la versión y la fecha del documento se guardan de forma compartida. Para modificarlos se necesita «Ver» de BASC Comercial y el permiso independiente «Editar» de BASC Documento; el administrador tiene acceso completo. El permiso de BASC Documento se configura desde Roles y permisos y no concede permiso para modificar las evaluaciones de clientes.

En una instalación existente, ejecuta desde la raíz del proyecto `C:\xampp\php\php.exe .\PHP\actualizar-clientes.php` en XAMPP o `php PHP/actualizar-clientes.php` en Linux para crear o actualizar las tablas y registrar los módulos sin modificar los datos existentes. Se requieren permisos de creación y modificación de tablas e inserción. En una instalación nueva, el instalador inicial crea los módulos.

HTTP es apropiado solo para pruebas locales: antes de publicar o usar la extranet en una red, configura HTTPS, protección frente a intentos repetidos de acceso a nivel de servidor y permisos restringidos para la configuración y la base de datos. El bloqueo actual tras cinco intentos fallidos solo afecta a la sesión del navegador y no sustituye una protección contra fuerza bruta. No hay recuperación automática de contraseñas.
