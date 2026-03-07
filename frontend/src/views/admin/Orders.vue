<template>
  <div v-if="canAccessAdmin" class="orders-container">
    <div class="header">
      <h1>Gestión de Órdenes</h1>
      <div class="header-actions">
        <select class="filter-select">
          <option value="all">Todas</option>
          <option value="pending">Pendientes</option>
          <option value="processing">En proceso</option>
          <option value="completed">Completadas</option>
        </select>
      </div>
    </div>

    <div class="content">
      <div class="info-box">
        <h2>📋 En construcción</h2>
        <p>Esta funcionalidad se implementará próximamente.</p>
        <p>Aquí podrás:</p>
        <ul>
          <li>Ver todas las órdenes del sistema</li>
          <li>Filtrar por estado (pendiente, proceso, completada)</li>
          <li>Ver detalles de cada orden</li>
          <li>Gestionar estados de órdenes</li>
          <li>Generar reportes de ventas</li>
        </ul>
      </div>
    </div>
  </div>

  <div v-else class="orders-container">
    <div class="content">
      <div class="info-box">
        <h2>⛔ Acceso denegado</h2>
        <p>Solo Admin y Superadmin pueden acceder a esta sección.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
</script>

<style scoped>
.orders-container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.filter-select {
  background: white;
  color: #2c3e50;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.info-box {
  text-align: center;
  padding: 2rem;
}

.info-box h2 {
  color: #e67e22;
  font-size: 1.8rem;
  margin-bottom: 1rem;
}

.info-box p {
  color: #7f8c8d;
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.info-box ul {
  text-align: left;
  max-width: 500px;
  margin: 2rem auto;
  list-style: none;
  padding: 0;
}

.info-box ul li {
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  background: #ecf0f1;
  border-radius: 5px;
  color: #2c3e50;
}

.info-box ul li:before {
  content: "✓ ";
  color: #27ae60;
  font-weight: bold;
  margin-right: 0.5rem;
}
</style>
