# Usuarios de prueba (local/dev)

Este documento centraliza las credenciales para entrar al sistema en entorno local.

## Dónde está la info del superadmin

- Seeder origen: `backend/database/seeders/RolePermissionSeeder.php`
- Usuario: `superadmin@scan2order.local`
- Clave: `superadmin123`
- Nota: si MFA está activado para superadmin, el login pedirá código por correo.

## Usuarios de prueba para entrar

Estos usuarios se crean/actualizan al ejecutar:

```bash
php artisan db:seed --class=OperationalTestDataSeeder
```

| Rol | Email | Clave |
|---|---|---|
| Superadmin | `superadmin@scan2order.local` | `superadmin123` |
| Admin demo | `admin.demo@scan2order.local` | `admin123` |
| Staff demo (Caja/Cocina) | `staff.demo@scan2order.local` | `staff123` |
| Cliente demo | `cliente.demo@scan2order.local` | `cliente123` |

## Seed recomendado para entorno local

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=TestDataSeeder
php artisan db:seed --class=OperationalTestDataSeeder
```

Con eso tendrás:
- roles + permisos,
- restaurantes/productos de prueba,
- pedidos/pagos de prueba,
- usuarios listos para login.
