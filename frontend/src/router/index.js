import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Public views
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'

// Client views
import ClientMenu from '../views/client/Menu.vue'
import ClientCart from '../views/client/Cart.vue'

import ClientCheckout from '../views/client/Checkout.vue'
import ClientProfile from '../views/client/Profile.vue'
// Admin views
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminUsers from '../views/admin/Users.vue'
import AdminRestaurants from '../views/admin/Restaurants.vue'
import AdminProducts from '../views/admin/Products.vue'
import AdminOrders from '../views/admin/Orders.vue'

// Caja views
import CajaPayments from '../views/caja/Payments.vue'

// Cocina views
import CocinaOrders from '../views/cocina/Orders.vue'

const routes = [
  // Public routes
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { public: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { public: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { public: true }
  },
  {
    path: '/restaurant/:id',
    name: 'RestaurantMenu',
    component: ClientMenu,
    meta: { public: true }
  },
  
  // Client routes
  {
    path: '/menu',
    name: 'Menu',
    component: ClientMenu,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  {
    path: '/cart',
    name: 'Cart',
    component: ClientCart,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  {
    path: '/checkout',
    name: 'Checkout',
    component: ClientCheckout,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ClientProfile,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  // Admin routes
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: AdminUsers,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/restaurants',
    name: 'AdminRestaurants',
    component: AdminRestaurants,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/products',
    name: 'AdminProducts',
    component: AdminProducts,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/orders',
    name: 'AdminOrders',
    component: AdminOrders,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },

  // Caja routes
  {
    path: '/caja',
    name: 'CajaPayments',
    component: CajaPayments,
    meta: { requiresAuth: true, roles: ['caja'] }
  },

  // Cocina routes
  {
    path: '/cocina',
    name: 'CocinaOrders',
    component: CocinaOrders,
    meta: { requiresAuth: true, roles: ['cocina'] }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

function getDefaultRouteByRole(role) {
  if (role === 'superadmin' || role === 'admin') return '/admin'
  if (role === 'caja') return '/caja'
  if (role === 'cocina') return '/cocina'
  if (role === 'cliente') return '/menu'
  return '/'
}

// Route guard
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!auth.user && auth.token) {
    auth.initFromStorage()
  }

  const isPublic = Boolean(to.meta?.public)
  const requiresAuth = Boolean(to.meta?.requiresAuth)
  const allowedRoles = Array.isArray(to.meta?.roles) ? to.meta.roles : []
  const userRole = auth.userRole
  const defaultRoute = getDefaultRouteByRole(userRole)

  if (isPublic) {
    if (auth.isAuthenticated && (to.name === 'Login' || to.name === 'Register')) {
      return defaultRoute
    }
    return true
  }

  if (requiresAuth) {
    if (!auth.isAuthenticated) {
      return {
        path: '/login',
        query: { redirect: to.fullPath }
      }
    }

    if (allowedRoles.length > 0 && !allowedRoles.includes(userRole)) {
      return defaultRoute
    }
  }

  return true
})

export default router
