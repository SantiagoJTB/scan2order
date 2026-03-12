<template>
  <div v-if="canAccessAdmin" class="products-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>

    <div class="header">
      <h1>📋 Gestión de Catálogos y Productos</h1>
      <button v-if="selectedRestaurantId" @click="backToList" class="btn-back">← Volver a lista</button>
    </div>

    <!-- Restaurant List View -->
    <div v-if="!selectedRestaurantId" class="restaurants-stats">
      <div v-if="isLoadingStats" class="loading">Cargando restaurantes...</div>
      <div v-else-if="restaurantsStats.length === 0" class="empty-state">
        <p>No hay restaurantes disponibles</p>
      </div>
      <div v-else class="stats-grid">
        <div v-for="restaurant in restaurantsStats" :key="restaurant.id" class="restaurant-card" @click="selectRestaurant(restaurant)">
          <div class="restaurant-card-header">
            <h3>{{ restaurant.name }}</h3>
            <p class="restaurant-info">📍 {{ restaurant.address }} | ☎️ {{ restaurant.phone }}</p>
          </div>
          <div class="restaurant-stats">
            <div class="stat-item">
              <span class="stat-label">Menús/Catálogos</span>
              <span class="stat-value">{{ restaurant.menus_count }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Productos Totales</span>
              <span class="stat-value">{{ restaurant.total_products }}</span>
            </div>
          </div>
          <div v-if="restaurant.products_per_menu.length > 0" class="products-per-menu">
            <h4>Productos por Menú:</h4>
            <ul>
              <li v-for="(menu, idx) in restaurant.products_per_menu" :key="idx">
                <strong>{{ menu.menu_name }}:</strong> {{ menu.products_count }} productos
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Restaurant Detail View -->
    <div v-if="selectedRestaurantId" class="restaurant-detail">
      <div class="restaurant-context">
        📍 Restaurante: <strong>{{ selectedRestaurantName }}</strong>
      </div>

      <div class="tools-row">
        <input
          v-model="searchQuery"
          type="text"
          class="search-input"
          placeholder="Buscar en catálogos, secciones y productos..."
        />
        <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''">Limpiar</button>
      </div>

      <div v-if="isLoading" class="loading">Cargando catálogos...</div>
      <div v-else class="content">
      <div class="catalogs-list">
        <div v-if="catalogs.length === 0" class="empty-section">
          <p>No hay catálogos creados</p>
          <button class="btn-primary" @click="openCatalogForm()">+ Crear primer catálogo</button>
        </div>

        <div v-else-if="filteredCatalogs.length === 0" class="empty-section">
          <p>No hay resultados para la búsqueda</p>
        </div>

        <div v-for="catalog in filteredCatalogs" :key="catalog.id" class="catalog-card">
          <div class="catalog-header">
            <div class="catalog-info">
              <h3>{{ catalog.name }}</h3>
              <p>{{ catalog.description || 'Sin descripción' }}</p>
            </div>
            <div class="catalog-actions">
              <button class="btn-icon" @click="openPriceAdjustModal(catalog)" title="Ajustar precios por porcentaje">💹</button>
              <button class="btn-icon" @click="editCatalog(catalog)" title="Editar">✏️</button>
              <button class="btn-icon btn-danger" @click="deleteCatalog(catalog)" title="Eliminar">🗑️</button>
            </div>
          </div>

          <div class="sections-container">
            <div v-if="catalog.sections.length === 0" class="empty-subsection">
              <small>Sin secciones</small>
              <button class="btn-small" @click="openSectionForm(catalog)">+ Sección</button>
            </div>

            <div v-for="section in catalog.sections" :key="section.id" class="section-item">
              <div class="section-header">
                <h4>{{ section.name }}</h4>
                <div class="section-actions">
                  <button class="btn-icon" @click="editSection(catalog, section)" title="Editar">✏️</button>
                  <button class="btn-icon btn-danger" @click="deleteSection(catalog, section)" title="Eliminar">🗑️</button>
                </div>
              </div>

              <div class="products-list">
                <div v-if="section.products.length === 0" class="empty-products">
                  <small>Sin productos</small>
                  <button class="btn-small" @click="openProductForm(catalog, section)">+ Producto</button>
                </div>

                <div v-for="product in section.products" :key="product.id" class="product-item">
                  <img v-if="product.image" :src="`/storage/${product.image}`" alt="" class="product-thumbnail" />
                  <div v-else class="product-no-image">📦</div>
                  <div class="product-name">{{ product.name }}</div>
                  <div class="product-price">${{ product.price }}</div>
                  <div class="product-actions">
                    <button class="btn-icon-small" @click="editProduct(catalog, section, product)" title="Editar">✏️</button>
                    <button class="btn-icon-small btn-danger" @click="deleteProduct(catalog, section, product)" title="Eliminar">🗑️</button>
                  </div>
                </div>

                <button class="btn-add-product" @click="openProductForm(catalog, section)">+ Agregar producto</button>
              </div>
            </div>

            <button class="btn-add-section" @click="openSectionForm(catalog)">+ Agregar sección</button>
          </div>
        </div>

        <button class="btn-primary btn-large" @click="openCatalogForm()">+ Crear nuevo catálogo</button>
      </div>
    </div>
    </div>
  </div>

  <!-- Catalog Modal -->
  <div v-if="showCatalogModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ editingCatalog ? 'Editar catálogo' : 'Nuevo catálogo' }}</h2>
        <button @click="closeCatalogModal" class="btn-close">×</button>
      </div>
      <form @submit.prevent="saveCatalog" class="modal-body">
        <div class="form-group">
          <label>Nombre:</label>
          <input v-model="catalogForm.name" type="text" required placeholder="Desayuno, Almuerzo, etc." />
        </div>
        <div class="form-group">
          <label>Descripción:</label>
          <textarea v-model="catalogForm.description" placeholder="Descripción opcional"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" @click="closeCatalogModal" class="btn-cancel">Cancelar</button>
          <button type="submit" class="btn-save">{{ editingCatalog ? 'Actualizar' : 'Crear' }}</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Section Modal -->
  <div v-if="showSectionModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ editingSection ? 'Editar sección' : 'Nueva sección' }}</h2>
        <button @click="closeSectionModal" class="btn-close">×</button>
      </div>
      <form @submit.prevent="saveSection" class="modal-body">
        <div class="form-group">
          <label>Nombre:</label>
          <input v-model="sectionForm.name" type="text" required placeholder="Bebidas, Postres, etc." />
        </div>
        <div class="form-group">
          <label>Descripción:</label>
          <textarea v-model="sectionForm.description" placeholder="Descripción opcional"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" @click="closeSectionModal" class="btn-cancel">Cancelar</button>
          <button type="submit" class="btn-save">{{ editingSection ? 'Actualizar' : 'Crear' }}</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Product Modal -->
  <div v-if="showProductModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ editingProduct ? 'Editar producto' : 'Nuevo producto' }}</h2>
        <button @click="closeProductModal" class="btn-close">×</button>
      </div>
      <form @submit.prevent="saveProduct" class="modal-body">
        <div class="form-group">
          <label>Nombre:</label>
          <input v-model="productForm.name" type="text" required placeholder="Nombre del producto" />
        </div>
        <div class="form-group">
          <label>Descripción:</label>
          <textarea v-model="productForm.description" placeholder="Descripción del producto"></textarea>
        </div>
        <div class="form-group">
          <label>Precio:</label>
          <input v-model.number="productForm.price" type="number" step="0.01" required placeholder="0.00" />
        </div>
        <div class="form-group">
          <label>Imagen (opcional):</label>
          <input type="file" @change="handleImageChange" accept="image/*" class="file-input" />
          <small class="help-text">Formatos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
          
          <div v-if="productImagePreview" class="image-preview">
            <img :src="productImagePreview" alt="Vista previa" />
            <button type="button" @click="removeImage" class="btn-remove-image">✕ Eliminar imagen</button>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" @click="closeProductModal" class="btn-cancel">Cancelar</button>
          <button type="submit" class="btn-save">{{ editingProduct ? 'Actualizar' : 'Crear' }}</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Catalog Price Adjust Modal -->
  <div v-if="showPriceAdjustModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>Ajustar precios por porcentaje</h2>
        <button @click="closePriceAdjustModal" class="btn-close">×</button>
      </div>
      <form @submit.prevent="applyCatalogPriceAdjustment" class="modal-body">
        <p class="price-adjust-context">
          Catálogo: <strong>{{ selectedCatalogForPrice?.name }}</strong>
        </p>

        <div class="form-group">
          <label>Acción:</label>
          <select v-model="priceAdjustForm.mode">
            <option value="increase">Aumentar</option>
            <option value="decrease">Disminuir</option>
          </select>
        </div>

        <div class="form-group">
          <label>Porcentaje (%):</label>
          <input v-model.number="priceAdjustForm.percent" type="number" min="0.01" step="0.01" required placeholder="Ej: 10" />
        </div>

        <div class="form-actions">
          <button type="button" @click="closePriceAdjustModal" class="btn-cancel">Cancelar</button>
          <button type="submit" class="btn-save" :disabled="isApplyingPriceAdjust">
            {{ isApplyingPriceAdjust ? 'Aplicando...' : 'Aplicar' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <div v-else-if="auth.initialized && !checkingAccess && !canAccessAdmin" class="products-container">
    <div class="content">
      <div class="info-box">
        <h2>⛔ Acceso denegado</h2>
        <p>Solo Admin, Superadmin, Caja y Cocina pueden acceder a esta sección.</p>
      </div>
    </div>
  </div>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin', 'staff']))
const checkingAccess = ref(true)
const selectedRestaurantId = ref(null)
const selectedRestaurantName = ref('')
const catalogs = ref([])
const searchQuery = ref('')
const isLoading = ref(false)
const isLoadingStats = ref(false)
const restaurantsStats = ref([])
const toast = ref({ show: false, type: 'success', message: '' })

const showCatalogModal = ref(false)
const editingCatalog = ref(null)
const catalogForm = ref({ name: '', description: '', active: true, order: 0 })

const showSectionModal = ref(false)
const editingSection = ref(null)
const selectedCatalog = ref(null)
const sectionForm = ref({ name: '', description: '', active: true, order: 0 })

const showProductModal = ref(false)
const editingProduct = ref(null)
const selectedSection = ref(null)
const productForm = ref({ name: '', description: '', price: 0, active: true })
const productImageFile = ref(null)
const productImagePreview = ref(null)
const removeProductImage = ref(false)
const showPriceAdjustModal = ref(false)
const isApplyingPriceAdjust = ref(false)
const selectedCatalogForPrice = ref(null)
const priceAdjustForm = ref({ mode: 'increase', percent: 0 })

const filteredCatalogs = computed(() => {
  const term = (searchQuery.value || '').trim().toLowerCase()
  if (!term) return catalogs.value

  const contains = (value) => String(value || '').toLowerCase().includes(term)

  return catalogs.value
    .map((catalog) => {
      const catalogMatch = contains(catalog.name) || contains(catalog.description)

      if (catalogMatch) {
        return { ...catalog, sections: catalog.sections || [] }
      }

      const filteredSections = (catalog.sections || [])
        .map((section) => {
          const sectionMatch = contains(section.name) || contains(section.description)
          if (sectionMatch) {
            return { ...section, products: section.products || [] }
          }

          const filteredProducts = (section.products || []).filter(
            (product) => contains(product.name) || contains(product.description)
          )

          if (filteredProducts.length > 0) {
            return { ...section, products: filteredProducts }
          }

          return null
        })
        .filter(Boolean)

      if (filteredSections.length > 0) {
        return { ...catalog, sections: filteredSections }
      }

      return null
    })
    .filter(Boolean)
})

onMounted(async () => {
  if (!auth.initialized) {
    await auth.initFromStorage()
  }

  if (!canAccessAdmin.value) {
    checkingAccess.value = false
    return
  }

  selectedRestaurantId.value = route.query.restaurantId
  selectedRestaurantName.value = route.query.restaurantName || ''

  if (selectedRestaurantId.value) {
    await fetchCatalogs()
  } else {
    await fetchRestaurantsStats()
  }

  checkingAccess.value = false
})

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

async function fetchRestaurantsStats() {
  isLoadingStats.value = true
  try {
    const response = await fetch('/api/restaurants/stats', {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) throw new Error('Error al cargar estadísticas')
    restaurantsStats.value = await response.json()
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isLoadingStats.value = false
  }
}

function selectRestaurant(restaurant) {
  selectedRestaurantId.value = restaurant.id
  selectedRestaurantName.value = restaurant.name
  router.push({ 
    path: '/admin/products', 
    query: { 
      restaurantId: restaurant.id, 
      restaurantName: restaurant.name 
    } 
  })
  fetchCatalogs()
}

function backToList() {
  selectedRestaurantId.value = null
  selectedRestaurantName.value = ''
  searchQuery.value = ''
  catalogs.value = []
  router.push({ path: '/admin/products' })
  fetchRestaurantsStats()
}

async function fetchCatalogs() {
  if (!selectedRestaurantId.value) return
  isLoading.value = true
  try {
    const response = await fetch(`/api/restaurants/${selectedRestaurantId.value}/catalogs`, {
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Accept': 'application/json'
      }
    })

    if (response.status === 401 || response.status === 403) {
      showToast('No tienes permiso para acceder a este restaurante', 'error')
      return
    }

    if (!response.ok) throw new Error('Error al cargar catálogos')
    catalogs.value = await response.json()
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isLoading.value = false
  }
}

function openCatalogForm(catalog = null) {
  if (catalog && catalog.id) {
    editingCatalog.value = catalog
    catalogForm.value = { 
      name: catalog.name, 
      description: catalog.description || '',
      active: catalog.active !== undefined ? catalog.active : true,
      order: catalog.order || 0
    }
  } else {
    editingCatalog.value = null
    catalogForm.value = { name: '', description: '', active: true, order: 0 }
  }
  showCatalogModal.value = true
}

function closeCatalogModal() {
  showCatalogModal.value = false
  editingCatalog.value = null
}

async function saveCatalog() {
  try {
    if (!selectedRestaurantId.value) {
      throw new Error('Selecciona un restaurante antes de guardar el catálogo')
    }

    const isEdit = Boolean(editingCatalog.value && editingCatalog.value.id)
    const url = isEdit
      ? `/api/restaurants/${selectedRestaurantId.value}/catalogs/${editingCatalog.value.id}`
      : `/api/restaurants/${selectedRestaurantId.value}/catalogs`
    const method = isEdit ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(catalogForm.value)
    })

    if (!response.ok) {
      let message = 'Error al guardar catálogo'
      try {
        const data = await response.json()
        message = data?.message || message
      } catch {
        // ignore json parsing errors
      }
      throw new Error(message)
    }

    const wasEdit = isEdit
    closeCatalogModal()
    await fetchCatalogs()
    showToast(wasEdit ? 'Catálogo actualizado' : 'Catálogo creado')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

async function deleteCatalog(catalog) {
  if (!confirm('¿Eliminar este catálogo y todos sus contenidos?')) return
  try {
    const response = await fetch(`/api/restaurants/${selectedRestaurantId.value}/catalogs/${catalog.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${auth.token}` }
    })
    if (!response.ok) throw new Error('Error al eliminar')
    await fetchCatalogs()
    showToast('Catálogo eliminado')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function openSectionForm(catalog, section = null) {
  selectedCatalog.value = catalog
  if (section) {
    editingSection.value = section
    sectionForm.value = { 
      name: section.name, 
      description: section.description || '',
      active: section.active !== undefined ? section.active : true,
      order: section.order || 0
    }
  } else {
    editingSection.value = null
    sectionForm.value = { name: '', description: '', active: true, order: 0 }
  }
  showSectionModal.value = true
}

function closeSectionModal() {
  showSectionModal.value = false
  editingSection.value = null
  selectedCatalog.value = null
}

async function saveSection() {
  try {
    const url = editingSection.value
      ? `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections/${editingSection.value.id}`
      : `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections`
    const method = editingSection.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(sectionForm.value)
    })

    if (!response.ok) throw new Error('Error al guardar sección')
    closeSectionModal()
    await fetchCatalogs()
    showToast(editingSection.value ? 'Sección actualizada' : 'Sección creada')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function editSection(catalog, section) {
  openSectionForm(catalog, section)
}

async function deleteSection(catalog, section) {
  if (!confirm('¿Eliminar esta sección?')) return
  try {
    const response = await fetch(`/api/restaurants/${selectedRestaurantId.value}/catalogs/${catalog.id}/sections/${section.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${auth.token}` }
    })
    if (!response.ok) throw new Error('Error al eliminar')
    await fetchCatalogs()
    showToast('Sección eliminada')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function openProductForm(catalog, section, product = null) {
  selectedCatalog.value = catalog
  selectedSection.value = section
  productImageFile.value = null
  productImagePreview.value = null
  removeProductImage.value = false
  if (product) {
    editingProduct.value = product
    productForm.value = { 
      name: product.name, 
      description: product.description || '', 
      price: product.price || 0,
      active: product.active !== undefined ? product.active : true
    }
    // Set preview to existing image if available
    if (product.image) {
      productImagePreview.value = `/storage/${product.image}`
    }
  } else {
    editingProduct.value = null
    productForm.value = { name: '', description: '', price: 0, active: true }
  }
  showProductModal.value = true
}

function closeProductModal() {
  showProductModal.value = false
  editingProduct.value = null
  selectedSection.value = null
  productImageFile.value = null
  productImagePreview.value = null
  removeProductImage.value = false
}

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    productImageFile.value = file
    removeProductImage.value = false
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      productImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeImage() {
  productImageFile.value = null
  productImagePreview.value = null
  removeProductImage.value = true
}

async function saveProduct() {
  try {
    const url = editingProduct.value
      ? `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections/${selectedSection.value.id}/products/${editingProduct.value.id}`
      : `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections/${selectedSection.value.id}/products`
    const method = editingProduct.value ? 'PUT' : 'POST'

    // Use FormData for file upload
    const formData = new FormData()
    formData.append('name', productForm.value.name)
    formData.append('description', productForm.value.description || '')
    formData.append('price', productForm.value.price)
    formData.append('active', productForm.value.active ? '1' : '0')
    
    if (productImageFile.value) {
      formData.append('image', productImageFile.value)
    }
    
    if (removeProductImage.value) {
      formData.append('remove_image', '1')
    }

    // For PUT requests, we need to use POST with _method override
    if (method === 'PUT') {
      formData.append('_method', 'PUT')
    }

    const response = await fetch(url, {
      method: method === 'PUT' ? 'POST' : method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        // Don't set Content-Type, let browser set it with boundary for FormData
      },
      body: formData
    })

    if (!response.ok) throw new Error('Error al guardar producto')
    closeProductModal()
    await fetchCatalogs()
    showToast(editingProduct.value ? 'Producto actualizado' : 'Producto creado')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function editProduct(catalog, section, product) {
  openProductForm(catalog, section, product)
}

async function deleteProduct(catalog, section, product) {
  if (!confirm('¿Eliminar este producto?')) return
  try {
    const response = await fetch(`/api/restaurants/${selectedRestaurantId.value}/catalogs/${catalog.id}/sections/${section.id}/products/${product.id}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${auth.token}` }
    })
    if (!response.ok) throw new Error('Error al eliminar')
    await fetchCatalogs()
    showToast('Producto eliminado')
  } catch (err) {
    showToast(err.message, 'error')
  }
}

function editCatalog(catalog) {
  openCatalogForm(catalog)
}

function openPriceAdjustModal(catalog) {
  selectedCatalogForPrice.value = catalog
  priceAdjustForm.value = { mode: 'increase', percent: 0 }
  showPriceAdjustModal.value = true
}

function closePriceAdjustModal() {
  showPriceAdjustModal.value = false
  selectedCatalogForPrice.value = null
}

async function applyCatalogPriceAdjustment() {
  const catalog = selectedCatalogForPrice.value
  const percent = Number(priceAdjustForm.value.percent)

  if (!catalog) {
    showToast('Catálogo no válido', 'error')
    return
  }

  if (!Number.isFinite(percent) || percent <= 0) {
    showToast('El porcentaje debe ser mayor que 0', 'error')
    return
  }

  const factor = priceAdjustForm.value.mode === 'decrease'
    ? 1 - (percent / 100)
    : 1 + (percent / 100)

  if (factor <= 0) {
    showToast('El porcentaje de disminución es demasiado alto', 'error')
    return
  }

  const productsToUpdate = (catalog.sections || []).flatMap((section) =>
    (section.products || []).map((product) => ({ sectionId: section.id, product }))
  )

  if (productsToUpdate.length === 0) {
    showToast('No hay productos en este catálogo', 'error')
    return
  }

  isApplyingPriceAdjust.value = true

  try {
    for (const item of productsToUpdate) {
      const currentPrice = Number(item.product.price) || 0
      const adjustedPrice = Math.max(0, Number((currentPrice * factor).toFixed(2)))

      const response = await fetch(
        `/api/restaurants/${selectedRestaurantId.value}/catalogs/${catalog.id}/sections/${item.sectionId}/products/${item.product.id}`,
        {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${auth.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ price: adjustedPrice })
        }
      )

      if (!response.ok) {
        throw new Error(`Error al actualizar precio en producto ${item.product.name}`)
      }
    }

    closePriceAdjustModal()
    await fetchCatalogs()
    const actionText = priceAdjustForm.value.mode === 'decrease' ? 'disminuidos' : 'aumentados'
    showToast(`Precios ${actionText} ${percent}% en ${productsToUpdate.length} productos`)
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isApplyingPriceAdjust.value = false
  }
}
</script>

<style scoped>
.products-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 0;
}

