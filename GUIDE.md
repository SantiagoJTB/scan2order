# Guía de Funcionamiento - Scan2Order

Sistema de gestión de pedidos de restaurantes con autenticación multi-rol y carrito de compra.

## Tabla de Contenidos

1. [Roles y Permisos](#roles-y-permisos)
2. [Acceso Inicial](#acceso-inicial)
3. [Flujo por Rol](#flujo-por-rol)
4. [Operaciones Comunes](#operaciones-comunes)
5. [Datos de Prueba](#datos-de-prueba)

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

## Operaciones Comunes

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
