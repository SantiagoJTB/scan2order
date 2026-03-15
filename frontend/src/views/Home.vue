<template>
  <div class="home-container">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-grid">

        <div class="hero-column brand-column" data-aos="fade-left">
          <div class="hero-icon">🍽️</div>
          <h1 class="hero-title">Scan2Order</h1>
          <p class="hero-subtitle">
            Explora, ordena y disfruta de tu comida favorita
          </p>
          <div class="hero-features">
            <span class="feature-pill">⚡ Rápido</span>
            <span class="feature-pill">🔒 Seguro</span>
            <span class="feature-pill">📱 Fácil</span>
          </div>
        </div>
        
        <!-- Auth Column -->
        <div class="hero-column auth-column" data-aos="fade-right">
          <div v-if="!auth.isAuthenticated" class="auth-hero-card">
            <div class="auth-icon">🔐</div>
            <h2>¿Listo para ordenar?</h2>
            <p>Inicia sesión o crea una cuenta para comenzar</p>
            <div class="auth-buttons">
              <router-link to="/login" class="btn-auth btn-login">Iniciar sesión</router-link>
              <router-link to="/register" class="btn-auth btn-register">Crear cuenta</router-link>
            </div>
          </div>
          <div v-else class="welcome-hero-card">
            <div class="welcome-avatar">{{ auth.user?.name?.charAt(0) }}</div>
            <h2>¡Hola, {{ auth.user?.name }}! 👋</h2>
            <p>¿Qué te gustaría comer hoy?</p>
          </div>
        </div>

        <!-- Brand Column -->
      </div>
      <div class="hero-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
          <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"></path>
        </svg>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="content-wrapper">
        <!-- Section Header -->
        <div class="section-header">
          <h2 class="section-title">Restaurantes Cerca de Ti</h2>
          <p class="section-subtitle">Descubre los mejores sabores de tu ciudad</p>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-state">
          <div class="loader-spinner"></div>
          <p class="loading-text">Buscando restaurantes...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state" data-aos="fade-in">
          <div class="error-icon">⚠️</div>
          <h3>Ups, algo salió mal</h3>
          <p>{{ error }}</p>
          <button @click="fetchRestaurants" class="btn-retry">Intentar de nuevo</button>
        </div>

        <!-- Empty State -->
        <div v-else-if="restaurants.length === 0" class="empty-state" data-aos="fade-in">
          <div class="empty-icon">🍴</div>
          <h3>No hay restaurantes disponibles</h3>
          <p>Vuelve pronto para descubrir nuevas opciones</p>
        </div>

        <!-- Restaurants Grid -->
        <div v-else class="restaurants-grid">
          <div 
            v-for="(restaurant, index) in restaurants" 
            :key="restaurant.id" 
            class="restaurant-card"
            @click="viewRestaurant(restaurant)"
            data-aos="fade-up"
            :data-aos-delay="index * 50"
          >
            <div class="card-image">
              <div class="card-image-placeholder">
                <span class="image-emoji">🏪</span>
              </div>
              <div class="card-badge">Disponible</div>
            </div>
            <div class="card-content">
              <h3 class="card-title">{{ restaurant.name }}</h3>
              <div class="card-details">
                <div v-if="restaurant.address" class="detail-item">
                  <span class="detail-icon">📍</span>
                  <span class="detail-text">{{ restaurant.address }}</span>
                </div>
                <div v-if="restaurant.phone" class="detail-item">
                  <span class="detail-icon">📞</span>
                  <span class="detail-text">{{ restaurant.phone }}</span>
                </div>
              </div>
              <div class="card-footer">
                <span class="view-menu">Ver menú</span>
                <span class="arrow-icon">→</span>
              </div>
            </div>
          </div>
        </div>
      </div>
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
  router.push({ name: 'RestaurantMenu', params: { id: restaurant.id } })
}

onMounted(() => {
  fetchRestaurants()
  initAOS()
})

function initAOS() {
  // Simple fade-in animation
  setTimeout(() => {
    document.querySelectorAll('[data-aos]').forEach((el, index) => {
      setTimeout(() => {
        el.style.opacity = '1'
        el.style.transform = 'translateY(0)'
      }, index * 100)
    })
  }, 100)
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

[data-aos] {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.6s ease;
}

.home-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow-x: hidden;
}

/* Hero Section */
.hero-section {
  position: relative;
  padding: max(2.5rem, calc(env(safe-area-inset-top) + 1.5rem)) 1.5rem 8rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
  background-size: 50px 50px;
  opacity: 0.3;
}

.hero-grid {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
}

.hero-column {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.auth-column {
  justify-content: center;
}

.brand-column {
  text-align: center;
}

/* Auth Hero Card */
.auth-hero-card,
.welcome-hero-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  text-align: center;
  width: 100%;
}

