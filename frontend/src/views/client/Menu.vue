<template>
  <div class="menu-container">
    <div class="header">
      <div v-if="restaurant" class="restaurant-header">
        <h1>{{ restaurant.name }}</h1>
        <p v-if="restaurant.address" class="restaurant-info">📍 {{ restaurant.address }}</p>
        <p v-if="restaurant.phone" class="restaurant-info">📞 {{ restaurant.phone }}</p>
        <div class="restaurant-stats">
          <span class="stat-chip">{{ allSections.length }} secciones</span>
          <span class="stat-chip">{{ totalProducts }} productos</span>
          <span v-if="cart.itemCount > 0" class="stat-chip stat-chip-accent">{{ cart.itemCount }} en carrito</span>
        </div>
      </div>
      <div v-else>
        <h1>Menú de Productos</h1>
        <p>Selecciona los productos que deseas ordenar</p>
      </div>
    </div>

    <div class="menu-content">
      <!-- Search bar -->
      <div class="filters">
        <div class="filters-row">
          <div class="search-input-wrap">
            <span class="search-icon">🔎</span>
            <input
              v-model="searchText"
              type="text"
              placeholder="Buscar productos..."
              class="search-input"
            />
            <button
              v-if="searchText"
              type="button"
              class="btn-clear-search"
              @click="searchText = ''"
            >
              ✕
            </button>
          </div>
        </div>
        <div v-if="selectedSection" class="filters-meta">
          <span>{{ allSections.length }} secciones</span>
          <span>{{ filteredCurrentProducts.length }} productos visibles</span>
          <span v-if="searchText">Buscando: “{{ searchText }}”</span>
        </div>
      </div>

      <!-- Loading and error states -->
      <div v-if="isLoading" class="loading">Cargando menú...</div>
      <div v-if="error" class="error">{{ error }}</div>

      <!-- Menu with navigation -->
      <div v-if="allSections.length > 0 && !isLoading" class="menu-layout">
        <!-- Sections Navigation -->
        <nav class="sections-nav">
          <div class="nav-header">
            <h3>📋 Secciones</h3>
          </div>
          <div class="nav-sections">
            <button
              v-for="section in allSections"
              :key="section.id"
              @click="selectSection(section)"
              :class="['nav-section-btn', { active: selectedSection?.id === section.id }]"
            >
              <span class="section-name">{{ section.name }}</span>
              <span class="section-badge">{{ section.products?.length || 0 }}</span>
            </button>
          </div>
        </nav>

        <!-- Products Display -->
        <div class="products-container">
          <div v-if="selectedSection" class="section-header">
            <h2 class="section-title">{{ selectedSection.name }}</h2>
            <p v-if="selectedSection.catalog" class="catalog-subtitle">
              {{ selectedSection.catalog.name }}
            </p>
          </div>

          <div v-if="filteredCurrentProducts.length > 0" class="products-grid">
            <div 
              v-for="product in filteredCurrentProducts" 
              :key="product.id" 
              :class="['product-card', { compact: !shouldDisplayProductImage(product), expanded: isProductExpanded(product.id) }]"
              @click="toggleProductDescription(product.id, Boolean(product.description))"
            >
              <div v-if="shouldDisplayProductImage(product)" class="product-image">
                <img :src="`/storage/${product.image}`" :alt="product.name" />
              </div>
              <div :class="['product-info', { compact: !shouldDisplayProductImage(product) }]">
                <h3>{{ product.name }}</h3>
                <p
                  v-if="product.description"
                  :class="['description', { expanded: isProductExpanded(product.id) }]"
                >
                  {{ product.description }}
                </p>
                <div class="product-footer">
                  <span class="price">${{ product.price.toFixed(2) }}</span>
                  <button @click.stop="addToCart(product)" class="btn-add">
                    Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="no-products-section">
            <div class="empty-icon">🍽️</div>
            <p>No hay productos disponibles en esta sección</p>
          </div>
        </div>
      </div>

      <div v-else-if="!isLoading && !error" class="no-products">
        No hay menús disponibles para este restaurante
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
const catalogs = ref([])
const restaurant = ref(null)
const searchText = ref('')
const selectedSection = ref(null)
const expandedProductIds = ref(new Set())

