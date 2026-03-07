<template>
  <div class="menu-container">
    <div class="header">
      <div v-if="restaurant" class="restaurant-header">
        <h1>{{ restaurant.name }}</h1>
        <p v-if="restaurant.address" class="restaurant-info">📍 {{ restaurant.address }}</p>
        <p v-if="restaurant.phone" class="restaurant-info">📞 {{ restaurant.phone }}</p>
      </div>
      <div v-else>
        <h1>Menú de Productos</h1>
        <p>Selecciona los productos que deseas ordenar</p>
      </div>
    </div>

    <div class="menu-content">
      <!-- Restaurants selector -->
      <div class="filters">
        <input
          v-model="searchText"
          type="text"
          placeholder="Buscar productos..."
          class="search-input"
        />
      </div>

      <!-- Loading and error states -->
      <div v-if="isLoading" class="loading">Cargando productos...</div>
      <div v-if="error" class="error">{{ error }}</div>

      <!-- Products grid -->
      <div v-if="filteredProducts.length > 0" class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card">
          <div class="product-image">
            <!-- Placeholder for image -->
            <div class="image-placeholder">{{ product.name.charAt(0) }}</div>
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="category">{{ product.category?.name }}</p>
            <p class="description">{{ product.description }}</p>
            <div class="product-footer">
              <span class="price">${{ product.price.toFixed(2) }}</span>
              <button @click="addToCart(product)" class="btn-add">
                Agregar al carrito
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!isLoading" class="no-products">
        No hay productos disponibles para este restaurante
      </div>
    </div>

    <!-- Quick cart preview -->
    <div v-if="cart.itemCount > 0" class="cart-preview">
      <div class="preview-header">
        <h4>Carrito ({{ cart.itemCount }} items)</h4>
        <span class="preview-total">Total: ${{ cart.total.toFixed(2) }}</span>
      </div>
      <router-link to="/cart" class="btn-go-cart">Ir al carrito</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../../stores/cart'

const route = useRoute()
const cart = useCartStore()
const isLoading = ref(false)
const error = ref(null)
const products = ref([])
const restaurant = ref(null)
const searchText = ref('')

const filteredProducts = computed(() => {
  if (!searchText.value) return products.value
  
  const search = searchText.value.toLowerCase()
  return products.value.filter(p => 
    p.name.toLowerCase().includes(search) ||
    p.description.toLowerCase().includes(search)
  )
})

async function fetchRestaurant(id) {
  try {
    const response = await fetch(`/api/restaurants/${id}`, {
      headers: { 'Accept': 'application/json' }
    })
    if (!response.ok) throw new Error('Restaurant not found')
    restaurant.value = await response.json()
  } catch (err) {
    console.error('Error fetching restaurant:', err)
  }
}

async function fetchProducts() {
  isLoading.value = true
  error.value = null
  try {
    const restaurantId = route.params.id
    let url = '/api/products'
    
    // If we have a restaurant ID, filter by restaurant
    if (restaurantId) {
      url += `?restaurant_id=${restaurantId}`
    }
    
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    })
    
    if (!response.ok) throw new Error('Failed to fetch products')
    
    const data = await response.json()
    // Filter by restaurant_id if provided
    if (restaurantId) {
      products.value = data.filter(p => p.restaurant_id == restaurantId)
    } else {
      products.value = data
    }
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function addToCart(product) {
  cart.addItem(product, 1)
}

onMounted(() => {
  const restaurantId = route.params.id
  if (restaurantId) {
    fetchRestaurant(restaurantId)
  }
  fetchProducts()
})
</script>

<style scoped>
.menu-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
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

.restaurant-header {
  text-align: center;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(10px);
}

.restaurant-header h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.restaurant-info {
  font-size: 1.1rem;
  margin: 0.5rem 0;
  opacity: 0.95;
}

.menu-content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.filters {
  margin-bottom: 2rem;
}

.search-input {
  width: 100%;
  padding: 1rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
}

.loading, .error, .no-products {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  font-size: 1.1rem;
}

.error {
  background: #ffe6e6;
  color: #c0392b;
  border-radius: 5px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.product-card {
  border: 2px solid #ecf0f1;
  border-radius: 10px;
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
  border-color: #667eea;
  transform: translateY(-2px);
}

.product-image {
  height: 180px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 4rem;
  font-weight: bold;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.product-info {
  padding: 1rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-info h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-size: 1.1rem;
}

.category {
  color: #667eea;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.description {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  flex: 1;
}

.product-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #ecf0f1;
}

.price {
  font-size: 1.4rem;
  font-weight: 700;
  color: #27ae60;
}

.btn-add {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-add:hover {
  opacity: 0.9;
}

.cart-preview {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  position: sticky;
  bottom: 0;
  left: 0;
  right: 0;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.preview-header h4 {
  color: #2c3e50;
  margin: 0;
}

.preview-total {
  font-size: 1.3rem;
  font-weight: 700;
  color: #27ae60;
}

.btn-go-cart {
  width: 100%;
  display: block;
  text-align: center;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 5px;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-go-cart:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }

  .menu-container {
    padding: 1rem;
  }
}
</style>
