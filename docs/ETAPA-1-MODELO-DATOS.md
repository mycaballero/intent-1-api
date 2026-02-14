# Etapa 1: Modelo de datos – Autenticación y base multi-tenant

## 1. Resumen del modelo propuesto

El modelo cubre **solo** lo necesario para soportar:

- **Login**: usuario identificado por email, contraseña hasheada, y contexto multi-tenant/roles.
- **Forgot password**: tokens de un solo uso con expiración, almacenados hasheados.
- **Change password**: mismo usuario; la revocación de sesiones se soporta vía tabla de refresh tokens.

Se diseña desde el inicio para **multi-tenant**, **roles** (platform admin, tenant admin, tenant user) y **escalabilidad** (single DB con aislamiento por tenant).

---

## 2. Tablas y justificación

| Tabla | Propósito |
|-------|-----------|
| **tenants** | Organización/edificio/conjunto. Unidad de tenancy y futura suscripción. Aísla datos por organización. |
| **users** | Cuenta global por persona (email único). Passwords hasheados. Flag `is_platform_admin` para rol de plataforma sin mezclar con roles por tenant. |
| **tenant_users** | N:M usuario–tenant con **rol** en ese tenant. Un usuario puede estar en varios tenants con roles distintos. |
| **password_reset_tokens** | Tokens de “forgot password”: guardamos hash del token, expiración y `used_at` para un solo uso e invalidación. |
| **refresh_tokens** | Sesiones/refresh tokens para poder invalidar al hacer logout o “change password” (revocar todos los del usuario). |

No se implementa billing ni suscripciones en esta etapa; `tenants` queda listo para añadir `billing_customer_id` u otros campos después.

---

## 3. Diagrama lógico (texto)

```
┌─────────────────────┐
│      tenants        │
│─────────────────────│
│ id (PK)             │
│ name                │
│ slug (unique)       │
│ created_at          │
│ updated_at          │
└──────────┬──────────┘
           │
           │ 1
           │
           │ N
┌──────────▼──────────┐       N ┌─────────────────────┐
│   tenant_users      │◄───────│       users         │
│─────────────────────│        │─────────────────────│
│ id (PK)             │        │ id (PK)             │
│ tenant_id (FK)      │        │ email (unique)      │
│ user_id (FK)        │        │ password_hash       │
│ role                │        │ is_platform_admin   │
│ created_at          │  1     │ email_verified_at   │
│ updated_at          │        │ created_at          │
│ UNIQUE(tenant_id,   │        │ updated_at          │
│        user_id)     │        └──────────┬──────────┘
└─────────────────────┘                   │
                                          │ 1
                        ┌─────────────────┼─────────────────┐
                        │                 │                 │ N
                        ▼                 ▼                 ▼
           ┌─────────────────────┐  ┌─────────────────────────────┐
           │ password_reset_      │  │ refresh_tokens               │
           │ tokens               │  │─────────────────────────────│
           │─────────────────────│  │ id (PK)                      │
           │ id (PK)             │  │ user_id (FK)                 │
           │ user_id (FK)         │  │ token_hash                   │
           │ token_hash           │  │ expires_at                   │
           │ expires_at           │  │ revoked_at                   │
           │ used_at (nullable)   │  │ created_at                   │
           │ created_at           │  └─────────────────────────────┘
           └─────────────────────┘
```

---

## 4. Decisiones clave

- **PK numérica (bigint)**: Uso de `id` auto-increment (convención Laravel). Simplifica migraciones y relaciones; si en el futuro se requiere no revelar secuencialidad en APIs, puede exponerse un UUID en una columna adicional o en la capa de presentación.
- **Email único en `users`**: Login es global por email; el tenant se resuelve después (tenant seleccionado o primer tenant del usuario).
- **Password siempre hasheado**: Solo `password_hash`; el algoritmo (ej. bcrypt/argon2) se define en código, no en el esquema.
- **Tokens guardados como hash**: Tanto en `password_reset_tokens` como en `refresh_tokens` se guarda `token_hash`, nunca el valor en claro (seguridad y compliance).
- **`used_at` en password_reset_tokens**: Un solo uso; una vez usado, no se reutiliza. `expires_at` cubre la expiración temporal.
- **`refresh_tokens` con `revoked_at`**: Permite invalidar sesiones sin borrar filas (útil para auditoría y “cerrar todas las sesiones” al cambiar contraseña).
- **Rol de plataforma en `users`**: `is_platform_admin` evita una tabla extra de “platform_roles” en esta fase; los roles por tenant viven en `tenant_users.role`.
- **Single DB con tenant_id**: Todas las tablas de negocio futuras llevarán `tenant_id`; las consultas siempre filtran por tenant. Escalabilidad horizontal (read replicas, sharding) queda para más adelante.

---

## 5. Convenciones de nombres

- **Tablas**: `snake_case`, plural (`users`, `tenant_users`).
- **Campos**: `snake_case` (`password_hash`, `created_at`, `is_platform_admin`).
- **PKs**: `id` (bigint, auto-increment).
- **FKs**: `{tabla_singular}_id` (ej. `user_id`, `tenant_id`).
- **Timestamps**: `created_at`, `updated_at` (UTC en aplicación).

---

## 6. Mejoras futuras (sin implementar)

- **Billing**: En `tenants`, campos como `billing_provider`, `billing_customer_id`, `subscription_status`.
- **Auditoría**: Tablas `audit_log` o eventos para cambios sensibles (cambio de password, revocación de tokens).
- **Rate limiting / intentos fallidos**: Tabla `login_attempts` o contador en `users` para bloqueos temporales.
- **MFA**: Tabla `user_mfa` o `user_authenticators` cuando se requiera 2FA.
- **Sesiones**: Ampliar `refresh_tokens` con `device_info`, `ip`, `user_agent` para “cerrar sesión en este dispositivo”.
- **Soft delete**: `deleted_at` en `users` y/o `tenants` si el producto requiere baja lógica.

Cuando confirmes este modelo y las migraciones, se puede pasar a la siguiente etapa (servicios, controladores y endpoints de auth).