// Flatten all sections from all catalogs
const allSections = computed(() => {
  const sections = []
  catalogs.value.forEach(catalog => {
    if (catalog.sections && catalog.sections.length > 0) {
      catalog.sections.forEach(section => {
        sections.push({
          ...section,
          catalog: { id: catalog.id, name: catalog.name }
        })
      })
    }
  })
  
  // Auto-select first section if none selected
  if (sections.length > 0 && !selectedSection.value) {
    selectedSection.value = sections[0]
  }
  
  return sections
})

// Get filtered products from selected section
const filteredCurrentProducts = computed(() => {
  if (!selectedSection.value || !selectedSection.value.products) {
    return []
  }
  return filterProducts(selectedSection.value.products)
})

const totalProducts = computed(() => {
  return allSections.value.reduce((count, section) => count + (section.products?.length || 0), 0)
})

function selectSection(section) {
  selectedSection.value = section
  expandedProductIds.value = new Set()

  if (typeof window !== 'undefined' && window.innerWidth <= 1024) {
    requestAnimationFrame(() => {
      const productsTop = document.querySelector('.products-container')
      if (!productsTop) return

      const top = productsTop.getBoundingClientRect().top + window.scrollY - 110
      window.scrollTo({ top, behavior: 'smooth' })
    })
  }
}

function isProductExpanded(productId) {
  return expandedProductIds.value.has(productId)
}

function toggleProductDescription(productId, hasDescription) {
  if (!hasDescription) return

  const next = new Set(expandedProductIds.value)
  if (next.has(productId)) {
    next.delete(productId)
  } else {
    next.add(productId)
  }
  expandedProductIds.value = next
}

function filterProducts(products) {
  if (!searchText.value) return products
  
  const search = searchText.value.toLowerCase()
  return products.filter(p => 
    p.name.toLowerCase().includes(search) ||
    (p.description && p.description.toLowerCase().includes(search))
  )
}

function shouldDisplayProductImage(product) {
  return Boolean(product?.image) && product?.show_image !== false
}

async function fetchRestaurant(id) {
  try {
    const response = await fetch(`/api/restaurants/${id}`, {
      headers: { 'Accept': 'application/json' }
    })
    if (!response.ok) throw new Error('Restaurante no encontrado')
    restaurant.value = await response.json()
  } catch (err) {
    console.error('Error al obtener restaurante:', err)
    error.value = err.message
  }
}

async function fetchCatalogs(restaurantId) {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch(`/api/restaurants/${restaurantId}/catalogs`, {
      headers: { 'Accept': 'application/json' }
    })
    
    if (!response.ok) throw new Error('Error al cargar el menú')
    
    const data = await response.json()
    catalogs.value = data
  } catch (err) {
    error.value = err.message
    console.error('Error al obtener catálogos:', err)
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
    fetchCatalogs(restaurantId)
  } else {
    error.value = 'No se especificó un restaurante'
  }
})
</script>

<style scoped>
.menu-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding-bottom: calc(6.5rem + env(safe-area-inset-bottom));
}

.header {
  text-align: center;
  color: white;
  padding: max(2rem, calc(env(safe-area-inset-top) + 1rem)) 1.5rem 3rem;
  position: relative;
}

.header h1 {
  font-size: clamp(2rem, 5vw, 2.5rem);
  margin-bottom: 0.5rem;
}

.restaurant-header {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 20px;
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.restaurant-header h1 {
  font-size: clamp(2rem, 6vw, 3rem);
  margin-bottom: 1rem;
  text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  font-weight: 800;
}

.restaurant-info {
  font-size: 1.1rem;
  margin: 0.5rem 0;
  opacity: 0.95;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.restaurant-stats {
  margin-top: 1.2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
}

.stat-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 0.9rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.24);
  color: white;
  font-size: 0.92rem;
  font-weight: 700;
}

