<template>
  <div v-if="canAccessAdmin" class="admin-container">
    <div class="header">
      <h1 v-if="isSuperAdmin">Dashboard Superadmin</h1>
      <h1 v-else>Dashboard Admin</h1>
      <p v-if="isSuperAdmin">Gestión global del sistema</p>
      <p v-else>Gestión de tu restaurante</p>
    </div>

    <div v-if="isSuperAdmin" class="dashboard-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <p class="stat-label">Usuarios totales (clientes)</p>
          <p class="stat-value">{{ stats.users }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🍽️</div>
        <div class="stat-info">
          <p class="stat-label">Restaurantes</p>
          <p class="stat-value">{{ stats.restaurants }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">👨‍💼</div>
        <div class="stat-info">
          <p class="stat-label">Administradores (staffs)</p>
          <p class="stat-value">{{ stats.admins }}</p>
        </div>
      </div>
    </div>

    <div v-else class="dashboard-grid">
      <div class="stat-card">
        <div class="stat-icon">🍽️</div>
        <div class="stat-info">
          <p class="stat-label">Restaurantes</p>
          <p class="stat-value">{{ stats.restaurants }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
          <p class="stat-label">Productos</p>
          <p class="stat-value">{{ stats.products }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <p class="stat-label">Órdenes</p>
          <p class="stat-value">{{ stats.orders }}</p>
        </div>
      </div>
    </div>

    <div class="actions-section">
      <h2 v-if="isSuperAdmin">Gestión del sistema</h2>
      <h2 v-else>Acciones rápidas</h2>
      <div class="action-buttons">
        <router-link v-if="isSuperAdmin" to="/admin/users" class="action-btn primary">
          <span class="btn-icon">👥</span>
          Gestionar usuarios
        </router-link>
        <router-link to="/admin/restaurants" class="action-btn">
          <span class="btn-icon">🍽️</span>
          Gestionar locales
        </router-link>
        <router-link v-if="!isSuperAdmin" to="/admin/users" class="action-btn">
          <span class="btn-icon">👨‍💼</span>
          Gestionar staffs
        </router-link>
        <router-link v-if="canManageProducts" to="/admin/products" class="action-btn">
          <span class="btn-icon">📦</span>
          Gestionar productos
        </router-link>
        <router-link to="/admin/reports" class="action-btn">
          <span class="btn-icon">📊</span>
          Informes
        </router-link>
        <router-link v-if="isSuperAdmin" to="/admin/security" class="action-btn">
          <span class="btn-icon">🛡️</span>
          Seguridad
        </router-link>
      </div>
    </div>
  </div>

  <div v-else class="admin-container unauthorized-view">
    <div class="stat-card">
      <div class="stat-info">
        <p class="stat-label">Acceso denegado</p>
        <p class="stat-value">No autorizado</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
const isSuperAdmin = computed(() => auth.hasRole('superadmin'))
const canManageProducts = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
const stats = ref({
  users: 0,
  admins: 0,
  restaurants: 0,
  products: 0,
  orders: 0
})

function countByRole(users, roleName) {
  if (!Array.isArray(users)) return 0
  return users.filter((item) => item?.role?.name === roleName).length
}

async function fetchDashboardStats() {
  const headers = {
    Accept: 'application/json',
    ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {})
  }

  const [restaurantsResponse, usersResponse, restaurantsStatsResponse, ordersResponse] = await Promise.all([
    fetch('/api/restaurants', { headers }),
    fetch('/api/users', { headers }),
    fetch('/api/restaurants/stats', { headers }),
    fetch('/api/orders', { headers })
  ])

  const restaurantsData = restaurantsResponse.ok ? await restaurantsResponse.json() : []
  const usersData = usersResponse.ok ? await usersResponse.json() : []
  const restaurantsStatsData = restaurantsStatsResponse.ok ? await restaurantsStatsResponse.json() : []
  const ordersData = ordersResponse.ok ? await ordersResponse.json() : []

  const restaurants = Array.isArray(restaurantsData) ? restaurantsData : []
  const users = Array.isArray(usersData) ? usersData : []
  const restaurantsStats = Array.isArray(restaurantsStatsData) ? restaurantsStatsData : []
  const orders = Array.isArray(ordersData) ? ordersData : []

  const productsCount = restaurantsStats.reduce((sum, restaurant) => {
    return sum + Number(restaurant?.total_products || 0)
  }, 0)

  const ordersCount = orders.length

  if (isSuperAdmin.value) {
    stats.value = {
      users: countByRole(users, 'cliente'),
      admins: countByRole(users, 'staff'),
      restaurants: restaurants.length,
      products: productsCount,
      orders: ordersCount
    }
    return
  }

  stats.value = {
    users: 0,
    admins: 0,
    restaurants: restaurants.length,
    products: productsCount,
    orders: ordersCount
  }
}

onMounted(async () => {
  if (!auth.initialized) {
    await auth.initFromStorage()
  }

  if (!canAccessAdmin.value) return

  try {
    await fetchDashboardStats()
  } catch (err) {
    console.error('No se pudieron cargar estadísticas del dashboard:', err)
  }
})
</script>

<style scoped>
.admin-container {
  max-width: 1200px;
  margin: 0 auto;
}

.unauthorized-view {
  margin-top: 2rem;
}

.header {
  text-align: center;
  color: white;
  margin-bottom: 3rem;
}

.header h1 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.header p {
  font-size: 1.1rem;
  opacity: 0.9;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-info {
  flex: 1;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  color: #2c3e50;
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
}

.actions-section {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.actions-section h2 {
  color: #2c3e50;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  transition: transform 0.3s ease, opacity 0.3s ease;
  cursor: pointer;
  border: none;
  font-size: 1rem;
  font-weight: 600;
}

.action-btn:hover {
  transform: translateY(-3px);
  opacity: 0.9;
}

.action-btn.primary {
  background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
  border: 2px solid #8e44ad;
  box-shadow: 0 4px 12px rgba(155, 89, 182, 0.3);
}

.action-btn.primary:hover {
  box-shadow: 0 6px 16px rgba(155, 89, 182, 0.4);
}

.btn-icon {
  font-size: 2rem;
}
</style>
