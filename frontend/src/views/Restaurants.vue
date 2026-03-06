<template>
  <div class="restaurants-container">
    <div class="page-header">
      <h2>Restaurantes</h2>
      <button @click="showAddForm = true" class="btn btn-primary">+ Nuevo Restaurante</button>
    </div>

    <div v-if="showAddForm" class="form-modal">
      <div class="form-content">
        <h3>Nuevo Restaurante</h3>
        <form @submit.prevent="addRestaurant">
          <input v-model="newRestaurant.name" type="text" placeholder="Nombre" required />
          <input v-model="newRestaurant.address" type="text" placeholder="Dirección" required />
          <input v-model="newRestaurant.phone" type="tel" placeholder="Teléfono" />
          <div class="form-actions">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" @click="showAddForm = false" class="btn btn-secondary">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="restaurantStore.loading" class="loading">Cargando...</div>
    
    <div class="restaurants-grid">
      <div v-for="restaurant in restaurantStore.restaurants" :key="restaurant.id" class="restaurant-card">
        <h3>{{ restaurant.name }}</h3>
        <p><strong>Dirección:</strong> {{ restaurant.address }}</p>
        <p v-if="restaurant.phone"><strong>Teléfono:</strong> {{ restaurant.phone }}</p>
        <div class="card-actions">
          <button @click="deleteRestaurant(restaurant.id)" class="btn btn-small btn-danger">Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="restaurantStore.restaurants.length === 0 && !restaurantStore.loading" class="empty-state">
      <p>No hay restaurantes registrados. ¡Crea uno para empezar!</p>
    </div>
  </div>
</template>

<script setup>
import { useRestaurantsStore } from '../stores/restaurants'
import { ref, onMounted } from 'vue'

const restaurantStore = useRestaurantsStore()
const showAddForm = ref(false)
const newRestaurant = ref({
  name: '',
  address: '',
  phone: ''
})

const addRestaurant = async () => {
  try {
    const success = await restaurantStore.createRestaurant(newRestaurant.value)
    if (success) {
      newRestaurant.value = { name: '', address: '', phone: '' }
      showAddForm.value = false
      alert('Restaurante agregado exitosamente')
    } else {
      alert('Error al agregar restaurante: ' + restaurantStore.error)
    }
  } catch (error) {
    alert('Error al agregar restaurante')
  }
}

const deleteRestaurant = async (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar este restaurante?')) {
    const success = await restaurantStore.deleteRestaurant(id)
    if (success) {
      alert('Restaurante eliminado')
    } else {
      alert('Error al eliminar restaurante: ' + restaurantStore.error)
    }
  }
}

onMounted(() => {
  restaurantStore.fetchRestaurants()
})
</script>

<style scoped>
.restaurants-container {
  animation: fadeIn 0.5s ease-in;
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

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.3s;
}

.btn-primary {
  background-color: #667eea;
  color: white;
}

.btn-primary:hover {
  background-color: #5568d3;
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

.btn-secondary {
  background-color: #718096;
  color: white;
}

.btn-small {
  padding: 0.25rem 0.75rem;
  font-size: 0.9rem;
}

.btn-info {
  background-color: #4299e1;
  color: white;
}

.form-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.form-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 400px;
}

.form-content h3 {
  margin-bottom: 1.5rem;
  color: #2c3e50;
}

.form-content form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-content input {
  padding: 0.75rem;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.restaurants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.restaurant-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.restaurant-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

.restaurant-card h3 {
  color: #667eea;
  margin-bottom: 1rem;
}

.restaurant-card p {
  color: #666;
  margin-bottom: 0.5rem;
  line-height: 1.6;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.empty-state {
  text-align: center;
  color: white;
  padding: 3rem;
  font-size: 1.1rem;
}

.loading {
  text-align: center;
  color: white;
  padding: 2rem;
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
