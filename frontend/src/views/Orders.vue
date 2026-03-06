<template>
  <div class="orders-container">
    <div class="page-header">
      <h2>Pedidos</h2>
      <div class="filters">
        <select v-model="selectedStatus" class="filter-select">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="completed">Completado</option>
          <option value="cancelled">Cancelado</option>
        </select>
      </div>
    </div>

    <div v-if="ordersStore.loading" class="loading">Cargando pedidos...</div>

    <div class="orders-list">
      <div v-for="order in filteredOrders" :key="order.id" class="order-card">
        <div class="order-header">
          <h3>Pedido #{{ order.id }}</h3>
          <span :class="['status', 'status-' + order.status]">{{ getStatusLabel(order.status) }}</span>
        </div>
        <div class="order-details">
          <p><strong>Total:</strong> ${{ order.total || 0 }}</p>
          <p><strong>Fecha:</strong> {{ formatDate(order.created_at) }}</p>
        </div>
        <div class="order-actions">
          <button @click="updateOrderStatus(order.id, 'completed')" class="btn btn-small btn-success">Completar</button>
          <button @click="deleteOrder(order.id)" class="btn btn-small btn-danger">Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="ordersStore.orders.length === 0 && !ordersStore.loading" class="empty-state">
      <p>No hay pedidos registrados</p>
    </div>
  </div>
</template>

<script setup>
import { useOrdersStore } from '../stores/orders'
import { ref, computed, onMounted } from 'vue'

const ordersStore = useOrdersStore()
const selectedStatus = ref('')

const filteredOrders = computed(() => {
  if (!selectedStatus.value) return ordersStore.orders
  return ordersStore.orders.filter(order => order.status === selectedStatus.value)
})

const getStatusLabel = (status) => {
  const labels = {
    'pending': 'Pendiente',
    'completed': 'Completado',
    'cancelled': 'Cancelado'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const updateOrderStatus = async (id, status) => {
  const success = await ordersStore.updateOrderStatus(id, status)
  if (success) {
    alert('Pedido actualizado')
  } else {
    alert('Error al actualizar: ' + ordersStore.error)
  }
}

const deleteOrder = async (id) => {
  if (confirm('¿Eliminar este pedido?')) {
    const success = await ordersStore.deleteOrder(id)
    if (success) {
      alert('Pedido eliminado')
    } else {
      alert('Error: ' + ordersStore.error)
    }
  }
}

onMounted(() => {
  ordersStore.fetchOrders()
})
</script>

<style scoped>
.orders-container {
  animation: fadeIn 0.5s ease-in;
  padding: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  color: white;
}

.page-header h2 {
  font-size: 2rem;
}

.filters {
  display: flex;
  gap: 1rem;
}

.filter-select {
  padding: 0.5rem;
  border-radius: 4px;
  border: none;
  background: white;
  cursor: pointer;
}

.loading {
  text-align: center;
  color: white;
  padding: 2rem;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.order-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #eee;
}

.order-header h3 {
  color: #667eea;
  margin: 0;
}

.status {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: bold;
}

.status-pending {
  background: #fbd38d;
  color: #975a16;
}

.status-completed {
  background: #9ae6b4;
  color: #22543d;
}

.status-cancelled {
  background: #fc8181;
  color: #742a2a;
}

.order-details {
  margin-bottom: 1rem;
  color: #666;
}

.order-details p {
  margin: 0.5rem 0;
}

.order-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s;
}

.btn-small {
  padding: 0.4rem 0.8rem;
}

.btn-success {
  background-color: #48bb78;
  color: white;
}

.btn-success:hover {
  background-color: #38a169;
}

.btn-danger {
  background-color: #f56565;
  color: white;
}

.btn-danger:hover {
  background-color: #e53e3e;
}

.empty-state {
  text-align: center;
  color: white;
  padding: 3rem;
  font-size: 1.1rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
