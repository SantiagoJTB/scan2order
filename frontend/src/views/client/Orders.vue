<template>
  <div class="orders-page">
    <!-- Header -->
    <div class="orders-header">
      <div class="header-content">
        <button @click="$router.back()" class="btn-back">
          <span class="back-icon">←</span>
          <span>Volver</span>
        </button>
        <h1 class="page-title">📦 Mis Pedidos</h1>
        <div class="header-spacer"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Cargando pedidos...</p>
      </div>

      <div v-else-if="error" class="error-box">
        <p>{{ error }}</p>
        <button @click="fetchOrders" class="btn-retry">Intentar de nuevo</button>
      </div>

      <div v-else-if="orders.length === 0" class="no-orders">
        <div class="empty-icon">📦</div>
        <h2>No tienes pedidos aún</h2>
        <p>¡Haz tu primer pedido ahora!</p>
        <router-link to="/restaurants" class="btn-shop">
          <span>Explorar restaurantes</span>
          <span class="btn-arrow">→</span>
        </router-link>
      </div>

      <div v-else class="orders-list">
        <div v-for="order in orders" :key="order.id" class="order-card">
          <div class="order-header">
            <div class="order-info">
              <h3>Pedido #{{ order.id }}</h3>
              <p class="restaurant">🍽️ {{ order.restaurant?.name || `Restaurante #${order.restaurant_id}` }}</p>
            </div>
            <div class="order-status">
              <span :class="['status-badge', `status-${order.status}`]">
                {{ formatStatus(order.status) }}
              </span>
            </div>
          </div>

          <div class="order-details">
            <div class="detail-row">
              <span class="label">Fecha:</span>
              <span class="value">{{ formatDate(order.created_at) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Tipo:</span>
              <span class="value">{{ formatOrderType(order.type) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Total:</span>
              <span class="value total">${{ Number(order.total || 0).toFixed(2) }}</span>
            </div>
          </div>

          <div v-if="order.order_items?.length" class="order-items">
            <div class="items-title">Productos:</div>
            <ul class="items-list">
              <li v-for="item in order.order_items" :key="item.id">
                {{ item.quantity }}x {{ item.product?.name || `Producto #${item.product_id}` }}
              </li>
            </ul>
          </div>

          <div v-if="order.notes" class="order-notes">
            <strong>Notas:</strong> {{ order.notes }}
          </div>

          <div class="order-actions">
            <router-link 
              :to="`/restaurant/${order.restaurant_id}`" 
              class="btn-reorder"
            >
              Pedir de nuevo
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const orders = ref([])
const isLoading = ref(false)
const error = ref('')

onMounted(() => {
  fetchOrders()
})

async function fetchOrders() {
  isLoading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/orders', {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar los pedidos')

    const data = await response.json()
    orders.value = Array.isArray(data) ? data.reverse() : []
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatStatus(status) {
  const statusMap = {
    pending: 'Pendiente',
    paid: 'Pagada',
    processing: 'En proceso',
    completed: 'Completada',
    cancelled: 'Cancelada'
  }
  return statusMap[status] || status
}

function formatOrderType(type) {
  const typeMap = {
    local: 'Consumir en local',
    delivery: 'Para llevar'
  }
  return typeMap[type] || type
}
</script>

<style scoped>
.orders-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding-bottom: 2rem;
}

/* Header */
.orders-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.btn-back {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 0.7rem 1.2rem;
  border-radius: 50px;
  cursor: pointer;
  font-weight: 700;
  font-size: 1rem;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateX(-3px);
}

.back-icon {
  font-size: 1.2rem;
}

.page-title {
  color: white;
  margin: 0;
  font-size: clamp(1.5rem, 4vw, 2rem);
  text-align: center;
  flex: 1;
}

.header-spacer {
  width: 7rem;
}

/* Content */
.content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

/* Loading & Empty States */
.loading,
.no-orders {
  background: white;
  border-radius: 20px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  margin: 3rem auto;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #ecf0f1;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading p {
  font-size: 1.1rem;
  color: #7f8c8d;
  margin: 0;
}

.empty-icon {
  font-size: 6rem;
  margin-bottom: 1.5rem;
  opacity: 0.3;
}

.no-orders h2 {
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-size: 1.8rem;
}

.no-orders p {
  color: #7f8c8d;
  margin: 0 0 2rem;
  font-size: 1.1rem;
}

.btn-shop {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  border: none;
  cursor: pointer;
}

.btn-shop:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.btn-arrow {
  transition: transform 0.3s ease;
}

.btn-shop:hover .btn-arrow {
  transform: translateX(3px);
}

.error-box {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  margin: 3rem auto;
}

.error-box p {
  color: #e74c3c;
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
}

.btn-retry {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  border: none;
  padding: 0.8rem 1.8rem;
  border-radius: 50px;
  cursor: pointer;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.btn-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(231, 76, 60, 0.4);
}

/* Orders List */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.order-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.order-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #f0f0f0;
}

.order-info h3 {
  font-size: 1.4rem;
  color: #2c3e50;
  margin: 0 0 0.5rem 0;
  font-weight: 700;
}

.restaurant {
  color: #7f8c8d;
  font-size: 1rem;
  margin: 0;
}

.status-badge {
  display: inline-block;
  padding: 0.6rem 1.2rem;
  border-radius: 50px;
  font-size: 0.9rem;
  font-weight: 700;
}

.status-pending {
  background: linear-gradient(135deg, #fef5e7 0%, #fdebd0 100%);
  color: #d68910;
  box-shadow: 0 2px 10px rgba(214, 137, 16, 0.2);
}

.status-paid {
  background: linear-gradient(135deg, #d5f4e6 0%, #b8e6d0 100%);
  color: #27ae60;
  box-shadow: 0 2px 10px rgba(39, 174, 96, 0.2);
}

.status-processing {
  background: linear-gradient(135deg, #d6eaf8 0%, #aed6f1 100%);
  color: #1f618d;
  box-shadow: 0 2px 10px rgba(31, 97, 141, 0.2);
}

.status-completed {
  background: linear-gradient(135deg, #d5f4e6 0%, #b8e6d0 100%);
  color: #27ae60;
  box-shadow: 0 2px 10px rgba(39, 174, 96, 0.2);
}

.status-cancelled {
  background: linear-gradient(135deg, #fadbd8 0%, #f5b7b1 100%);
  color: #c0392b;
  box-shadow: 0 2px 10px rgba(192, 57, 43, 0.2);
}

.order-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.label {
  color: #7f8c8d;
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.value {
  color: #2c3e50;
  font-size: 1rem;
  font-weight: 600;
}

.total {
  font-size: 1.4rem;
  font-weight: 700;
  color: #27ae60;
}

.order-items {
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
}

.items-title {
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.items-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.items-list li {
  padding: 0.5rem 0;
  color: #555;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.items-list li::before {
  content: "•";
  color: #667eea;
  font-weight: bold;
  font-size: 1.5rem;
}

.order-notes {
  margin-bottom: 1.5rem;
  padding: 1.2rem;
  background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
  border-left: 4px solid #f0ad4e;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #856404;
}

.order-notes strong {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 1rem;
}

.order-actions {
  display: flex;
  gap: 1rem;
}

.btn-reorder {
  flex: 1;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 50px;
  cursor: pointer;
  font-weight: 700;
  font-size: 1rem;
  text-decoration: none;
  text-align: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-reorder:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .btn-back {
    align-self: flex-start;
  }

  .header-spacer {
    display: none;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .content {
    padding: 1.5rem 1rem;
  }

  .order-header {
    flex-direction: column;
    gap: 1rem;
  }

  .order-details {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .order-card {
    padding: 1.5rem;
  }

  .order-info h3 {
    font-size: 1.2rem;
  }
}

@media (max-width: 480px) {
  .empty-icon {
    font-size: 4rem;
  }

  .no-orders h2 {
    font-size: 1.5rem;
  }

  .no-orders p {
    font-size: 1rem;
  }

  .order-card {
    padding: 1.2rem;
  }
}
</style>
