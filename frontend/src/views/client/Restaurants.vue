<template>
  <div class="restaurants-page">
    <!-- Header -->
    <div class="restaurants-header">
      <div class="header-content">
        <button @click="$router.back()" class="btn-back">
          <span class="back-icon">←</span>
          <span>Volver</span>
        </button>
        <h1 class="page-title">🍽️ Restaurantes</h1>
        <div class="header-spacer"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="🔍 Buscar restaurante por nombre..."
          class="search-input"
        />
      </div>

      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Cargando restaurantes...</p>
      </div>

      <div v-else-if="error" class="error-box">
        <p>{{ error }}</p>
        <button @click="fetchRestaurants" class="btn-retry">Intentar de nuevo</button>
      </div>

      <div v-else-if="restaurants.length === 0" class="no-restaurants">
        <div class="empty-icon">🍽️</div>
        <h2>No hay restaurantes disponibles</h2>
        <p>En este momento no hay restaurantes disponibles.</p>
      </div>

      <div v-else-if="filteredRestaurants.length === 0" class="no-restaurants">
        <div class="empty-icon">🔍</div>
        <h2>No hay resultados</h2>
        <p>No se encontraron restaurantes que coincidan con "{{ searchQuery }}"</p>
      </div>

      <div v-else class="restaurants-grid">
        <div
          v-for="restaurant in filteredRestaurants"
          :key="restaurant.id"
          class="restaurant-card"
          @click="selectRestaurant(restaurant.id)"
        >
          <div class="restaurant-content">
            <h3>{{ restaurant.name }}</h3>
            <p v-if="restaurant.address" class="address">📍 {{ restaurant.address }}</p>
            <p v-if="restaurant.phone" class="phone">📞 {{ restaurant.phone }}</p>
            <div class="status">
              <span v-if="restaurant.active" class="badge active">Abierto</span>
              <span v-else class="badge inactive">Cerrado</span>
            </div>
          </div>
          <div class="arrow">→</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const restaurants = ref([])
const searchQuery = ref('')
const isLoading = ref(false)
const error = ref('')

const filteredRestaurants = computed(() => {
  if (!searchQuery.value.trim()) {
    return restaurants.value
  }
  
  const query = searchQuery.value.toLowerCase()
  return restaurants.value.filter(restaurant =>
    restaurant.name.toLowerCase().includes(query) ||
    (restaurant.address && restaurant.address.toLowerCase().includes(query))
  )
})

onMounted(() => {
  fetchRestaurants()
})

async function fetchRestaurants() {
  isLoading.value = true
  error.value = ''

  try {
    const response = await fetch('/api/restaurants', {
      headers: {
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar los restaurantes')

    const data = await response.json()
    restaurants.value = Array.isArray(data) ? data : []
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function selectRestaurant(restaurantId) {
  router.push({ name: 'RestaurantMenu', params: { id: restaurantId } })
}
</script>

<style scoped>
.restaurants-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding-bottom: calc(2rem + env(safe-area-inset-bottom));
}

/* Header */
.restaurants-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: max(1rem, env(safe-area-inset-top)) 0 1rem;
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
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.search-box {
  margin-bottom: 2rem;
}

.search-input {
  width: 100%;
  min-height: 56px;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  border: none;
  border-radius: 50px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.search-input:focus {
  outline: none;
  transform: translateY(-2px);
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
}

/* Loading & Empty States */
.loading,
.no-restaurants {
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

.no-restaurants h2 {
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-size: 1.8rem;
}

.no-restaurants p {
  color: #7f8c8d;
  margin: 0;
  font-size: 1.1rem;
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

/* Restaurants Grid */
.restaurants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.restaurant-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  border: 2px solid transparent;
}

.restaurant-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.restaurant-content {
  flex: 1;
}

.restaurant-card h3 {
  font-size: 1.4rem;
  margin: 0 0 0.8rem 0;
  color: #2c3e50;
  font-weight: 700;
}

.address,
.phone {
  font-size: 0.95rem;
  color: #7f8c8d;
  margin: 0.4rem 0;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.status {
  margin-top: 1rem;
}

.badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 700;
}

.badge.active {
  background: linear-gradient(135deg, #d5f4e6 0%, #b8e6d0 100%);
  color: #27ae60;
  box-shadow: 0 2px 10px rgba(39, 174, 96, 0.2);
}

.badge.inactive {
  background: linear-gradient(135deg, #fadbd8 0%, #f5b7b1 100%);
  color: #c0392b;
  box-shadow: 0 2px 10px rgba(192, 57, 43, 0.2);
}

.arrow {
  font-size: 2rem;
  color: #667eea;
  margin-left: 1.5rem;
  transition: transform 0.3s ease;
}

.restaurant-card:hover .arrow {
  transform: translateX(5px);
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

  .search-box {
    position: sticky;
    top: 5.75rem;
    z-index: 40;
    margin: 0 -1rem 1rem;
    padding: 0 1rem 0.75rem;
    background: linear-gradient(180deg, rgba(102, 126, 234, 0.96) 0%, rgba(102, 126, 234, 0) 100%);
  }

  .restaurants-grid {
    grid-template-columns: 1fr;
  }

  .restaurant-card {
    padding: 1.5rem;
    gap: 1rem;
  }

  .restaurant-card h3 {
    font-size: 1.2rem;
  }

  .arrow {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .restaurant-card {
    align-items: flex-start;
  }

  .arrow {
    margin-left: 0;
    align-self: flex-end;
  }

  .empty-icon {
    font-size: 4rem;
  }

  .no-restaurants h2 {
    font-size: 1.5rem;
  }

  .no-restaurants p {
    font-size: 1rem;
  }
}
</style>
