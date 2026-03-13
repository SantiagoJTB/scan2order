# Matriz de permisos por rol

Última actualización: 13-03-2026

## Alcance

Este documento resume los permisos **efectivos** por rol en la aplicación, basados en:

- Rutas frontend y guards de navegación.
- Restricciones en controladores backend (autorización real).
- Reglas de alcance por restaurante (ownership/vinculación).

> Nota: aunque existe un catálogo de permisos en `RolePermissionSeeder`, la autorización operativa se aplica principalmente por validaciones de rol y alcance en controladores.

---

## Roles existentes

- `superadmin`
- `admin`
- `staff`
- `cliente`

Fuente: `backend/database/seeders/RolePermissionSeeder.php`

---

## Resumen ejecutivo por rol

### superadmin

- Acceso global a todos los restaurantes y recursos.
- Gestión completa de usuarios (`admin`, `staff`, `cliente`).
- Puede cambiar contraseñas de cualquier usuario.
- Puede asignar admin y staff en restaurantes (con reglas de consistencia).

### admin

- Acceso solo a restaurantes que administra o creó.
- Gestiona operaciones del restaurante: productos, categorías, mesas, pedidos, reportes.
- Gestiona solo usuarios `staff` bajo su ownership.
- No puede crear `admin` ni `cliente`.

### staff

- Acceso operativo de caja/cocina y gestión de pedidos/pagos en su ámbito.
- Acceso limitado a restaurantes vinculados (y reglas de ownership en backend).
- En caja, validación extra de frontend: debe entrar con el `restaurant_id` asignado.

### cliente

- Flujo de compra: ver restaurantes activos, carrito, checkout, pedidos propios.
- Solo opera sobre sus propios pedidos.
- No tiene acceso al panel admin/staff.

---

## Frontend: acceso por rutas (guard)

Fuente: `frontend/src/router/index.js`

| Ruta | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| `/admin` | ✅ | ✅ | ❌ | ❌ |
| `/admin/restaurants` | ✅ | ✅ | ❌ | ❌ |
| `/admin/users` | ✅ | ✅ | ❌ | ❌ |
| `/admin/products` | ✅ | ✅ | ✅ | ❌ |
| `/admin/reports` | ✅ | ✅ | ❌ | ❌ |
| `/admin/restaurants/:restaurantId/operations` | ✅ | ✅ | ✅ | ❌ |
| `/staff/:restaurantId?` | ❌ | ❌ | ✅ | ❌ |
| `/caja/:restaurantId?` | ❌ | ❌ | ✅ | ❌ |
| `/cocina/:restaurantId?` | ❌ | ❌ | ✅ | ❌ |
| `/restaurants` | ❌ | ❌ | ❌ | ✅ |
| `/cart`, `/orders`, `/checkout`, `/profile` | ❌ | ❌ | ❌ | ✅ |

Redirecciones por rol tras login:

- `superadmin` / `admin` → `/admin`
- `staff` → `/staff/{restaurantId}` (siempre que exista restaurante asignado)
- `cliente` → `/restaurants`

Fuentes:

- `frontend/src/router/index.js`
- `frontend/src/views/Login.vue`
- `frontend/src/stores/auth.js`

---

## Backend: reglas de alcance por restaurante

Fuente principal: `backend/app/Http/Controllers/Controller.php`

### `managedRestaurantIds(user)`

- `superadmin`: todos los restaurantes.
- `admin`: restaurantes donde es admin o creador.
- `staff`: restaurantes vinculados + restaurantes del admin creador (si aplica).
- `cliente`: sin alcance de gestión.

### `canAccessRestaurant(user, restaurantId)`

- `superadmin`: siempre `true`.
- `cliente`: siempre `false` para gestión.
- `admin` y `staff`: depende de `managedRestaurantIds`.

---

## Backend: matriz por recurso/API

### Autenticación

Fuente: `backend/app/Http/Controllers/AuthController.php`

| Endpoint | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| `POST /api/login` | ✅ | ✅ | ✅ | ✅ |
| `POST /api/register` | ✅* | ✅* | ✅* | ✅* |
| `POST /api/logout` | ✅ | ✅ | ✅ | ✅ |
| `GET /api/me` | ✅ | ✅ | ✅ | ✅ |

`*` Registro crea usuario con rol `cliente`.

Extra staff:

- `me/login` devuelve `restaurant_id` y `restaurant_name` para `staff`.

---

### Usuarios

