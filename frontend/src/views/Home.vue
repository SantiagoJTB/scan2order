<template>
  <div class="home-container">
    <div class="hero">
      <h2>Bienvenido a Scan2Order</h2>
      <p>Sistema de gestión de pedidos para restaurantes</p>
      <div class="features">
        <div class="feature-card">
          <h3>📱 Escanea QR</h3>
          <p>Lee códigos QR desde las mesas del restaurante</p>
        </div>
        <div class="feature-card">
          <h3>🍽️ Gestiona Pedidos</h3>
          <p>Administra todos tus pedidos en tiempo real</p>
        </div>
        <div class="feature-card">
          <h3>💳 Pagos Seguros</h3>
          <p>Procesa pagos de manera segura y confiable</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Home',
  data() {
    return {
      message: 'Bienvenido a Scan2Order'
    }
  }
}
</script>

<style scoped>
.home-container {
  animation: fadeIn 0.5s ease-in;
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

.hero {
  text-align: center;
  color: white;
  padding: 3rem 0;
}

.hero h2 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.hero p {
  font-size: 1.2rem;
  margin-bottom: 3rem;
  opacity: 0.9;
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.feature-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  animation: slideUp 0.6s ease-out;
}

.feature-card h3 {
  color: #667eea;
  margin-bottom: 0.5rem;
  font-size: 1.3rem;
}

.feature-card p {
  color: #666;
  line-height: 1.6;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
