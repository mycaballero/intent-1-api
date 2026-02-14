# Etapa 1 – Diseño de base de datos para autenticación

## 1. Alcance

Este documento define **solo la estructura de base de datos** necesaria para:

- **Login**: email + password (hasheado).
- **Forgot password**: token de un solo uso con expiración.
- **Change password**: cambio por usuario autenticado; revocación de sesiones vía tokens de API.

**No se incluye en esta etapa:** controladores, servicios, endpoints, multitenencia activa ni billing. La arquitectura de tablas deja espacio para escalar a roles, multi-conjunto y suscripciones más adelante.

---

## 2. Decisión: Sanctum vs JWT

| Criterio | Sanctum | JWT |
|----------|---------|-----|
| Integración Laravel | Nativa, mantenida por Laravel | Paquete de terceros (tymon/jwt-auth, etc.) |
| Almacenamiento | Tokens en BD (`personal_access_tokens`) | Stateless; opcional blacklist en BD |
| Revocación | Por token o global (p. ej. “cerrar todas las sesiones”) | Requiere blacklist o expiry corto + refresh en BD |
| Por dispositivo/app | Un token por dispositivo, nombre y abilities | Mismo token o lógica custom por cliente |
| Auditoría / RNF05 | Fácil: qué tokens existen, cuándo se usaron, expiración | Sin BD de tokens es más difícil |
| App Android híbrida | Mismo flujo: login → token → Authorization header | Mismo flujo posible con JWT |

**Recomendación: Laravel Sanctum.**

- API REST centralizada y futura app móvil se benefician de tokens en BD: revocación explícita al cambiar contraseña o cerrar sesión.
- Alineado con protección de datos (RNF05): se puede saber qué tokens hay y revocarlos.
- Escalabilidad (RNF06): mismo esquema sirve para varios clientes (web, Android) sin añadir complejidad de refresh tokens JWT ni blacklist en esta fase.
- Menos sobre-ingeniería que JWT para un solo backend Laravel.

Si en el futuro se necesitara stateless puro o integración con otros sistemas que exijan JWT, se puede añadir un segundo guard y migrar progresivamente.

---

## 3. Modelo de datos propuesto

### 3.1 Tablas involucradas

| Tabla | Propósito |
|-------|------------|
| **users** | Cuenta por persona: email único, password hasheado, verificación de email, remember token (por si hay SPA/cookies en el futuro). |
| **password_reset_tokens** | Tokens de “forgot password”: un registro por email, token hasheado, expiración implícita por tiempo (config). Un solo uso: Laravel elimina el registro al consumirlo. |
| **sessions** | Sesiones web (driver `database`). Útil para futuro panel web o SPA con sesión; la API usará tokens en `personal_access_tokens`. |
| **personal_access_tokens** | Tokens de API (Sanctum): uno por dispositivo/sesión, con nombre, abilities, expiración y last_used_at. Permite revocar todos los tokens de un usuario (p. ej. al cambiar contraseña). |

### 3.2 Diagrama lógico (texto)

```
                    ┌─────────────────────┐
                    │       users         │
                    │─────────────────────│
                    │ id (PK)             │
                    │ name                │
                    │ email (unique)      │
                    │ email_verified_at   │
                    │ password            │
                    │ remember_token      │
                    │ created_at          │
                    │ updated_at          │
                    └──────────┬──────────┘
                               │
         ┌─────────────────────┼─────────────────────┐
         │ 1                    │ 1                   │ 1
         │                      │                     │
         ▼ N                    ▼ N                   ▼ N
┌─────────────────────┐ ┌─────────────────────┐ ┌──────────────────────────────┐
│ password_reset_      │ │ sessions            │ │ personal_access_tokens       │
│ tokens               │ │─────────────────────│ │──────────────────────────────│
│─────────────────────│ │ id (PK)             │ │ id (PK)                      │
│ email (PK)           │ │ user_id (FK)        │ │ tokenable_type (morph)       │
│ token (hashed)       │ │ ip_address          │ │ tokenable_id (morph)         │
│ created_at           │ │ user_agent          │ │ name                         │
└─────────────────────┘ │ payload             │ │ token (unique, 64, hashed)   │
                        │ last_activity       │ │ abilities (nullable)         │
                        └─────────────────────┘ │ last_used_at (nullable)     │
                                                │ expires_at (nullable)        │
                                                │ created_at / updated_at      │
                                                └──────────────────────────────┘
```

- **users**: entidad central; sin `tenant_id` ni roles en esta fase.
- **password_reset_tokens**: relación conceptual por email (Laravel usa `email` como clave); el token se guarda hasheado por el framework.
- **sessions**: opcional para futura autenticación web por sesión.
- **personal_access_tokens**: relación polimórfica (`tokenable` = User); varios tokens por usuario (p. ej. uno por dispositivo).

