<template>
  <div class="restaurants-container">
    <div class="header">
      <h1>Gestión de Restaurantes</h1>
      <button class="btn-create">
        + Crear Restaurante
      </button>
    </div>

    <div class="content">
      <div class="info-box">
        <h2>📋 En construcción</h2>
        <p>Esta funcionalidad se implementará próximamente.</p>
        <p>Aquí podrás:</p>
        <ul>
          <li>Ver lista de restaurantes</li>
          <li>Crear nuevos restaurantes</li>
          <li>Editar información de restaurantes</li>
          <li>Gestionar sucursales</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.restaurants-container {
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

.btn-create {
  background: #27ae60;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-create:hover {
  opacity: 0.9;
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
  color: #3498db;
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
