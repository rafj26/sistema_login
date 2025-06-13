# PHP Cookies & Sessions Lab

Un sistema completo de autenticación web en PHP que demuestra el uso de **cookies**, **sesiones** y **tokens de seguridad**. Perfecto para estudiantes de desarrollo web que quieren aprender sobre manejo de estado y autenticación.

## Características

- **Autenticación segura** con hash de contraseñas
- **Cookies persistentes** con función "Recuérdame" (30 días)
- **Gestión de perfil** completa con cambio de contraseña
- **Temas personalizables** (claro/oscuro)
- **Gestión de sesiones** con visualización de tokens activos
- **Diseño responsive** y moderno

## Instalación Rápida

1. **Clona el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/php-cookies-sessions-lab.git
   cd php-cookies-sessions-lab
   ```

2. **Configura tu servidor web** (Apache/Nginx + PHP + MySQL)

3. **Ejecuta la instalación automática**
   ```bash
   # Copia el instalador desde backup
   cp backup/install.php .
   
   # Accede vía navegador
   http://localhost/install.php
   ```

4. **Limpia archivos de instalación** (recomendado por seguridad)
   ```bash
   rm install.php
   ```

5. **¡Listo!** Accede a `login.php` y usa:
   - **Usuario:** `admin` | **Contraseña:** `password`

## Reinstalación / Rollback

Si quieres empezar de cero o probar nuevamente:

```bash
# 1. Restaurar archivos de instalación
cp backup/install.php .
cp backup/verificar_usuarios.php .

# 2. Eliminar base de datos actual (opcional)
mysql -u root -p -e "DROP DATABASE IF EXISTS lab9;"

# 3. Ejecutar instalación nuevamente
http://localhost/install.php

# 4. Limpiar archivos de instalación
rm install.php verificar_usuarios.php
```

## Estructura del Proyecto

```
├── backup/                    # Archivos de instalación y rollback
│   ├── install.php           #     Instalación automática
│   ├── verificar_usuarios.php #     Diagnóstico de usuarios
│   └── database_structure.sql #     Script de base de datos
├── config.php                # Configuración de base de datos
├── login.php                 # Formulario de login
├── inicio.php                # Dashboard principal
├── perfil.php                # Gestión de perfil
└── logout.php                # Cierre de sesión
```

> **Nota:** Los archivos en `backup/` se usan solo para instalación inicial y rollback. No se ejecutan en producción por seguridad.

## Conceptos Demostrados

### Cookies y Sesiones
- Diferencias entre cookies y sesiones PHP
- Implementación de "Recuérdame" con tokens seguros
- Limpieza y gestión de tokens expirados

### Seguridad
- Hash de contraseñas con `password_hash()` y `password_verify()`
- Tokens aleatorios criptográficamente seguros
- Validación y sanitización de datos

### UX/UI
- Temas dinámicos con CSS variables
- Diseño responsive con CSS Grid/Flexbox
- Transiciones suaves y feedback visual

## Tecnologías

- **Backend:** PHP 7.4+, MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript Vanilla
- **Seguridad:** PDO, Password Hashing, Secure Tokens

## Casos de Uso Educativos

Perfect para aprender:
- Autenticación web básica
- Manejo de estado en aplicaciones web
- Buenas prácticas de seguridad
- Diseño de interfaces de usuario
- Gestión de base de datos con PDO

## Nivel

**Intermedio** - Requiere conocimientos básicos de:
- PHP y programación orientada a objetos
- HTML/CSS/JavaScript
- MySQL y bases de datos relacionales
- Conceptos de HTTP y cookies

## Seguridad

**Importante:** Este es un proyecto educativo. Para uso en producción, considera implementar:
- HTTPS obligatorio
- Rate limiting
- CSRF protection
- Logging de seguridad
- Validación más robusta

### Archivos de Backup
La carpeta `backup/` contiene archivos de instalación que:
- **Se usan solo una vez** para configuración inicial
- **No se ejecutan en producción** (eliminados por seguridad)
- **Permiten rollback** para testing y desarrollo
- **Facilitan la reinstalación** sin perder el código original

## Licencia

MIT License - Úsalo libremente para aprender y enseñar

## Contribuciones

¡Las contribuciones son bienvenidas! Especialmente:
- Mejoras de seguridad
- Nuevas características educativas
- Documentación
- Ejemplos adicionales

---

**¿Te gustó el proyecto?** ¡Dale una estrella y compártelo!

**¿Encontraste un bug?** Abre un issue y lo resolveremos juntos.

**¿Tienes ideas para mejorarlo?** ¡Crea un pull request!