---

## 4. Relaciones

- **User** → **password_reset_tokens**: por `email` (no FK en esta implementación estándar; un registro activo por email).
- **User** → **sessions**: `user_id` FK; una sesión pertenece a un usuario.
- **User** → **personal_access_tokens**: polimórfica `tokenable`; un usuario tiene muchos tokens de API.

No se definen aún relaciones con “conjuntos” ni “roles”; las tablas futuras (p. ej. `buildings`, `building_user`, `roles`) se añadirán cuando se active multitenencia y permisos.

---

## 5. Justificación de decisiones

### 5.1 Tabla `password_reset_tokens` estándar vs personalizada

- **Opción A (elegida):** Tabla estándar de Laravel (`email` PK, `token`, `created_at`). Laravel hashea el token al guardarlo, aplica expiración por tiempo (config) y elimina el registro al usarlo, por lo que se cumple “token con expiración” y “un solo uso”. Sin cambios en el broker ni en el esquema.
- **Opción B (futura):** Tabla personalizada con `user_id` (FK), `token_hash`, `expires_at`, `used_at` para auditoría explícita y un solo uso por campo `used_at`. Implica un TokenRepository custom y más complejidad; se puede valorar cuando existan requisitos de auditoría detallada (RNF05).

Para esta etapa se mantiene la tabla estándar.

### 5.2 Passwords y protección de datos

- Un solo campo `password` en `users`; Laravel lo hashea con bcrypt/argon2 (`password` => `hashed` en el model).
- No se almacenan contraseñas en claro ni tokens de reset en claro; alineado con buenas prácticas y RNF05.

### 5.3 Tokens de API (Sanctum)

- `personal_access_tokens`: token almacenado como hash (Sanctum usa hash de 40 caracteres en el valor que se envía al cliente; internamente guarda hash). Expiración opcional (`expires_at`), `last_used_at` para auditoría y `abilities` para futuros scopes.
- Revocación: al cambiar contraseña se pueden eliminar o marcar como revocados todos los tokens del usuario.

### 5.4 Sin multitenencia ni roles en esta fase

- No se crean tablas `tenants`, `tenant_users` ni `roles` en esta etapa.
- `users` se deja “global”; más adelante se puede añadir `tenant_id` o tabla pivot `user_buildings` / `tenant_users` sin romper el esquema actual.
- Roles: se puede añadir después `role` en `users` o tabla `roles` + pivot; el diseño actual no lo asume.

---

## 6. Migraciones propuestas

1. **0001_01_01_000000_create_users_table.php** (existente)  
   - Crea `users`, `password_reset_tokens` y `sessions`. Se mantiene tal cual.

2. **Nueva: create_personal_access_tokens_table.php**  
   - Tabla requerida por Sanctum para tokens de API: `tokenable` (morphs), `name`, `token` (64, unique), `abilities` (nullable), `last_used_at`, `expires_at`, `timestamps`.

No se modifican migraciones ya desplegadas; solo se añade la de `personal_access_tokens`.

---

## 7. Recomendaciones futuras

- **Multi-conjunto:** Añadir tabla `buildings` (o `complexes`) y pivot `building_user` (o `tenant_users`) con `building_id` + `user_id` + rol; consultas siempre filtradas por `building_id`.
- **Roles:** Tabla `roles` y pivot `role_user`, o columna `role` en `users` si solo hay un rol por usuario; para roles por edificio, mejor en la pivot usuario–edificio.
- **Auditoría:** Si se exige trazabilidad de resets de contraseña, valorar tabla personalizada de `password_reset_tokens` con `used_at` y opcionalmente `user_id`.
- **MFA / 2FA:** En el futuro, tabla dedicada (p. ej. `user_authenticators` o uso de paquete MFA) sin tocar el núcleo de auth actual.
- **Suscripciones/billing:** Campos en la futura tabla de conjuntos (p. ej. `billing_customer_id`, `subscription_status`) cuando se integre pasarela de pago.

---

## 8. Resumen

- **Login:** `users` (email + password hasheado).  
- **Forgot password:** `password_reset_tokens` (estándar Laravel; token hasheado, un uso, expiración por config).  
- **Change password:** mismo usuario; revocación de sesiones vía borrado/revocación de filas en `personal_access_tokens`.  
- **API:** Sanctum con `personal_access_tokens`; sin JWT en esta fase.  
- Estructura lista para crecer hacia roles, multi-conjunto y auditoría sin romper el diseño actual.

Cuando confirmes este diseño y las migraciones, se puede pasar a la siguiente etapa (instalación de Sanctum, servicios, controladores y endpoints de autenticación).
