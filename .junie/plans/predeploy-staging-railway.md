---
sessionId: session-261126-004254-zk3z
---

# Requirements

### Objetivo
Corregir el rechazo del registro de pasajeros cuando la contraseña cumple con los requisitos mostrados al usuario, sin debilitar las condiciones de longitud, composición ni confirmación.

### Alcance
- Alinear la validación del navegador en `resources/views/auth/register.blade.php` con la regla autoritativa del servidor en `app/Http/Controllers/Auth/RegisteredUserController.php`.
- Añadir regresiones a `tests/Feature/Auth/RegistrationTest.php` para cubrir símbolos comunes fuera de la lista actual, así como las condiciones de contraseña que deben seguir rechazándose.
- No modificar credenciales, base PostgreSQL, configuración de Railway, registros de usuarios existentes ni el flujo distinto de registro de conductores/recuperación de contraseña.

### Comportamiento esperado
- El registro acepta contraseñas de al menos 8 caracteres que incluyan letra, dígito y un símbolo no alfanumérico común, incluida puntuación como `#` y `_`, siempre que coincidan con su confirmación.
- El navegador y Laravel muestran el mismo criterio; contraseñas cortas, sin letra, sin dígito, sin símbolo o sin confirmación coincidente siguen siendo rechazadas con mensajes en español.

# Technical Design

### Implementación actual
- `routes/auth.php` registra `POST /register` en el grupo `guest` y lo dirige a `RegisteredUserController::store`; el correo se consulta aparte con `POST /check-email`.
- El servidor impone mínimo de 8 caracteres, confirmación, letra y dígito, pero su regex solo permite los símbolos `@$!%*?&` y prohíbe cualquier otro carácter.
- El indicador de seguridad de `register.blade.php` cuenta como símbolo cualquier carácter no alfanumérico; la validación del navegador también usa una lista reducida. Por tanto, su mensaje genérico sobre símbolos no describe la lista que realmente admite Laravel.
- `RegistrationTest` confirma el caso `Password1!`, pero no cubre símbolos como `#` o `_`. Un `302` de `POST /register` seguido de `/register` concuerda con un rechazo de validación, pero el access log no identifica el campo ni la regla que lo causó.

### Decisión técnica
Conservar el mínimo de 8 caracteres, el requisito de al menos una letra y un dígito, y la confirmación; ampliar el criterio de símbolo a puntuación/símbolos no alfanuméricos sin espacios, coherente con el indicador visual existente. Usar una única política semántica equivalente en PHP y JavaScript, manteniendo la validación del servidor como autoridad y sin cambiar el comportamiento de otros formularios de contraseña.

### Cambios propuestos
- Actualizar la regex del campo `password` de `RegisteredUserController::store` para admitir puntuación común fuera de la lista actual, manteniendo los requisitos de composición existentes.
- Ajustar `validatePassword()` en `register.blade.php` para verificar los mismos requisitos y evitar una falsa discrepancia entre indicador de seguridad, validación cliente y servidor.
- Extender `RegistrationTest` con un registro válido usando `#` o `_`, y casos inválidos para omisión de cada requisito; preservar el caso existente con `!`.

# Testing

### Validación
- Ejecutar primero `php artisan test --filter=RegistrationTest`, verificando que las nuevas pruebas pasan y el usuario válido queda persistido como pasajero.
- Ejecutar `php artisan test` para verificar regresiones en autenticación y en el resto de la aplicación.
- Ejecutar `./vendor/bin/pint --test app/Http/Controllers/Auth/RegisteredUserController.php tests/Feature/Auth/RegistrationTest.php` y `git diff --check`.
- Comprobar por revisión del diff que el navegador y el servidor aplican la misma política a ejemplos válidos e inválidos; no usar ni solicitar contraseñas reales en logs o pruebas.

### Criterio de aceptación
Una contraseña como `Password1#` pasa registro y confirmación; una contraseña sin letra, dígito, símbolo o longitud mínima conserva el rechazo. La pantalla no debe mostrar el error de símbolos para una contraseña que cumpla el criterio acordado.

# Delivery Steps

### ✓ Step 1: Corregir la política de contraseña del registro en el servidor
El endpoint de pasajeros acepta símbolos comunes no alfanuméricos y conserva sus requisitos de seguridad, con regresiones automatizadas en `RegistrationTest`.

- Añadir pruebas de registro para un símbolo no permitido por la regex actual y para contraseñas que omiten cada requisito obligatorio.
- Actualizar la regla de `password` en `RegisteredUserController::store` para reflejar la política documentada, dejando intactos la longitud mínima, `confirmed`, la letra y el dígito.
- Ejecutar `php artisan test --filter=RegistrationTest` y Pint dirigido a los archivos PHP tocados.

### ✓ Step 2: Alinear la validación del formulario y verificar el flujo completo
El formulario del navegador y Laravel aceptan/rechazan los mismos ejemplos sin alterar la comprobación independiente del correo.

- Ajustar `validatePassword()` en `resources/views/auth/register.blade.php` al mismo criterio del servidor y conservar los mensajes localizados existentes o afinarlos para indicar los requisitos exactos.
- Confirmar que `POST /check-email` sigue separado y no bloquea la validación del formulario por estado temporal de disponibilidad.
- Ejecutar la suite completa y revisar que `submitForm()` no detenga contraseñas válidas según la regla del servidor; no registrar valores de contraseña ni desplegar en Railway como parte de este cambio.