Fuente: `backend/app/Http/Controllers/UserController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Listar usuarios (`GET /api/users`) | ✅ todos | ✅ solo su ámbito | ❌ | ❌ |
| Ver usuario (`GET /api/users/{id}`) | ✅ | ✅ si es suyo/creado por él | ❌ | ❌ |
| Crear admin (`POST /api/users` role=admin) | ✅ | ❌ | ❌ | ❌ |
| Crear staff (`POST /api/users` role=staff) | ✅ | ✅ | ❌ | ❌ |
| Crear cliente (`POST /api/users` role=cliente) | ✅ | ❌ | ❌ | ❌ |
| Editar usuario (`PATCH /api/users/{id}`) | ✅ | ✅ solo staff propio | ❌ | ❌ |
| Cambiar estado (`PATCH /api/users/{id}/status`) | ✅ | ✅ según ownership | ❌ | ❌ |
| Cambiar contraseña (`PATCH /api/users/{id}/password`) | ✅ | ❌ | ❌ | ❌ |
| Eliminar usuario (`DELETE /api/users/{id}`) | ✅ | ✅ solo staff propio | ❌ | ✅ solo su propia cuenta cliente |

Reglas clave:

- Admin no puede crear/editar/eliminar `cliente`.
- Admin no puede autogestionarse desde esa sección.
- Eliminar admin puede eliminar cuentas staff vinculadas creadas por ese admin.

---

### Restaurantes

Fuente: `backend/app/Http/Controllers/RestaurantController.php`

| Acción | superadmin | admin | staff | cliente/no auth |
|---|---:|---:|---:|---:|
| Listar (`GET /api/restaurants`) | ✅ todos | ✅ de su ámbito | ✅ de su ámbito | ✅ solo activos |
| Ver (`GET /api/restaurants/{id}`) | ✅ | ✅ si acceso | ✅ si acceso | ✅ solo activos |
| Crear (`POST /api/restaurants`) | ✅ | ✅ | ❌ | ❌ |
| Editar (`PUT /api/restaurants/{id}`) | ✅ | ✅ si vinculado/creador | ❌ | ❌ |
| Eliminar (`DELETE /api/restaurants/{id}`) | ✅ | ✅ si vinculado/creador | ❌ | ❌ |
| Sync admins (`PUT /api/restaurants/{id}/admins`) | ✅ | ✅ (solo puede asignarse a sí mismo) | ❌ | ❌ |
| Sync staffs (`PUT /api/restaurants/{id}/staffs`) | ✅ | ✅ con reglas de ownership | ❌ | ❌ |

Reglas clave en asignaciones:

- Cada restaurante debe tener exactamente 1 admin en `syncAdmins`.
- Superadmin solo puede asignar staff creados por el admin del restaurante.
- Admin solo puede asignar staff creados por ese admin.
- Un staff solo puede estar asignado a un restaurante.
- Se permite `staff_ids` vacío (restaurante con admin sin staff).

---

### Productos / Catálogos / Secciones

Fuente: `backend/app/Http/Controllers/ProductController.php`

| Acción | superadmin | admin | staff | cliente/no auth |
|---|---:|---:|---:|---:|
| Ver stats restaurantes (`GET /api/restaurants/stats`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ |
| Ver catálogos (`GET /api/restaurants/{id}/catalogs`) | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo restaurante activo |
| Crear/editar/eliminar catálogo y sección | ✅ | ✅ ámbito | ❌ | ❌ |
| Crear/editar/eliminar producto | ✅ | ✅ ámbito | ❌ | ❌ |
| Ocultar/mostrar producto (`active`) | ✅ | ✅ ámbito | ✅ ámbito (solo `active`) | ❌ |

---

### Categorías

Fuente: `backend/app/Http/Controllers/CategoryController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Listar (`GET /api/categories`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ (lista vacía) |
| Ver (`GET /api/categories/{id}`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ |
| Crear (`POST /api/categories`) | ✅ | ✅ ámbito | ❌ | ❌ |
| Editar (`PUT /api/categories/{id}`) | ✅ | ✅ ámbito | ❌ | ❌ |
| Eliminar (`DELETE /api/categories/{id}`) | ✅ | ✅ ámbito | ❌ | ❌ |

---

### Mesas

Fuente: `backend/app/Http/Controllers/TableController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Listar/ver (`GET /api/tables`, `GET /api/tables/{id}`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ |
| Crear/editar/eliminar (`POST/PUT/DELETE /api/tables...`) | ✅ | ✅ ámbito | ❌ | ❌ |

---

### Pedidos

Fuente: `backend/app/Http/Controllers/OrderController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Listar (`GET /api/orders`) | ✅ todos | ✅ ámbito | ✅ ámbito | ✅ solo propios |
| Ver (`GET /api/orders/{id}`) | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo propios |
| Crear (`POST /api/orders`) | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo en restaurante activo |
| Editar (`PUT/PATCH /api/orders/{id}`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ |
| Eliminar (`DELETE /api/orders/{id}`) | ✅ | ✅ ámbito | ❌ | ❌ |

---

### Ítems de pedido

Fuente: `backend/app/Http/Controllers/OrderItemController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Listar/ver (`GET /api/order-items...`) | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo propios |
| Crear (`POST /api/order-items` y anidado) | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo en pedido propio |
| Editar/eliminar (`PUT/DELETE /api/order-items/{id}`) | ✅ | ✅ ámbito | ❌ | ❌ |

---

### Pagos

Fuente: `backend/app/Http/Controllers/PaymentController.php`

| Acción | superadmin | admin | staff | cliente |
|---|---:|---:|---:|---:|
| Crear pago stripe/cash/test | ✅ | ✅ ámbito | ✅ ámbito | ✅ solo en pedido propio |
| Ver pago (`GET /api/payments/{id}`) | ✅ | ✅ ámbito | ✅ ámbito | ❌ |
| Webhook stripe (`POST /api/webhooks/stripe`) | N/A (público firmado) | N/A | N/A | N/A |

Notas:

- Cliente puede iniciar pagos sobre su pedido en endpoints de creación.
- Cliente no puede consultar `GET /api/payments/{id}` por autorización actual.

---

## Restricciones especiales de staff (actuales)

### 1) Contexto de restaurante en autenticación

- En login/me de staff se devuelve `restaurant_id` y `restaurant_name`.
- Fuente: `backend/app/Http/Controllers/AuthController.php`.

### 2) Navegación y acceso caja

- Login staff redirige con contexto de restaurante (cuando está disponible).
- Caja valida que `:restaurantId` coincida con el staff autenticado.
- Router corrige automáticamente `/staff`, `/caja/:id` y `/cocina/:id` al `restaurantId` asignado al staff.
- Fuentes:
  - `frontend/src/router/index.js`
  - `frontend/src/views/Login.vue`
  - `frontend/src/stores/auth.js`
  - `frontend/src/views/caja/Payments.vue`
  - `frontend/src/views/cocina/Orders.vue`

---

## Validación E2E del flujo pedido → cocina → caja → informes

### Objetivo

Validar que el flujo operativo registra datos consistentes y trazables para informes:

1. Cocina cambia estados de pedido (`pending` → `processing` → `completed`).
2. Caja registra cobro inmediato (`/payments/cash` con `immediate: true`) y consolida estado `paid`.
3. Informes consumen únicamente pedidos cobrados con evidencia de pago.

### Tests E2E implementados

- `frontend/tests/e2e/order-flow-cocina-caja-reports.spec.js`
  - **Test 1:** `flujo de pedido en cocina y caja registra eventos clave`
    - Verifica transiciones de estado en cocina.
    - Verifica registro de pago en caja con payload esperado (`amount`, `immediate: true`).
    - Verifica persistencia lógica final: pedido `paid` + pago `succeeded` + `paid_at`.
  - **Test 2:** `informes admin usan solo pedidos cobrados con evidencia de pago`
    - Verifica que KPI de informes excluye pedidos no cobrados/cancelados.
    - Verifica ventas totales basadas en pedidos cobrados.

- `frontend/tests/e2e/caja-cocina-no-reappear.spec.js`
  - Verifica que un pedido cobrado no reaparece como pendiente en caja.

- `frontend/tests/e2e/order-flow-real-backend.spec.js`
  - Ejecuta el flujo completo contra backend real (sin mocks):
    - Provisiona admin/staff/restaurante/producto/pedido vía API real.
    - Ejecuta cocina y caja desde UI real.
    - Valida persistencia real de `status=paid` y pago `succeeded`.
    - Verifica aparición del restaurante en informes con `Pedidos: 1` y `Ventas: $30.00`.

### Precondiciones para E2E real

- Servicios docker levantados: `docker-compose up -d` (expone `http://localhost:8080`).
- Frontend dev usa proxy `/api` hacia backend real (`VITE_API_PROXY_TARGET`, por defecto `http://localhost:8080`).
- Seeder base con superadmin disponible (`superadmin@scan2order.local`).

Comando recomendado:

- `cd frontend && npm run test:e2e -- tests/e2e/order-flow-real-backend.spec.js`

### Criterio de veracidad aplicado en informes

En `frontend/src/views/admin/Reports.vue`:

- Se contabilizan para ventas solo pedidos **cobrados** (`isOrderCollected`).
- La fecha de cómputo se toma de referencia de cobro (`paid_at` / referencia de colección).

Esto asegura que los reportes financieros se basen en evidencia de cobro, no solo en estado operativo del pedido.

---

## Archivos de referencia

- `backend/routes/api.php`
- `backend/app/Http/Controllers/Controller.php`
- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Controllers/UserController.php`
- `backend/app/Http/Controllers/RestaurantController.php`
- `backend/app/Http/Controllers/ProductController.php`
- `backend/app/Http/Controllers/CategoryController.php`
- `backend/app/Http/Controllers/TableController.php`
- `backend/app/Http/Controllers/OrderController.php`
- `backend/app/Http/Controllers/OrderItemController.php`
- `backend/app/Http/Controllers/PaymentController.php`
- `frontend/src/router/index.js`
- `frontend/src/views/Login.vue`
- `frontend/src/views/caja/Payments.vue`
- `frontend/src/views/cocina/Orders.vue`
- `frontend/src/views/admin/Reports.vue`
- `frontend/src/stores/auth.js`
- `backend/database/seeders/RolePermissionSeeder.php`
- `frontend/tests/e2e/order-flow-cocina-caja-reports.spec.js`
- `frontend/tests/e2e/caja-cocina-no-reappear.spec.js`
- `frontend/tests/e2e/order-flow-real-backend.spec.js`
