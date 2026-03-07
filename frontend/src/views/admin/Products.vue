<template>
  <div v-if="canAccessAdmin" class="products-container">
    <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">{{ toast.message }}</div>

    <div class="header">
      <h1>📋 Gestión de Catálogos y Productos</h1>
    </div>

    <div v-if="selectedRestaurantId" class="restaurant-context">
      📍 Restaurante: <strong>{{ selectedRestaurantName }}</strong>
    </div>

    <div v-if="isLoading" class="loading">Cargando catálogos...</div>
    <div v-else-if="!selectedRestaurantId" class="empty-state">
      <p>⚠️ Selecciona primero un restaurante desde la gestión de restaurantes</p>
    </div>
    <div v-else class="content">
      <div class="catalogs-list">
        <div v-if="catalogs.length === 0" class="empty-section">
          <p>No hay catálogos creados</p>
          <button class="btn-primary" @click="openCatalogForm">+ Crear primer catálogo</button>
        </div>

        <div v-for="catalog in catalogs" :key="catalog.id" class="catalog-card">
          <div class="catalog-header">
            <div class="catalog-info">
              <h3>{{ catalog.name }}</h3>
              <p>{{ catalog.description || 'Sin descripción' }}</p>
            </div>
            <div class="catalog-actions">
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

        <button class="btn-primary btn-large" @click="openCatalogForm">+ Crear nuevo catálogo</button>
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
        <div class="form-actions">
          <button type="button" @click="closeProductModal" class="btn-cancel">Cancelar</button>
          <button type="submit" class="btn-save">{{ editingProduct ? 'Actualizar' : 'Crear' }}</button>
        </div>
      </form>
    </div>
  </div>

  <div v-else class="products-container">
    <div class="content">
      <div class="info-box">
        <h2>⛔ Acceso denegado</h2>
        <p>Solo Admin y Superadmin pueden acceder a esta sección.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const route = useRoute()

const canAccessAdmin = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
const selectedRestaurantId = ref(null)
const selectedRestaurantName = ref('')
const catalogs = ref([])
const isLoading = ref(false)
const toast = ref({ show: false, type: 'success', message: '' })

const showCatalogModal = ref(false)
const editingCatalog = ref(null)
const catalogForm = ref({ name: '', description: '' })

const showSectionModal = ref(false)
const editingSection = ref(null)
const selectedCatalog = ref(null)
const sectionForm = ref({ name: '', description: '' })

const showProductModal = ref(false)
const editingProduct = ref(null)
const selectedSection = ref(null)
const productForm = ref({ name: '', description: '', price: 0 })

onMounted(() => {
  selectedRestaurantId.value = route.query.restaurantId
  selectedRestaurantName.value = route.query.restaurantName || ''
  if (selectedRestaurantId.value) {
    fetchCatalogs()
  }
})

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 2500)
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
    if (!response.ok) throw new Error('Error al cargar catálogos')
    catalogs.value = await response.json()
  } catch (err) {
    showToast(err.message, 'error')
  } finally {
    isLoading.value = false
  }
}

function openCatalogForm(catalog = null) {
  if (catalog) {
    editingCatalog.value = catalog
    catalogForm.value = { name: catalog.name, description: catalog.description }
  } else {
    editingCatalog.value = null
    catalogForm.value = { name: '', description: '' }
  }
  showCatalogModal.value = true
}

function closeCatalogModal() {
  showCatalogModal.value = false
  editingCatalog.value = null
}

async function saveCatalog() {
  try {
    const url = editingCatalog.value
      ? `/api/restaurants/${selectedRestaurantId.value}/catalogs/${editingCatalog.value.id}`
      : `/api/restaurants/${selectedRestaurantId.value}/catalogs`
    const method = editingCatalog.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(catalogForm.value)
    })

    if (!response.ok) throw new Error('Error al guardar catálogo')
    closeCatalogModal()
    await fetchCatalogs()
    showToast(editingCatalog.value ? 'Catálogo actualizado' : 'Catálogo creado')
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
    sectionForm.value = { name: section.name, description: section.description }
  } else {
    editingSection.value = null
    sectionForm.value = { name: '', description: '' }
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
  if (product) {
    editingProduct.value = product
    productForm.value = { name: product.name, description: product.description, price: product.price }
  } else {
    editingProduct.value = null
    productForm.value = { name: '', description: '', price: 0 }
  }
  showProductModal.value = true
}

function closeProductModal() {
  showProductModal.value = false
  editingProduct.value = null
  selectedSection.value = null
}

async function saveProduct() {
  try {
    const url = editingProduct.value
      ? `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections/${selectedSection.value.id}/products/${editingProduct.value.id}`
      : `/api/restaurants/${selectedRestaurantId.value}/catalogs/${selectedCatalog.value.id}/sections/${selectedSection.value.id}/products`
    const method = editingProduct.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(productForm.value)
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
}

.header h1 {
  font-size: 2rem;
  margin: 0;
}

.restaurant-context {
  margin-bottom: 1.5rem;
  background: #ecf6ff;
  color: #2c3e50;
  border-radius: 8px;
  padding: 1rem;
  border-left: 4px solid #3498db;
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
.form-group textarea {
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
