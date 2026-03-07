import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Public views
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'

// Client views
import ClientMenu from '../views/client/Menu.vue'
import ClientCart from '../views/client/Cart.vue'
import ClientCheckout from '../views/client/Checkout.vue'

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
  },

  // Redirect to home
  {
    path: '/',
    name: 'Home',
    redirect: (to) => {
      const auth = useAuthStore()
      if (!auth.isAuthenticated) return '/login'
      
      const role = auth.userRole
      if (role === 'cliente') return '/menu'
      if (role === 'admin' || role === 'superadmin') return '/admin'
      if (role === 'caja') return '/caja'
      if (role === 'cocina') return '/cocina'
      return '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Route guard
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  // Initialize auth from storage if needed
  if (!auth.user && auth.token) {
    auth.initFromStorage()
  }

  const isPublic = to.meta.public
  const requiresAuth = to.meta.requiresAuth
  const allowedRoles = to.meta.roles

  // If route is public, allow
  if (isPublic) {
    if (auth.isAuthenticated && (to.name === 'Login' || to.name === 'Register')) {
      // Redirect authenticated users away from auth pages
      return next('/')
    }
    return next()
  }

  // If route requires auth
  if (requiresAuth) {
    if (!auth.isAuthenticated) {
      return next('/login')
    }

    // Check roles
    if (allowedRoles && !allowedRoles.includes(auth.userRole)) {
      return next('/login')
    }
  }

  next()
})

export default router