.stat-chip-accent {
  background: rgba(39, 174, 96, 0.22);
}

.menu-content {
  background: #f8f9fa;
  border-radius: 30px 30px 0 0;
  padding: 2rem 1.5rem 0;
  margin-top: -2rem;
  position: relative;
  min-height: calc(100vh - 300px);
}

.filters {
  max-width: 1400px;
  margin: 0 auto 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.filters-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.search-input-wrap {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  opacity: 0.65;
  pointer-events: none;
}

.search-input {
  width: 100%;
  flex: 1;
  min-height: 56px;
  padding: 1rem 3rem 1rem 2.8rem;
  border: 2px solid transparent;
  border-radius: 50px;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  background: white;
}

.toggle-images {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border-radius: 50px;
  padding: 0.75rem 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  color: #2c3e50;
  font-weight: 600;
  white-space: nowrap;
}

.toggle-images input {
  accent-color: #667eea;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 6px 30px rgba(102, 126, 234, 0.2);
  transform: translateY(-2px);
}

.filters-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  color: #667085;
  font-size: 0.92rem;
  font-weight: 600;
}

.filters-meta span {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.btn-clear-search {
  position: absolute;
  right: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 50%;
  background: #eef1ff;
  color: #5568d3;
  font-weight: 800;
  cursor: pointer;
}

.loading, .error, .no-products {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
  font-size: 1.1rem;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.loading::before {
  content: '';
  width: 60px;
  height: 60px;
  border: 4px solid #ecf0f1;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error {
  background: white;
  border-radius: 16px;
  color: #e74c3c;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(231, 76, 60, 0.1);
}

.menu-layout {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1.5rem;
  align-items: start;
}

.sections-nav {
  position: sticky;
  top: 1rem;
  background: white;
  border-radius: 16px;
  padding: 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  max-height: calc(100vh - 120px);
  overflow: auto;
}

.nav-header {
  margin-bottom: 0.8rem;
  padding-bottom: 0.8rem;
  border-bottom: 1px solid #edf1f5;
}

.nav-header h3 {
  margin: 0;
  font-size: 1.05rem;
  color: #2c3e50;
}

.nav-sections {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.nav-section-btn {
  border: 1px solid #edf1f5;
  background: #f8f9fa;
  border-radius: 12px;
  padding: 0.7rem 0.8rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.nav-section-btn:hover {
  border-color: #cfd6ff;
  background: #f3f5ff;
}

.nav-section-btn.active {
  border-color: #667eea;
  background: #eef1ff;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.12);
}

.section-name {
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.95rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.section-badge {
  min-width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #e9eef6;
  color: #4a5568;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
}

.nav-section-btn.active .section-badge {
  background: #667eea;
  color: white;
}

.products-container {
  min-width: 0;
}

.section-header {
  margin-bottom: 1rem;
  background: white;
  border-radius: 14px;
  padding: 1rem 1.2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.catalog-subtitle {
  margin: 0.25rem 0 0;
  color: #7f8c8d;
  font-size: 0.95rem;
}

.catalogs-container {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 4rem;
}

.catalog-section {
  padding-bottom: 3rem;
}

.catalog-title {
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  color: #2c3e50;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 4px solid #667eea;
  display: inline-block;
  font-weight: 800;
}

.catalog-description {
  color: #7f8c8d;
  font-size: 1.1rem;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.section-wrapper {
  margin-top: 2.5rem;
}

.section-title {
  font-size: clamp(1.3rem, 3vw, 1.8rem);
  color: #34495e;
  margin-bottom: 1.5rem;
  padding-left: 1rem;
  border-left: 6px solid #764ba2;
  font-weight: 700;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.products-grid.compact {
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}

.product-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  cursor: pointer;
}

.product-card.compact {
  border-radius: 14px;
}

.product-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
}

.product-card.expanded {
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.18);
}

.product-image {
  height: 200px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  position: relative;
  z-index: 2;
}

.product-image::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(20%, 20%); }
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 4.5rem;
  font-weight: 800;
  text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 1;
}

.product-info {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.product-info.compact {
  padding: 1rem;
  gap: 0.35rem;
}

.product-info.compact h3 {
  font-size: 1.05rem;
}

.product-info.compact .description {
  -webkit-line-clamp: 1;
  font-size: 0.9rem;
}

.product-info h3 {
  color: #2c3e50;
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  line-height: 1.3;
}

.description {
  color: #7f8c8d;
  font-size: 0.95rem;
  margin: 0;
  line-height: 1.5;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.description.expanded {
  display: block;
  -webkit-line-clamp: unset;
  -webkit-box-orient: initial;
  overflow: visible;
}

.product-info.compact .description.expanded {
  -webkit-line-clamp: unset;
}

.product-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 2px solid #f8f9fa;
  margin-top: auto;
}

.price {
  font-size: 1.6rem;
  font-weight: 800;
  color: #27ae60;
}

.btn-add {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 50px;
  cursor: pointer;
  font-weight: 700;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.btn-add:active {
  transform: translateY(0);
}

.no-products-section {
  text-align: center;
  padding: 2rem;
  background: white;
  border-radius: 16px;
  color: #95a5a6;
  font-style: italic;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.cart-preview {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  padding: 1rem max(1rem, env(safe-area-inset-right)) calc(1rem + env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  z-index: 100;
  transform: translateY(0);
  transition: transform 0.3s ease;
  animation: slideUp 0.4s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.preview-header {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.preview-header h4 {
  color: #2c3e50;
  margin: 0;
  font-size: 1.1rem;
}

.preview-total {
  font-size: 1.5rem;
  font-weight: 800;
  color: #27ae60;
}

.btn-go-cart {
  max-width: 1400px;
  margin: 0 auto;
  display: block;
  text-align: center;
  min-height: 52px;
  padding: 1rem 1.2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 700;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-go-cart:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

/* Mobile Responsive */
@media (max-width: 1024px) {
  .menu-layout {
    grid-template-columns: 1fr;
  }

  .sections-nav {
    top: 0.75rem;
    z-index: 30;
    max-height: none;
    overflow: visible;
    padding: 0.8rem;
  }

  .nav-header {
    display: none;
  }

  .nav-sections {
    display: flex;
    flex-direction: row;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.2rem;
    scrollbar-width: none;
  }

  .nav-sections::-webkit-scrollbar {
    display: none;
  }

  .nav-section-btn {
    flex: 0 0 auto;
    min-width: max-content;
  }
}

@media (max-width: 768px) {
  .header {
    padding: max(1.5rem, calc(env(safe-area-inset-top) + 0.75rem)) 1rem 2.25rem;
  }

  .restaurant-header {
    padding: 1.5rem;
    text-align: left;
  }

  .restaurant-info {
    justify-content: flex-start;
  }

  .restaurant-stats {
    justify-content: flex-start;
  }

  .menu-content {
    padding: 1.25rem 1rem 0;
    border-radius: 26px 26px 0 0;
  }

  .section-header {
    padding: 0.9rem 1rem;
  }

  .products-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .product-image {
    height: 170px;
  }

  .product-card:hover {
    transform: none;
  }

  .product-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 0.85rem;
  }

  .price {
    font-size: 1.35rem;
  }

  .btn-add {
    width: 100%;
    min-height: 48px;
  }

  .preview-header {
    margin-bottom: 0.75rem;
  }

  .preview-total {
    font-size: 1.2rem;
  }
}

@media (max-width: 480px) {
  .restaurant-header {
    padding: 1.5rem;
    border-radius: 16px;
  }

  .restaurant-header h1 {
    font-size: 1.8rem;
  }

  .stat-chip {
    font-size: 0.84rem;
  }

  .filters-meta {
    gap: 0.5rem;
    font-size: 0.85rem;
  }

  .nav-section-btn {
    padding: 0.65rem 0.85rem;
  }

  .product-info {
    padding: 1rem;
  }
}
</style>
