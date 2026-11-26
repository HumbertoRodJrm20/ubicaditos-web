# Desviaciones de requisitos

| ID | Decisión | Motivo |
|----|----------|--------|
| HU-003 | Se excluye el inicio de sesión de pasajeros mediante JWT. La aplicación conserva la autenticación web de sesión con Laravel Breeze; no se agrega JWT ni inicio de sesión por tokens. | Ubicaditos es un monolito Laravel con autenticación de sesión. El alcance del backlog excluye HU-003 expresamente. |