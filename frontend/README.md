# Scan2Order Frontend

Frontend desarrollado con Vue 3 y Vite para el sistema de gestión de pedidos.

## Características

- Interfaz responsiva y moderna
- Gestión de restaurantes
- Gestión de pedidos
- Gestión de productos
- Enrutamiento con Vue Router
- Cliente HTTP con Axios

## Estructura del Proyecto

```
src/
├── components/        # Componentes reutilizables
├── views/            # Vistas principales
├── services/         # Servicios de API
├── router/          # Configuración de rutas
├── App.vue          # Componente raíz
└── main.js          # Punto de entrada
```

## Instalación y Desarrollo

```bash
npm install
npm run dev       # Servidor de desarrollo
npm run build     # Construcción para producción
```

## Variables de Entorno

El frontend se comunica con el backend a través de rutas proxy en Nginx.

- URL base de API: `/api`
- Host: `0.0.0.0`
- Puerto de desarrollo: `5173`

## Tareas Futuras

- [ ] Autenticación de usuarios
- [ ] Dashboard con estadísticas
- [ ] Escaneo de códigos QR
- [ ] Impresión de pedidos
- [ ] Notificaciones en tiempo real
- [ ] Temas oscuro/claro
