<template>
  <div class="payments-container">
    <div class="header">
      <h1>Gestión de Pagos</h1>
      <p>Panel de caja - Procesa los pagos de las órdenes</p>
    </div>

    <div class="content">
      <div class="info-box">
        <p>
          Este módulo permite gestionar los pagos de las órdenes en espera.
        </p>
      </div>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-value">0</div>
          <div class="stat-label">Órdenes pendientes</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">$0.00</div>
          <div class="stat-label">Total en caja</div>
        </div>
      </div>

      <div class="orders-section">
        <h2>Órdenes en espera de pago</h2>
        <div class="no-orders">
          No hay órdenes pendientes de pago en este momento.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

</script>

<style scoped>
.payments-container {
  max-width: 1000px;
  margin: 0 auto;
}

.header {
  text-align: center;
  color: white;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.info-box {
  background: #d1ecf1;
  color: #0c5460;
  padding: 1rem;
  border-radius: 5px;
  margin-bottom: 2rem;
  border-left: 4px solid #17a2b8;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 10px;
  text-align: center;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.orders-section {
  margin-top: 2rem;
}

.orders-section h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.no-orders {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  background: #f5f6fa;
  border-radius: 5px;
}
</style>
