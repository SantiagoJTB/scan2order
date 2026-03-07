<template>
  <div class="home-container">
    <div class="hero-section">
      <h1>🍽️ Scan2Order</h1>
      <p class="subtitle">Escanea, ordena y disfruta</p>
    </div>

    <div class="restaurants-section">
      <h2 class="section-title">Restaurantes Disponibles</h2>
      
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
        <p>Cargando restaurantes...</p>
      </div>

      <div v-else-if="error" class="error-message">
        {{ error }}
      </div>

      <div v-else-if="restaurants.length === 0" class="empty-state">
        <p>📭 No hay restaurantes disponibles en este momento</p>
      </div>

      <div v-else class="restaurants-grid">
        <div 
          v-for="restaurant in restaurants" 
          :key="restaurant.id" 
          class="restaurant-card"
          @click="viewRestaurant(restaurant)"
        >
          <div class="restaurant-icon">🏪</div>
          <div class="restaurant-info">
            <h3 class="restaurant-name">{{ restaurant.name }}</h3>
            <p v-if="restaurant.address" class="restaurant-address">
              📍 {{ restaurant.address }}
            </p>
            <p v-if="restaurant.phone" class="restaurant-phone">
              📞 {{ restaurant.phone }}
            </p>
          </div>
          <div class="card-arrow">→</div>
        </div>
      </div>
    </div>

    <div class="footer-actions">
      <p v-if="!auth.isAuthenticated" class="auth-prompt">
        <router-link to="/login" class="link-primary">Inicia sesión</router-link> 
        o 
        <router-link to="/register" class="link-primary">regístrate</router-link> 
        para hacer pedidos
      </p>
      <p v-else class="welcome-message">
        Bienvenido, <strong>{{ auth.user?.name }}</strong> 👋
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const restaurants = ref([])
const isLoading = ref(false)
const error = ref(null)

async function fetchRestaurants() {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/restaurants', {
      headers: {
        'Accept': 'application/json'
      }
    })
    
    if (!response.ok) {
      throw new Error('No se pudieron cargar los restaurantes')
    }
    
    const data = await response.json()
    restaurants.value = data
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function viewRestaurant(restaurant) {
  // Navegar a la carta del restaurante
  router.push({ name: 'RestaurantMenu', params: { id: restaurant.id } })
}

onMounted(() => {
  fetchRestaurants()
})
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.hero-section {
  text-align: center;
  color: white;
  margin-bottom: 3rem;
  padding: 2rem 0;
}

.hero-section h1 {
  font-size: 3rem;
  margin: 0 0 1rem;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.subtitle {
  font-size: 1.3rem;
  opacity: 0.95;
  margin: 0;
}

.restaurants-section {
  max-width: 1200px;
  margin: 0 auto;
}

.section-title {
  color: white;
  font-size: 2rem;
  text-align: center;
  margin-bottom: 2rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.loading {
  text-align: center;
  color: white;
  padding: 3rem;
}

.spinner {
  width: 50px;
  height: 50px;
  margin: 0 auto 1rem;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  background: #ffe6e6;
  color: #c0392b;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  max-width: 500px;
  margin: 0 auto;
}

.empty-state {
  text-align: center;
  color: white;
  font-size: 1.2rem;
  padding: 3rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(10px);
}

.restaurants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.restaurant-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.restaurant-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
}

.restaurant-icon {
  font-size: 3rem;
  flex-shrink: 0;
}

.restaurant-info {
  flex: 1;
}

.restaurant-name {
  font-size: 1.4rem;
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-weight: 700;
}

.restaurant-address,
.restaurant-phone {
  color: #7f8c8d;
  font-size: 0.95rem;
  margin: 0.25rem 0;
}

.card-arrow {
  font-size: 1.5rem;
  color: #667eea;
  font-weight: bold;
  flex-shrink: 0;
}

.footer-actions {
  text-align: center;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(10px);
  margin-top: 2rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.auth-prompt {
  color: white;
  font-size: 1.1rem;
  margin: 0;
}

.welcome-message {
  color: white;
  font-size: 1.1rem;
  margin: 0;
}

.link-primary {
  color: #ffd43b;
  text-decoration: none;
  font-weight: 700;
  transition: opacity 0.3s ease;
}

.link-primary:hover {
  opacity: 0.8;
  text-decoration: underline;
}

@media (max-width: 768px) {
  .hero-section h1 {
    font-size: 2rem;
  }

  .subtitle {
    font-size: 1rem;
  }

  .restaurants-grid {
    grid-template-columns: 1fr;
  }

  .restaurant-card {
    flex-direction: column;
    text-align: center;
  }

  .card-arrow {
    display: none;
  }
}
</style>