.auth-hero-card .auth-icon,
.welcome-hero-card .welcome-avatar {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.welcome-hero-card .welcome-avatar {
  width: 80px;
  height: 80px;
  margin: 0 auto 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 700;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.auth-hero-card h2,
.welcome-hero-card h2 {
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-size: 1.8rem;
  font-weight: 700;
}

.auth-hero-card p,
.welcome-hero-card p {
  color: #7f8c8d;
  margin: 0 0 2rem;
  font-size: 1.1rem;
}

.auth-buttons {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-auth {
  padding: 0.9rem 2rem;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 700;
  font-size: 1rem;
  transition: all 0.3s ease;
  text-align: center;
}

.btn-login {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.btn-register {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
}

.btn-register:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

/* Brand Column */
.hero-content {
  position: relative;
  z-index: 1;
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.hero-title {
  font-size: clamp(2.5rem, 8vw, 4rem);
  color: white;
  margin: 0 0 1rem;
  font-weight: 800;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  letter-spacing: -1px;
}

.hero-subtitle {
  font-size: clamp(1rem, 3vw, 1.4rem);
  color: rgba(255, 255, 255, 0.95);
  margin: 0 0 2rem;
  line-height: 1.6;
}

.hero-features {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: center;
  align-items: center;
}

.feature-pill {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 50px;
  font-size: 0.95rem;
  font-weight: 600;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.hero-wave {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 80px;
  overflow: hidden;
  line-height: 0;
}

.hero-wave svg {
  position: relative;
  display: block;
  width: calc(100% + 1.3px);
  height: 80px;
}

.hero-wave path {
  fill: #f8f9fa;
}

/* Main Content */
.main-content {
  background: #f8f9fa;
  min-height: 100vh;
  padding: 3rem 1.5rem 4rem;
}

.content-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title {
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-weight: 700;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #7f8c8d;
  margin: 0;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 4rem 2rem;
}

.loader-spinner {
  width: 60px;
  height: 60px;
  margin: 0 auto 1.5rem;
  border: 4px solid #ecf0f1;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1.1rem;
  color: #7f8c8d;
}

/* Error State */
.error-state {
  text-align: center;
  padding: 3rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  max-width: 500px;
  margin: 0 auto;
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.error-state h3 {
  color: #e74c3c;
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
}

.error-state p {
  color: #7f8c8d;
  margin: 0 0 1.5rem;
}

.btn-retry {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.8rem 2rem;
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  max-width: 500px;
  margin: 0 auto;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.empty-state h3 {
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
}

.empty-state p {
  color: #7f8c8d;
  margin: 0;
}

/* Restaurants Grid */
.restaurants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.restaurant-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.restaurant-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(102, 126, 234, 0.25);
}

.card-image {
  position: relative;
  height: 180px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-emoji {
  font-size: 5rem;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
}

.card-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.95);
  color: #27ae60;
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 700;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.card-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.card-title {
  font-size: 1.4rem;
  color: #2c3e50;
  margin: 0 0 1rem;
  font-weight: 700;
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #7f8c8d;
  font-size: 0.95rem;
}

.detail-icon {
  font-size: 1rem;
  flex-shrink: 0;
}

.detail-text {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 2px solid #ecf0f1;
  gap: 1rem;
}

.view-menu {
  color: #667eea;
  font-weight: 700;
  font-size: 1rem;
}

.arrow-icon {
  font-size: 1.5rem;
  color: #667eea;
  transition: transform 0.3s ease;
}

.restaurant-card:hover .arrow-icon {
  transform: translateX(5px);
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .hero-section {
    padding: 3rem 1rem 6rem;
  }

  .hero-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .brand-column {
    order: -1;
  }

  .hero-icon {
    font-size: 4rem;
  }

  .auth-hero-card,
  .welcome-hero-card {
    padding: 2rem;
    border-radius: 20px;
  }

  .auth-hero-card h2,
  .welcome-hero-card h2 {
    font-size: 1.5rem;
  }

  .auth-hero-card p,
  .welcome-hero-card p {
    font-size: 1rem;
  }

  .main-content {
    padding: 2rem 1rem;
  }

  .section-header {
    margin-bottom: 2rem;
  }

  .restaurants-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .card-content {
    padding: 1.25rem;
  }

  .detail-text {
    white-space: normal;
    overflow: visible;
  }
}

@media (max-width: 480px) {
  .hero-section {
    padding-top: max(2rem, calc(env(safe-area-inset-top) + 1rem));
  }

  .hero-features {
    gap: 0.5rem;
  }

  .feature-pill {
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
  }

  .card-image {
    height: 150px;
  }

  .image-emoji {
    font-size: 4rem;
  }

  .auth-hero-card,
  .welcome-hero-card {
    padding: 2rem 1.5rem;
  }

  .card-badge {
    top: 0.75rem;
    right: 0.75rem;
  }
}
</style>
