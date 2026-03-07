<template>
  <div class="admin-container">
    <div class="header">
      <h1>Dashboard Admin</h1>
      <p>Bienvenido al panel de administración</p>
    </div>

    <div class="dashboard-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <p class="stat-label">Usuarios</p>
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
      <h2>Acciones rápidas</h2>
      <div class="action-buttons">
        <router-link to="/admin/users" class="action-btn">
          <span class="btn-icon">👥</span>
          Gestionar usuarios
        </router-link>
        <a href="#" class="action-btn">
          <span class="btn-icon">🍽️</span>
          Gestionar restaurantes
        </a>
        <a href="#" class="action-btn">
          <span class="btn-icon">📦</span>
          Gestionar productos
        </a>
        <a href="#" class="action-btn">
          <span class="btn-icon">📋</span>
          Ver órdenes
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const stats = ref({
  users: 0,
  restaurants: 0,
  products: 0,
  orders: 0
})

// Simulated stats (in production, fetch from API)
onMounted(() => {
  stats.value = {
    users: 12,
    restaurants: 5,
    products: 48,
    orders: 156
  }
})
</script>

<style scoped>
.admin-container {
  max-width: 1200px;
  margin: 0 auto;
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

.btn-icon {
  font-size: 2rem;
}
</style>
