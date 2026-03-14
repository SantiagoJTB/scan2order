import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Public views
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'

// Client views
import ClientMenu from '../views/client/Menu.vue'
import ClientCart from '../views/client/Cart.vue'
import ClientRestaurants from '../views/client/Restaurants.vue'
import ClientOrders from '../views/client/Orders.vue'
import ClientCheckout from '../views/client/Checkout.vue'
import ClientProfile from '../views/client/Profile.vue'
// Admin views
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminRestaurants from '../views/admin/Restaurants.vue'
import AdminProducts from '../views/admin/Products.vue'
import AdminUsers from '../views/admin/Users.vue'
import AdminReports from '../views/admin/Reports.vue'
import AdminSecurity from '../views/admin/Security.vue'
import RestaurantOperations from '../views/admin/RestaurantOperations.vue'

// Staff views
import StaffDashboard from '../views/staff/Dashboard.vue'

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
    path: '/restaurants',
    name: 'ClientRestaurants',
    component: ClientRestaurants,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  {
    path: '/cart',
    name: 'Cart',
    component: ClientCart,
    meta: { requiresAuth: true, roles: ['cliente'] }
  },
  {
    path: '/orders',
    name: 'ClientOrders',
    component: ClientOrders,
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
    path: '/admin/restaurants',
    name: 'AdminRestaurants',
    component: AdminRestaurants,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: AdminUsers,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/products',
    name: 'AdminProducts',
    component: AdminProducts,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin', 'staff'] }
  },
  {
    path: '/admin/reports',
    name: 'AdminReports',
    component: AdminReports,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin'] }
  },
  {
    path: '/admin/security',
    name: 'AdminSecurity',
    component: AdminSecurity,
    meta: { requiresAuth: true, roles: ['superadmin'] }
  },
  {
    path: '/admin/restaurants/:restaurantId/operations',
    name: 'RestaurantOperations',
    component: RestaurantOperations,
    meta: { requiresAuth: true, roles: ['admin', 'superadmin', 'staff'] }
  },

  // Staff routes
  {
    path: '/staff/:restaurantId?',
    name: 'StaffDashboard',
    component: StaffDashboard,
    meta: { requiresAuth: true, roles: ['staff'] }
  },
  {
    path: '/caja/:restaurantId?',
    name: 'CajaPayments',
    component: CajaPayments,
    meta: { requiresAuth: true, roles: ['staff', 'admin', 'superadmin'] }
  },
  {
    path: '/cocina/:restaurantId?',
    name: 'CocinaOrders',
    component: CocinaOrders,
    meta: { requiresAuth: true, roles: ['staff', 'admin', 'superadmin'] }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

function getDefaultRouteByRole(role, auth) {
  if (role === 'superadmin' || role === 'admin') return '/admin'
  if (role === 'staff') {
    if (auth?.staffRestaurantId) return `/staff/${auth.staffRestaurantId}`
    return '/staff'
  }
  if (role === 'cliente') return '/restaurants'
  return '/'
}

// Route guard
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  const missingRole = !auth.user?.role?.name
  if (auth.token && (!auth.user || missingRole)) {
    await auth.initFromStorage()
  }

  const isPublic = Boolean(to.meta?.public)
  const requiresAuth = Boolean(to.meta?.requiresAuth)
  const allowedRoles = Array.isArray(to.meta?.roles) ? to.meta.roles : []
  const userRole = auth.userRole
  const defaultRoute = getDefaultRouteByRole(userRole, auth)

  if (isPublic) {
    if (auth.isAuthenticated && to.name === 'Home' && userRole !== 'cliente') {
      return defaultRoute
    }

    if (auth.isAuthenticated && to.name === 'RestaurantMenu' && userRole !== 'cliente') {
      return defaultRoute
    }

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

    if (userRole === 'staff') {
      const assignedRestaurantId = auth.staffRestaurantId ? Number(auth.staffRestaurantId) : null
      const isStaffScopedRoute = ['StaffDashboard', 'CajaPayments', 'CocinaOrders'].includes(String(to.name || ''))

      if (isStaffScopedRoute && assignedRestaurantId) {
        const routeRestaurantId = to.params?.restaurantId ? Number(to.params.restaurantId) : null

        if (!routeRestaurantId || routeRestaurantId !== assignedRestaurantId) {
          return {
            name: to.name,
            params: {
              ...to.params,
              restaurantId: assignedRestaurantId,
            },
            query: to.query,
            hash: to.hash,
          }
        }
      }
    }
  }

  return true
})

export default router
