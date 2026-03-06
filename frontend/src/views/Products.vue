<template>
  <div class="products-container">
    <div class="page-header">
      <h2>Productos</h2>
      <button @click="showAddForm = true" class="btn btn-primary">+ Nuevo Producto</button>
    </div>

    <div v-if="showAddForm" class="form-modal">
      <div class="form-content">
        <h3>Nuevo Producto</h3>
        <form @submit.prevent="addProduct">
          <input v-model="newProduct.name" type="text" placeholder="Nombre" required />
          <textarea v-model="newProduct.description" placeholder="Descripción"></textarea>
          <input v-model="newProduct.price" type="number" placeholder="Precio" step="0.01" required />
          <input v-model="newProduct.category_id" type="number" placeholder="ID de Categoría" />
          <div class="form-actions">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" @click="showAddForm = false" class="btn btn-secondary">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="productsStore.loading" class="loading">Cargando productos...</div>

    <div class="products-grid">
      <div v-for="product in productsStore.products" :key="product.id" class="product-card">
        <h3>{{ product.name }}</h3>
        <p class="description">{{ product.description }}</p>
        <p class="price">${{ formatPrice(product.price) }}</p>
        <div class="card-actions">
          <button @click="deleteProduct(product.id)" class="btn btn-small btn-danger">Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="productsStore.products.length === 0 && !productsStore.loading" class="empty-state">
      <p>No hay productos registrados. ¡Crea uno para empezar!</p>
    </div>
  </div>
</template>

<script setup>
import { useProductsStore } from '../stores/products'
import { ref, onMounted } from 'vue'

const productsStore = useProductsStore()
const showAddForm = ref(false)
const newProduct = ref({
  name: '',
  description: '',
  price: '',
  category_id: ''
})

const addProduct = async () => {
  try {
    const success = await productsStore.createProduct(newProduct.value)
    if (success) {
      newProduct.value = { name: '', description: '', price: '', category_id: '' }
      showAddForm.value = false
      alert('Producto agregado exitosamente')
    } else {
      alert('Error: ' + productsStore.error)
    }
  } catch (error) {
    alert('Error al agregar producto')
  }
}

const deleteProduct = async (id) => {
  if (confirm('¿Eliminar este producto?')) {
    const success = await productsStore.deleteProduct(id)
    if (success) {
      alert('Producto eliminado')
    } else {
      alert('Error: ' + productsStore.error)
    }
  }
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}

onMounted(() => {
  productsStore.fetchProducts()
})
</script>

<style scoped>
.products-container {
  animation: fadeIn 0.5s ease-in;
  padding: 2rem;
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
  padding: 0.4rem 0.8rem;
  font-size: 0.9rem;
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
  max-width: 500px;
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

.form-content input,
.form-content textarea {
  padding: 0.75rem;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 1rem;
  font-family: inherit;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.loading {
  text-align: center;
  color: white;
  padding: 2rem;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.product-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

.product-card h3 {
  color: #667eea;
  margin-bottom: 0.5rem;
}

.description {
  color: #666;
  margin-bottom: 1rem;
  font-size: 0.9rem;
  line-height: 1.5;
}

.price {
  font-size: 1.5rem;
  font-weight: bold;
  color: #48bb78;
  margin-bottom: 1rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.empty-state {
  text-align: center;
  color: white;
  padding: 3rem;
  font-size: 1.1rem;
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
