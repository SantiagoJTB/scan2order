<template>
  <div class="staff-container">
    <div class="header">
      <h1>Panel de {{ staffName }}</h1>
      <p>{{ staffEmail }}</p>
      <div v-if="lastUpdated" class="last-update-info">
        Última actualización: {{ lastUpdated }}
      </div>
    </div>

    <div class="staff-summary">
      <div class="summary-item">
        <span class="summary-label">Rol</span>
        <span class="summary-value">{{ (auth.userRole || 'staff').toUpperCase() }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Restaurante asignado</span>
        <span class="summary-value">{{ staffRestaurantName }}</span>
      </div>
    </div>

    <div v-if="restaurant" class="restaurant-info">
      <h2>🍽️ {{ restaurant.name }}</h2>
      <p v-if="restaurant.address">📍 {{ restaurant.address }}</p>
    </div>

    <div v-else class="restaurant-warning">
      No tienes un restaurante asignado. Contacta con un administrador.
    </div>

    <div class="dashboard-grid">

      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <p class="stat-label">Pendientes de pago</p>
          <p class="stat-value">{{ stats.pendingPayments }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
          <p class="stat-label">Órdenes activas</p>
          <p class="stat-value">{{ stats.activeOrders }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">👨‍🍳</div>
        <div class="stat-info">
          <p class="stat-label">En cocina</p>
          <p class="stat-value">{{ stats.inKitchen }}</p>
        </div>
      </div>
    </div>

    <div class="actions-section">
      <h2>Acceso rápido</h2>
      <div class="action-buttons">
        <router-link :to="cajaRoute" class="action-btn caja">
          <span class="btn-icon">💰</span>
          <div class="btn-content">
            <span class="btn-title">Caja</span>
            <span class="btn-subtitle">Gestionar pagos</span>
          </div>
        </router-link>

        <router-link :to="cocinaRoute" class="action-btn cocina">
          <span class="btn-icon">👨‍🍳</span>
          <div class="btn-content">
            <span class="btn-title">Cocina</span>
            <span class="btn-subtitle">Órdenes a preparar</span>
          </div>
        </router-link>

        <router-link to="/admin/products" class="action-btn products">
          <span class="btn-icon">📦</span>
          <div class="btn-content">
            <span class="btn-title">Productos</span>
            <span class="btn-subtitle">Gestionar menú</span>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRoute } from 'vue-router'

const auth = useAuthStore()
const route = useRoute()
const restaurant = ref(null)
const orders = ref([])
const refreshIntervalId = ref(null)
const lastUpdated = ref('')
const stats = ref({
  activeOrders: 0,
  pendingPayments: 0,
  inKitchen: 0
})

const staffName = computed(() => auth.user?.name || 'Staff')
const staffEmail = computed(() => auth.user?.email || 'Sin email')
const staffRestaurantName = computed(() => restaurant.value?.name || auth.user?.restaurant_name || 'Sin asignar')
const routeRestaurantId = computed(() => route.params.restaurantId ? parseInt(route.params.restaurantId, 10) : null)
const restaurantId = computed(() => routeRestaurantId.value || auth.staffRestaurantId || restaurant.value?.id || null)
const cajaRoute = computed(() => restaurantId.value ? `/caja/${restaurantId.value}` : '/caja')
const cocinaRoute = computed(() => restaurantId.value ? `/cocina/${restaurantId.value}` : '/cocina')

async function fetchStaffRestaurant() {
  try {
    if (auth.staffRestaurantId) {
      const response = await fetch(`/api/restaurants/${auth.staffRestaurantId}`, {
        headers: {
          Authorization: `Bearer ${auth.token}`,
          Accept: 'application/json'
        }
      })

      if (!response.ok) throw new Error('Error al cargar restaurante asignado')

      restaurant.value = await response.json()
      return
    }

    const response = await fetch('/api/restaurants', {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al cargar restaurante')

    const data = await response.json()
    const restaurants = Array.isArray(data) ? data : []
    
    // Staff normalmente está asignado a un restaurante
    // Por ahora tomamos el primero disponible
    restaurant.value = restaurants[0] || null
  } catch (err) {
    console.error('Error fetching restaurant:', err)
  }
}

async function fetchOrders() {
  try {
    const response = await fetch('/api/orders', {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al cargar órdenes')

    const data = await response.json()
    orders.value = Array.isArray(data) ? data : []

    // Filtrar por restaurante si está definido
    let filtered = orders.value
    if (restaurantId.value) {
      filtered = filtered.filter(o => o.restaurant_id === restaurantId.value)
    }

    // Calcular estadísticas
    stats.value.activeOrders = filtered.filter(o => 
      !['completed', 'cancelled'].includes(o.status)
    ).length

    stats.value.pendingPayments = filtered.filter(o => {
      // Excluir solo cancelados
      if (o.status === 'cancelled') return false
      
      const payments = o.payments || []
      
      // Si no tiene pagos, está pendiente
      if (payments.length === 0) return true
      
      // Si tiene pagos, verificar si alguno está pendiente
      const hasPendingPayment = payments.some(p => p.status === 'pending')
      
      // Si todos los pagos están exitosos, no está pendiente
      const allPaymentsSucceeded = payments.length > 0 && 
        payments.every(p => p.status === 'succeeded')
      
      return hasPendingPayment || !allPaymentsSucceeded
    }).length

    stats.value.inKitchen = filtered.filter(o => 
      ['pending', 'paid', 'processing'].includes(o.status)
    ).length

    // Actualizar timestamp
    const now = new Date()
    lastUpdated.value = now.toLocaleTimeString('es-ES', { 
      hour: '2-digit', 
      minute: '2-digit', 
      second: '2-digit' 
    })

  } catch (err) {
    console.error('Error fetching orders:', err)
  }
}

function startAutoRefresh() {
  // Actualizar cada 10 segundos
  refreshIntervalId.value = setInterval(() => {
    fetchOrders()
  }, 10000)
}

function stopAutoRefresh() {
  if (refreshIntervalId.value) {
    clearInterval(refreshIntervalId.value)
    refreshIntervalId.value = null
  }
}

onMounted(async () => {
  await fetchStaffRestaurant()
  await fetchOrders()
  startAutoRefresh()
})

onBeforeUnmount(() => {
  stopAutoRefresh()
})
</script>

<style scoped>
.staff-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.header h1 {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
  color: #2c3e50;
  font-weight: 700;
}

.header p {
  font-size: 1.1rem;
  color: #7f8c8d;
}

.last-update-info {
  text-align: center;
  margin-top: 1rem;
  font-size: 0.9rem;
  color: #7f8c8d;
  font-style: italic;
}

.staff-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.summary-item {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-label {
  font-size: 0.85rem;
  color: #7f8c8d;
}

.summary-value {
  font-size: 1rem;
  font-weight: 700;
  color: #2c3e50;
}

.restaurant-info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 2rem;
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
}

.restaurant-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.restaurant-info p {
  margin: 0;
  font-size: 1rem;
  opacity: 0.9;
}

.restaurant-warning {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeeba;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 2rem;
  text-align: center;
  font-weight: 600;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.stat-icon {
  font-size: 3rem;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 0.9rem;
  color: #7f8c8d;
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
}

.actions-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.actions-section h2 {
  color: #2c3e50;
  margin: 0 0 1.5rem 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 2rem;
  border-radius: 16px;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.action-btn.caja {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  color: white;
}

.action-btn.cocina {
  background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
  color: white;
}

.action-btn.products {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.action-btn:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.btn-icon {
  font-size: 3rem;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.btn-content {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.btn-title {
  font-size: 1.4rem;
  font-weight: 700;
}

.btn-subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
}

@media (max-width: 768px) {
  .staff-container {
    padding: 1rem;
  }

  .header {
    padding: 1.5rem;
  }

  .header h1 {
    font-size: 2rem;
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    grid-template-columns: 1fr;
  }

  .stat-value {
    font-size: 2rem;
  }
}
</style>
