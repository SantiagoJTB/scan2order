# Guía de Funcionamiento - Scan2Order

Sistema de gestión de pedidos de restaurantes con autenticación multi-rol y carrito de compra.

## Tabla de Contenidos

1. [Roles y Permisos](#roles-y-permisos)
2. [Acceso Inicial](#acceso-inicial)
3. [Flujo por Rol](#flujo-por-rol)
4. [API Endpoints y Vistas](#api-endpoints-y-vistas)
5. [Operaciones Comunes](#operaciones-comunes)
6. [Datos de Prueba](#datos-de-prueba)

---

## Roles y Permisos

El sistema tiene **5 roles jerárquicos** con permisos específicos:

### 1. **Superadmin** 👑
- Acceso total al sistema
- Gestionar cuentas de admin
- Ver reportes de todo el negocio
- Permisos: Todos (crear restaurantes, gestionar usuarios, pagos, órdenes, etc.)
- **Acceso:** Panel de Admin completo

### 2. **Admin** 🏢
- Gestionar usuarios (caja y cocina)
- Ver órdenes del restaurante
- Acceso a reportes
- Ver y gestionar productos
- Permisos: Gestionar usuarios, productos, categorías, ver órdenes y reportes
- **Acceso:** Dashboard de Admin + Gestión de Usuarios

### 3. **Caja** 💳
- Procesar pagos
- Ver órdenes
- Manejar el registro de caja
- Permisos: Gestionar pagos, ver órdenes
- **Acceso:** Panel de Pagos

### 4. **Cocina** 👨‍🍳
- Gestionar y preparar órdenes
- Ver estado de órdenes
- Permisos: Gestionar órdenes, ver órdenes
- **Acceso:** Panel de Cocina

### 5. **Cliente** 🛒
- Navegar la carta de productos
- Agregar artículos al carrito
- Realizar compras
- Ver órdenes propias
- Permisos: Hacer pedidos, ver sus propias órdenes
- **Acceso:** Menú, Carrito y Checkout

---

## Acceso Inicial

### Entrada al Sistema

1. Abre el navegador y ve a: **http://localhost:8080**
2. Verás la pantalla de login

### Cuenta Superadmin (Pre-creada)

- **Email:** `superadmin@scan2order.local`
- **Clave:** `superadmin123`
- Usa esta cuenta para crear las primeras cuentas de admin

---

## Flujo por Rol

### 🔑 SUPERADMIN: Crear Administradores

**Objetivo:** Configurar la first time setup del sistema

1. **Inicia sesión** con la cuenta superadmin
2. **Ir a:** Panel de Admin → "Gestionar Usuarios"
3. **Hacer clic:** Botón "Crear Usuario"
4. **Formulario - Crear Admin:**
   - Nombre: (ej: `Juan García`)
   - Email: (ej: `admin@restaurant1.local`)
   - Clave: (elegir una segura)
   - Teléfono: (ej: `+34912345678`)
   - Rol: Seleccionar **Admin**
   - **Clic en "Crear"**

**Qué sucede automáticamente:**
- Se crea la cuenta de Admin
- Se crean automáticamente las cuentas de **Caja** y **Cocina** asociadas
- Los emails y claves serán combinaciones de la cuenta admin (ej: `admin@restaurant1_caja@local`)
- Se muestra un modal con todas las credenciales creadas

5. **Anotar las credenciales** (verás todas las tres cuentas generadas)

**Resultado:** Ahora tienes 3 usuarios listos: 1 Admin + 1 Caja + 1 Cocina

---

### 🏢 ADMIN: Gestionar el Restaurante

**Objetivo:** Supervisar operaciones y crear más personal (opcional)

1. **Inicia sesión** con cuenta admin
2. **Verás el Dashboard** con:
   - Cuadros de estadísticas (usuarios, restaurantes, productos, órdenes)
   - Botones de acción rápida
3. **Opciones disponibles:**
   - **Gestionar Usuarios:** Crear más cuentas de caja/cocina si las necesitas
   - **Cambiar estado de usuarios:** Activar/desactivar empleados

---

### 💳 CAJA: Procesar Pagos

**Objetivo:** Manejar la caja registradora y cobros

1. **Inicia sesión** con cuenta caja
2. **Ir a:** Panel de Caja (aparece automáticamente en el menú)
3. **Verás:**
   - Órdenes pendientes por pagar
   - Monto total en caja
   - Historial de transacciones (placeholder por ahora)

**Funcionalidad actual:** Panel informativo (listo para expansión futura)

---

### 👨‍🍳 COCINA: Preparar Órdenes

**Objetivo:** Gestionar la preparación de los pedidos

1. **Inicia sesión** con cuenta cocina
2. **Ir a:** Panel de Cocina (aparece en el menú)
3. **Verás:**
   - Órdenes nuevas por preparar
   - Órdenes en preparación
   - Órdenes completadas (placeholder por ahora)

**Funcionalidad actual:** Panel informativo (listo para expansión futura)

---

### 🛒 CLIENTE: Realizar Compra

**Objetivo:** Comprar productos del restaurante

#### Paso 1: Navegar el Menú

1. **Inicia sesión** o **Regístrate** como cliente
   - Para registrarse: Haz clic en "¿No tienes cuenta?" desde el login
   - Completa: Nombre, Email, Clave
   
2. **Verás la Carta:**
   - Lista de productos disponibles
   - Búsqueda por nombre o descripción
   - Precio de cada artículo

3. **Buscar productos:** Usa la barra de búsqueda (ej: "pizza", "bebida")

#### Paso 2: Agregar al Carrito

1. Haz **clic en "Agregar al Carrito"** en el producto deseado
2. Se agregará 1 unidad
3. **Verás un cartel flotante** en la esquina derecha mostrando el total

#### Paso 3: Revisar Carrito

1. Haz **clic en el icono del carrito** (esquina superior derecha)
2. Verás la tabla con todos los artículos:
   - Nombre del producto
   - Precio unitario
   - Cantidad (con botones ± para cambiar)
   - Subtotal por línea

#### Paso 4: Pagar

1. Haz **clic en "Ir al Checkout"**
2. **Formulario de Envío:**
   - Dirección: Tu dirección completa
   - Ciudad: Tu ciudad
   - Código Postal: Tu código postal

3. **Selecciona Método de Pago:**
   - **Tarjeta de Crédito:** Muestra formulario (tarjeta, expiración, CVV - simulado)
   - **Efectivo en Entrega:** No muestra formulario

4. **Resumen de Orden:**
   - Lista detallada de productos
   - Subtotal
   - IVA (10%)
   - Envío ($5.00)
   - **Total a pagar**

5. Haz **clic en "Realizar Pago"**

#### Paso 5: Confirmación

1. Se muestra un **modal de éxito** con:
   - ✅ Número de orden
   - Monto total pagado
   - Tiempo estimado de entrega (15-20 minutos)

2. Haz **clic en "Cerrar"** o la cruz
3. Se te redirige automáticamente al menú
4. **El carrito se vacía** automáticamente

**¡Compra completada!** ✅

---

## API Endpoints y Vistas

Documentación completa de todos los endpoints, views asociadas y permisos de acceso.

### 🔓 ENDPOINTS PÚBLICOS (Sin Autenticación)

#### Login
- **Endpoint:** `POST /api/login`
- **View/Componente:** `src/views/Login.vue`
- **Acceso:** Cualquiera (no autenticado)
- **Parámetros:**
  ```json
  {
    "email": "usuario@example.com",
    "password": "password123"
  }
  ```
- **Respuesta:** Token de acceso + Datos del usuario + Rol + Permisos
- **Uso:** Primera entrada al sistema

#### Registro
- **Endpoint:** `POST /api/register`
- **View/Componente:** `src/views/Register.vue`
- **Acceso:** Cualquiera (no autenticado)
- **Parámetros:**
  ```json
  {
    "name": "Juan Pérez",
    "email": "cliente@example.com",
    "password": "password123"
  }
  ```
- **Respuesta:** Usuario creado con rol **Cliente** automático
- **Uso:** Crear nuevas cuentas de cliente (auto-asigna rol cliente)

#### Obtener Productos
- **Endpoint:** `GET /api/products`
- **View/Componente:** `src/views/client/Menu.vue`
- **Acceso:** Todos (autenticados y no autenticados)
- **Parámetros:** Ninguno (opcional: filtros de búsqueda)
- **Respuesta:** Lista de productos con nombre, descripción, precio, imagen
- **Uso:** Mostrar carta de restaurante

#### Health Check
- **Endpoint:** `GET /api/hello`
- **Acceso:** Cualquiera (sin autenticación)
- **Respuesta:** `{"message": "Hello World"}`
- **Uso:** Verificar que el API está disponible

---

### 🔒 ENDPOINTS PROTEGIDOS (Requieren Token Bearer)

> **Nota:** Todo endpoint protegido requiere header:
> ```
> Authorization: Bearer {token}
> ```

#### Logout
- **Endpoint:** `POST /api/logout`
- **View/Componente:** `src/App.vue` (Menú de usuario)
- **Acceso:** ✅ Todos los roles autenticados
- **Autorización:** Cualquier usuario autenticado
- **Parámetros:** Ninguno
- **Respuesta:** `{"message": "Logged out successfully"}`
- **Uso:** Cerrar sesión y revocar token

#### Obtener Perfil Actual
- **Endpoint:** `GET /api/me`
- **View/Componente:** `src/App.vue`, Componentes privadas
- **Acceso:** ✅ Todos los roles autenticados
- **Autorización:** Solo el usuario autenticado
- **Parámetros:** Ninguno
- **Respuesta:** Usuario actual con rol, permisos y datos completos
- **Uso:** Mostrar información del usuario en el UI

---

### 🏢 ENDPOINTS DE GESTIÓN DE USUARIOS

#### Crear Usuario
- **Endpoint:** `POST /api/users`
- **View/Componente:** `src/views/admin/Users.vue` (Modal)
- **Acceso:** ❌ Cliente, Caja, Cocina
- **Acceso:** ✅ **Admin**, **Superadmin**
- **Autorización:**
  - **Admin:** Puede crear Admin, Caja, Cocina
  - **Superadmin:** Puede crear cualquier rol
- **Parámetros:**
  ```json
  {
    "name": "Juan García",
    "email": "admin@restaurant.local",
    "password": "segura123",
    "phone": "+34912345678",
    "role_id": 2
  }
  ```
- **Respuesta Especial (Admin crea Admin):** Se generan automáticamente 2 usuarios adicionales (Caja + Cocina)
- **Uso:** Crear nuevas cuentas de personal

#### Listar Usuarios
- **Endpoint:** `GET /api/users`
- **View/Componente:** `src/views/admin/Users.vue` (Tabla)
- **Acceso:** ❌ Cliente, Caja, Cocina
- **Acceso:** ✅ **Admin**, **Superadmin**
- **Autorización:**
  - **Admin:** Ve solo usuarios que creó
  - **Superadmin:** Ve todos los usuarios
- **Parámetros:** `page`, `limit` (paginación)
- **Respuesta:** Array de usuarios con rol, status, datos personales
- **Uso:** Mostrar lista de empleados

#### Ver Usuario
- **Endpoint:** `GET /api/users/{id}`
- **View/Componente:** `src/views/admin/Users.vue` (Detalles)
- **Acceso:** ❌ Cliente, Caja, Cocina
- **Acceso:** ✅ **Admin**, **Superadmin**
- **Autorización:**
  - **Admin:** Solo ve usuarios que creó
  - **Superadmin:** Ve cualquier usuario
  - Usuario puede verse a sí mismo
- **Parámetros:** `{id}` = ID del usuario
- **Respuesta:** Objeto usuario completo
- **Uso:** Ver detalles de un empleado específico

#### Cambiar Estado de Usuario
- **Endpoint:** `PATCH /api/users/{id}/status`
- **View/Componente:** `src/views/admin/Users.vue` (Botones de estado)
- **Acceso:** ❌ Cliente, Caja, Cocina
- **Acceso:** ✅ **Admin**, **Superadmin**
- **Autorización:**
  - **Admin:** Solo usuarios que creó
  - **Superadmin:** Cualquier usuario
- **Parámetros:**
  ```json
  {
    "status": "active" | "inactive" | "suspended"
  }
  ```
- **Respuesta:** Usuario actualizado con nuevo estado
- **Uso:** Activar/desactivar empleados

---

### 📊 RESUMEN DE ACCESO POR ROLE

| Endpoint | Cliente | Caja | Cocina | Admin | Superadmin |
|----------|---------|------|--------|-------|------------|
| POST /api/login | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /api/register | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /api/products | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /api/hello | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /api/logout | ✅ | ✅ | ✅ | ✅ | ✅ |
| GET /api/me | ✅ | ✅ | ✅ | ✅ | ✅ |
| POST /api/users | ❌ | ❌ | ❌ | ✅ | ✅ |
| GET /api/users | ❌ | ❌ | ❌ | ✅ | ✅ |
| GET /api/users/{id} | ❌ | ❌ | ❌ | ✅ | ✅ |
| PATCH /api/users/{id}/status | ❌ | ❌ | ❌ | ✅ | ✅ |

---

### 🎨 ARQUITECTURA DE VISTAS

```
src/
├── views/
│   ├── Login.vue                    # [PUBLIC] Formulario de login
│   ├── Register.vue                 # [PUBLIC] Formulario de registro
│   ├── client/
│   │   ├── Menu.vue                 # [CLIENTE] Mostrar productos
│   │   ├── Cart.vue                 # [CLIENTE] Revisar carrito
│   │   └── Checkout.vue             # [CLIENTE] Completar compra
│   ├── admin/
│   │   ├── Dashboard.vue            # [ADMIN + SUPERADMIN] Panel estadísticas
│   │   └── Users.vue                # [ADMIN + SUPERADMIN] Gestión usuarios
│   ├── caja/
│   │   └── Payments.vue             # [CAJA] Panel de pagos
│   └── cocina/
│       └── Orders.vue               # [COCINA] Panel de órdenes
├── stores/
│   ├── auth.js                      # Gestión de autenticación y token
│   └── cart.js                      # Gestión del carrito
├── router/
│   └── index.js                     # Enrutamiento y guards
└── App.vue                          # Componente raíz con navbar

```

---

### 🔐 FLUJO DE AUTENTICACIÓN

```
1. Usuario no autenticado
   ↓
2. Accede a http://localhost:8080
   ↓
3. Router guard verifica isAuthenticated
   ↓
4. Redirige a /login (sin token)
   ↓
5. Usuario completa formulario
   ↓
6. POST /api/login con email + password
   ↓
7. Backend valida credenciales
   ↓
8. De vuelta: token + user + role + permissions
   ↓
9. Frontend almacena en localStorage
   ↓
10. Auth store se actualiza
    ↓
11. Router detecta isAuthenticated = true
    ↓
12. Redirige a dashboard según rol:
    - Cliente → /menu
    - Admin → /admin
    - Caja → /caja
    - Cocina → /cocina
    - Superadmin → /admin
```

---

### 🛡️ ROUTE GUARDS

El router valida en cada navegación:

```javascript
// Validaciones en beforeEach:
1. ¿Está autenticado? → Si no, redirige a /login
2. ¿Tiene permiso para esta ruta? → Si no, redirige al dashboard de su rol
3. ¿La ruta es pública? → Permite acceso sin token
```

**Rutas protegidas por rol:**
- `/menu` → Solo Cliente
- `/cart` → Solo Cliente
- `/checkout` → Solo Cliente
- `/admin` → Admin + Superadmin
- `/admin/users` → Admin + Superadmin
- `/caja` → Solo Caja
- `/cocina` → Solo Cocina



### Cerrar Sesión

- Haz clic en tu **nombre/rol** en la esquina superior derecha
- Selecciona **"Cerrar Sesión"**
- Serás redirigido al login

### Cambiar de Rol

- Cierra sesión
- Inicia con otra cuenta
- El sistema adapta automáticamente la interfaz

### Volver Atrás

- Usa las opciones del menú
- O el botón "Volver" si está disponible
- Nunca pierdas tu carrito (se guarda localmente)

---

## Datos de Prueba

> Credenciales actualizadas y ubicación del superadmin: ver `docs/usuarios-prueba.md`

### Cuentas Incluidas

| Rol | Email | Clave | Propósito |
|-----|-------|-------|-----------|
| Superadmin | `superadmin@scan2order.local` | `superadmin123` | Setup inicial |
| Cliente Ejemplo | `cliente@test.local` | `cliente123` | Pruebas compra |

### Crear Cuentas de Prueba

1. **Admin de prueba:**
   - Inicia como superadmin
   - Crea usuario con rol Admin
   - Se generarán automáticamente Caja + Cocina

2. **Cliente de prueba:**
   - Ve a "Registrarse"
   - Crea cuenta con cualquier email/clave
   - Se asigna automáticamente el rol Cliente

3. **Productos de prueba:**
   - Están seeded en la base de datos
   - Visible en la vista de Menú para clientes

---

## Flujo Completo de Ejemplo

### Scenario: Primer día de operaciones

```
1. SETUP (10 min)
   ├─ Inicia sesión como superadmin
   ├─ Crea usuario "Admin" → se crean Caja + Cocina en cascada
   └─ Anotas las 3 credenciales

2. CLIENTE (5 min)
   ├─ Regístrate como nuevo cliente
   ├─ Busca "pizza" en el menú
   ├─ Agrega 2 pizzas al carrito
   ├─ Agrega 2 bebidas
   ├─ Va al checkout
   ├─ Completa dirección → elige método pago → paga
   └─ ✅ Orden #12345 confirmada

3. ADMIN (3 min)
   ├─ Inicia como admin
   ├─ Ve dashboard con estadísticas
   └─ Puede ver la orden del cliente si está implementado

4. CAJA (2 min)
   ├─ Inicia como caja
   ├─ Ve panel de pagos
   └─ Puede ver orden pendiente

5. COCINA (2 min)
   ├─ Inicia como cocina
   ├─ Ve panel de órdenes
   └─ Puede marcar como "preparado"
```

---

## Notas Importantes

### Persistencia de Datos

- ✅ **Autenticación:** Se guarda en localStorage → permanece tras refrescar
- ✅ **Carrito:** Se guarda localmente → NO se pierde al refrescar
- ⚠️ **Órdenes:** Actualmente se limpian al refrescar (simulado)
- ✅ **Base de datos:** Todos los usuarios y productos persisten en PostgreSQL

### Seguridad

- Todos los endpoints están protegidos con token Bearer (Sanctum)
- Cada rol tiene permisos específicos validados en el servidor
- Las rutas del frontend redirigen a login si no estás autenticado
- No se puede acceder a vistas sin los permisos requeridos

### Limitaciones Actuales

- Pagos son simulados (sin gateway real)
- Panel de Caja y Cocina son placeholders (sin CRUD completo)
- Órdenes no se persisten en la base de datos
- Sin notificaciones en tiempo real
- Sin imágenes de productos

### Próximas Mejoras

- [ ] Integración con Stripe/PayPal
- [ ] Persistencia de órdenes
- [ ] WebSockets para actualizaciones en tiempo real
- [ ] Carga de imágenes de productos
- [ ] Notificaciones por email
- [ ] Historial de órdenes del cliente
- [ ] Reportes avanzados para admin

---

## Soporte

Si encontras problemas:

1. **Verificar logs:** `docker-compose logs backend` o `docker-compose logs frontend`
2. **Reiniciar:** `docker-compose restart`
3. **Hacer reset completo:** `docker-compose down && docker-compose up --build -d`
4. **Verificar conexión API:** `curl -X POST http://localhost:8080/api/login -H "Content-Type: application/json" -d '{"email":"superadmin@scan2order.local","password":"superadmin123"}'`

---

**Última actualización:** 7 de marzo de 2026
**Versión:** 1.0