.header {
  margin-bottom: 2rem;
  color: #2c3e50;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h1 {
  font-size: 2rem;
  margin: 0;
}

.btn-back {
  background: #95a5a6;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.2s;
}

.btn-back:hover {
  background: #7f8c8d;
}

/* Restaurant Stats View */
.restaurants-stats {
  padding: 1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.restaurant-card {
  background: white;
  border: 2px solid #e8eef3;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.3s ease;
}

.restaurant-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(52, 152, 219, 0.2);
  border-color: #3498db;
}

.restaurant-card-header h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.4rem;
}

.restaurant-info {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin: 0;
}

.restaurant-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin: 1.5rem 0;
  padding: 1rem 0;
  border-top: 1px solid #ecf0f1;
  border-bottom: 1px solid #ecf0f1;
}

.stat-item {
  display: flex;
  flex-direction: column;
  text-align: center;
}

.stat-label {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 0.3rem;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: bold;
  color: #3498db;
}

.products-per-menu {
  margin-top: 1rem;
}

.products-per-menu h4 {
  font-size: 0.95rem;
  color: #2c3e50;
  margin: 0 0 0.5rem 0;
}

.products-per-menu ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.products-per-menu li {
  padding: 0.3rem 0;
  font-size: 0.9rem;
  color: #34495e;
}

