<template>
  <div id="app" class="app-container">
    <nav v-if="auth.isAuthenticated" :class="['navbar', { 'navbar--restaurant-focus': isRestaurantFocusView }]">
      <div class="nav-brand">
        <router-link v-if="!isRestaurantFocusView" to="/" class="brand-link">
          <h1>Scan2Order</h1>
        </router-link>
        <span v-else class="brand-secondary">{{ restaurantBrandName }}</span>
        <span class="role-badge">{{ auth.userRole }}</span>
      </div>
      <ul class="nav-links">
        <li v-if="isCliente"><router-link to="/restaurants">Restaurantes</router-link></li>
        <li v-if="isCliente"><router-link to="/cart">
          Carrito <span class="cart-count" v-if="cart.itemCount > 0">({{ cart.itemCount }})</span>
        </router-link></li>
        <li v-if="isCliente"><router-link to="/orders">Mis Pedidos</router-link></li>
        <li v-if="isStaff"><router-link :to="staffDashboardRoute">Panel Staff</router-link></li>
        
        <li v-if="canAccessAdmin"><router-link to="/admin">Panel Admin</router-link></li>
        <li v-if="isSuperadmin"><router-link to="/admin/security">Seguridad</router-link></li>
        
        <li class="user-menu">
          <button @click="showUserMenu = !showUserMenu" class="user-btn">
            {{ auth.user?.name }} ▼
          </button>
          <div v-if="showUserMenu" class="dropdown-menu">
            <router-link v-if="isCliente" to="/profile" class="dropdown-item">Ver perfil</router-link>
            <button @click="logout" class="logout-btn">Cerrar sesión</button>
          </div>
        </li>
      </ul>
    </nav>
    
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const route = useRoute()
const showUserMenu = ref(false)
const restaurantBrandName = ref('Restaurante')
const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
const isSuperadmin = computed(() => auth.hasRole('superadmin'))
const isCliente = computed(() => auth.hasRole('cliente'))
const isStaff = computed(() => auth.hasRole('staff'))
const staffDashboardRoute = computed(() => {
  if (auth.staffRestaurantId) return `/staff/${auth.staffRestaurantId}`
  return '/staff'
})
const isRestaurantFocusView = computed(() => route.name === 'RestaurantMenu')

async function fetchRestaurantBrandName(restaurantId) {
  if (!restaurantId) {
    restaurantBrandName.value = 'Restaurante'
    return
  }

  try {
    const response = await fetch(`/api/restaurants/${restaurantId}`, {
      headers: {
        Accept: 'application/json'
      }
    })

    if (!response.ok) {
      restaurantBrandName.value = 'Restaurante'
      return
    }

    const data = await response.json()
    restaurantBrandName.value = data?.name || 'Restaurante'
  } catch {
    restaurantBrandName.value = 'Restaurante'
  }
}

onMounted(async () => {
  await auth.initFromStorage()
})

watch(() => router.currentRoute.value.name, () => {
  showUserMenu.value = false
})

watch(
  () => [route.name, route.params.id],
  ([name, restaurantId]) => {
    if (name === 'RestaurantMenu') {
      fetchRestaurantBrandName(restaurantId)
      return
    }

    restaurantBrandName.value = 'Restaurante'
  },
  { immediate: true }
)

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>

<style scoped>
.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  background-color: #2c3e50;
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  flex-shrink: 0;
}

.navbar--restaurant-focus {
  background-color: rgba(44, 62, 80, 0.92);
  padding: 0.9rem 1.25rem;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-brand h1 {
  color: #fff;
  font-size: 1.8rem;
  font-weight: 700;
}

.brand-secondary {
  color: #d4dce4;
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.2px;
}

.brand-link {
  text-decoration: none;
}

.role-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.nav-links {
  display: flex;
  list-style: none;
  gap: 1.5rem;
  align-items: center;
}

.nav-links li {
  position: relative;
}

.nav-links a {
  color: #ecf0f1;
  text-decoration: none;
  font-size: 1rem;
  padding: 0.5rem 0;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
  font-weight: 500;
}

.nav-links a:hover {
  color: #667eea;
  border-bottom-color: #667eea;
}

.nav-links a.router-link-active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.cart-count {
  background: #e74c3c;
  color: white;
  padding: 0.1rem 0.4rem;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
}

.user-menu {
  position: relative;
  margin-left: 1rem;
}

.user-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.user-btn:hover {
  opacity: 0.8;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: rgba(44, 62, 80, 0.92);
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  margin-top: 0.5rem;
  min-width: 180px;
  z-index: 9999;
  overflow: hidden;
  border: 1px solid #d4dce4;
}

.logout-btn {
  width: 100%;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  cursor: pointer;
  color: #e74c3c;
  font-weight: 600;
  transition: background 0.3s ease;
}

.logout-btn:hover {
  background: #f5f5f5;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  text-align: center;
  cursor: pointer;
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  border-bottom: none;
  transition: background 0.3s ease;
}

.dropdown-item:hover {
  background: #ffffff;
  color: #764ba2;
  border-bottom: none;
}

.dropdown-item.router-link-active {
  color: #667eea;
  border-bottom: none;
}

.dropdown-item:first-child {
  border-bottom: 1px solid #ecf0f1;
}
.main-content {
  flex: 1;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow-y: auto;
}

@media (max-width: 768px) {
  .navbar {
    flex-direction: column;
    gap: 1rem;
  }

  .nav-links {
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    width: 100%;
  }

  .nav-brand h1 {
    font-size: 1.4rem;
  }
}
</style>