.products-per-menu li strong {
  color: #2c3e50;
}

/* Restaurant Detail View */
.restaurant-detail {
  /* Inherits existing styles */
}

.restaurant-context {
  margin-bottom: 1.5rem;
  background: #ecf6ff;
  color: #2c3e50;
  border-radius: 8px;
  padding: 1rem;
  border-left: 4px solid #3498db;
}

.tools-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.search-input {
  flex: 1;
  padding: 0.7rem 0.85rem;
  border: 1px solid #bdc3c7;
  border-radius: 6px;
  font-size: 0.95rem;
}

.btn-clear-search {
  background: #ecf0f1;
  color: #2c3e50;
  border: none;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}

.btn-clear-search:hover {
  background: #d5dbdb;
}

.loading, .empty-state {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  font-size: 1.1rem;
}

.content {
  background: white;
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.catalogs-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.catalog-card {
  border: 2px solid #e8eef3;
  border-radius: 8px;
  padding: 1rem;
  background: #f8fafb;
}

.catalog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.catalog-info h3 {
  margin: 0 0 0.25rem;
  color: #2c3e50;
  font-size: 1.2rem;
}

.catalog-info p {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.catalog-actions {
  display: flex;
  gap: 0.5rem;
}

.sections-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-left: 1rem;
  border-left: 3px solid #667eea;
}

.section-item {
  background: white;
  border-radius: 6px;
  padding: 0.75rem;
  border: 1px solid #ecf0f1;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.section-header h4 {
  margin: 0;
  color: #667eea;
  font-size: 1rem;
}

.section-actions {
  display: flex;
  gap: 0.5rem;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-left: 1rem;
  border-left: 2px solid #f39c12;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fffaf0;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #f5e6d3;
  gap: 0.75rem;
}

.product-thumbnail {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e0e0e0;
}

.product-no-image {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 4px;
  font-size: 1.2rem;
}

.product-name {
  font-weight: 500;
  color: #2c3e50;
  flex: 1;
}

.product-price {
  color: #27ae60;
  font-weight: 600;
  margin: 0 0.5rem;
  min-width: 60px;
}

.product-actions {
  display: flex;
  gap: 0.25rem;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0.3rem 0.5rem;
  cursor: pointer;
  font-size: 0.9rem;
  border-radius: 3px;
  transition: background 0.3s;
}

.btn-icon:hover {
  background: rgba(0, 0, 0, 0.1);
}

.btn-icon-small {
  background: none;
  border: none;
  padding: 0.2rem 0.3rem;
  cursor: pointer;
  font-size: 0.8rem;
  border-radius: 2px;
  transition: background 0.3s;
}

.btn-icon-small:hover {
  background: rgba(0, 0, 0, 0.1);
}

.btn-danger {
  color: #e74c3c;
}

.btn-small, .btn-add-product, .btn-add-section {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
  transition: background 0.3s;
  margin-top: 0.5rem;
}

.btn-small:hover, .btn-add-product:hover, .btn-add-section:hover {
  background: #5a67d8;
}

.btn-primary {
  background: #27ae60;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.3s;
}

.btn-primary:hover {
  background: #229954;
}

.btn-large {
  align-self: center;
  margin-top: 2rem;
}

.empty-section, .empty-subsection, .empty-products {
  text-align: center;
  color: #95a5a6;
  padding: 1rem 0.5rem;
  font-size: 0.9rem;
}

/* Modal Styles */
.modal-overlay {
  display: flex;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #ecf0f1;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #7f8c8d;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #bdc3c7;
  border-radius: 4px;
  font-family: inherit;
  font-size: 1rem;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.file-input {
  padding: 0.5rem 0 !important;
  border: none !important;
  cursor: pointer;
}

.help-text {
  display: block;
  margin-top: 0.25rem;
  color: #7f8c8d;
  font-size: 0.85rem;
}

.image-preview {
  margin-top: 1rem;
  text-align: center;
}

.image-preview img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}

.btn-remove-image {
  display: block;
  margin: 0.75rem auto 0;
  background: #e74c3c;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.3s;
}

.btn-remove-image:hover {
  background: #c0392b;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-cancel {
  background: #ecf0f1;
  color: #2c3e50;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-cancel:hover {
  background: #d5dbdb;
}

.btn-save {
  background: #27ae60;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.3s;
}

.btn-save:hover {
  background: #229954;
}

.price-adjust-context {
  margin: 0 0 1rem;
  color: #2c3e50;
  background: #f5f8ff;
  border: 1px solid #dbe6ff;
  border-radius: 6px;
  padding: 0.75rem;
}

.toast {
  position: fixed;
  top: 1rem;
  right: 1rem;
  padding: 1rem 1.5rem;
  border-radius: 6px;
  color: white;
  font-weight: 600;
  z-index: 2000;
  animation: slideIn 0.3s ease;
}

.toast-success {
  background: #27ae60;
}

.toast-error {
  background: #e74c3c;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
  }
  to {
    transform: translateX(0);
  }
}

.info-box {
  text-align: center;
  padding: 2rem;
}

.info-box h2 {
  color: #e74c3c;
  font-size: 1.8rem;
}

.info-box p {
  color: #7f8c8d;
}

@media (max-width: 768px) {
  .products-container {
    padding: 1rem;
  }

  .catalog-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .section-header, .product-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .product-price {
    display: none;
  }

  .modal {
    width: 95%;
    max-height: 95vh;
  }
}
</style>
