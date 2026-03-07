<template>
  <div class="checkout-container">
    <div class="header">
      <h1>Pago</h1>
      <p>Completa tu compra</p>
    </div>

    <div class="checkout-content">
      <div class="checkout-form">
        <div class="section">
          <h2>Información de envío</h2>
          <div class="form-group">
            <label for="address">Dirección:</label>
            <input
              v-model="formData.address"
              type="text"
              id="address"
              placeholder="Calle y número"
              required
            />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="city">Ciudad:</label>
              <input
                v-model="formData.city"
                type="text"
                id="city"
                placeholder="Ciudad"
                required
              />
            </div>
            <div class="form-group">
              <label for="postalcode">Código postal:</label>
              <input
                v-model="formData.postalcode"
                type="text"
                id="postalcode"
                placeholder="Código postal"
                required
              />
            </div>
          </div>
        </div>

        <div class="section">
          <h2>Método de pago</h2>
          <div class="payment-methods">
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="card" required />
              <span>Tarjeta de crédito/débito</span>
            </label>
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="cash" />
              <span>Pago en efectivo al recibir</span>
            </label>
          </div>

          <!-- Card payment form (simulated) -->
          <div v-if="formData.paymentMethod === 'card'" class="card-form">
            <div class="form-group">
              <label for="cardname">Nombre en la tarjeta:</label>
              <input
                v-model="formData.cardName"
                type="text"
                id="cardname"
                placeholder="Nombre completo"
              />
            </div>
            <div class="form-group">
              <label for="cardnumber">Número de tarjeta:</label>
              <input
                v-model="formData.cardNumber"
                type="text"
                id="cardnumber"
                placeholder="1234 5678 9012 3456"
                maxlength="19"
              />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="expiry">Vencimiento:</label>
                <input
                  v-model="formData.expiry"
                  type="text"
                  id="expiry"
                  placeholder="MM/YY"
                  maxlength="5"
                />
              </div>
              <div class="form-group">
                <label for="cvv">CVV:</label>
                <input
                  v-model="formData.cvv"
                  type="text"
                  id="cvv"
                  placeholder="123"
                  maxlength="3"
                />
              </div>
            </div>
          </div>
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>

        <div class="form-actions">
          <router-link to="/cart" class="btn-back">← Volver</router-link>
          <button
            @click="processPayment"
            :disabled="isLoading"
            class="btn-pay"
          >
            {{ isLoading ? 'Procesando...' : `Pagar $${totalAmount.toFixed(2)}` }}
          </button>
        </div>
      </div>

      <!-- Order summary -->
      <div class="order-summary">
        <h2>Resumen de tu orden</h2>
        <div class="summary-items">
          <div v-for="item in cart.items" :key="item.id" class="summary-item">
            <div>
              <span class="item-name">{{ item.name }}</span>
              <span class="item-qty">x{{ item.quantity }}</span>
            </div>
            <span class="item-price">${{ (item.price * item.quantity).toFixed(2) }}</span>
          </div>
        </div>

        <div class="summary-totals">
          <div class="summary-row">
            <span>Subtotal:</span>
            <span>${{ cart.total.toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Impuesto:</span>
            <span>${{ (cart.total * 0.1).toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Envío:</span>
            <span>$5.00</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span>${{ totalAmount.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Success modal -->
    <div v-if="showSuccess" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>¡Pedido realizado!</h2>
        </div>
        <div class="modal-body">
          <p>Tu pedido ha sido procesado exitosamente.</p>
          <p><strong>Número de pedido:</strong> #{{ orderNumber }}</p>
          <p><strong>Total pagado:</strong> ${{ totalAmount.toFixed(2) }}</p>
          <p>Recibirás tu pedido en aproximadamente 30-45 minutos.</p>
        </div>
        <div class="modal-footer">
          <button @click="goHome" class="btn-modal">Volver al menú</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCartStore } from '../../stores/cart'
import { useRouter } from 'vue-router'

const cart = useCartStore()
const router = useRouter()
const isLoading = ref(false)
const error = ref(null)
const showSuccess = ref(false)
const orderNumber = ref(null)

const formData = ref({
  address: '',
  city: '',
  postalcode: '',
  paymentMethod: 'card',
  cardName: '',
  cardNumber: '',
  expiry: '',
  cvv: ''
})

const totalAmount = computed(() => {
  const tax = cart.total * 0.1
  const shipping = 5.0
  return cart.total + tax + shipping
})

async function processPayment() {
  isLoading.value = true
  error.value = null

  try {
    // Validate form
    if (!formData.value.address || !formData.value.city || !formData.value.postalcode) {
      throw new Error('Completa todos los campos de envío')
    }

    if (formData.value.paymentMethod === 'card') {
      if (!formData.value.cardName || !formData.value.cardNumber) {
        throw new Error('Completa los datos de la tarjeta')
      }
    }

    // Simulate payment processing
    await new Promise(resolve => setTimeout(resolve, 2000))

    // Generate order number
    orderNumber.value = String(10000 + Math.floor(Math.random() * 90000))

    // Here you would normally send the order to the backend
    // For now, we're simulating a successful payment

    // Show success modal
    showSuccess.value = true

    // Clear cart
    cart.clear()
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function goHome() {
  showSuccess.value = false
  router.push('/menu')
}
</script>

<style scoped>
.checkout-container {
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

.checkout-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

.checkout-form {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.section {
  margin-bottom: 2rem;
}

.section h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  color: #2c3e50;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.payment-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  transition: border-color 0.3s ease;
}

.payment-option:hover {
  border-color: #667eea;
}

.payment-option input[type="radio"] {
  cursor: pointer;
  margin: 0;
}

.card-form {
  margin-top: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 5px;
}

.error-message {
  background: #ffe6e6;
  color: #c0392b;
  padding: 1rem;
  border-radius: 5px;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-back,
.btn-pay {
  flex: 1;
  padding: 1rem;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-back {
  background: #ecf0f1;
  color: #2c3e50;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-back:hover {
  opacity: 0.8;
}

.btn-pay {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  color: white;
}

.btn-pay:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-pay:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.order-summary {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  height: fit-content;
  sticky: top 2rem;
}

.order-summary h2 {
  color: #2c3e50;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid #ecf0f1;
  padding-bottom: 0.5rem;
}

.summary-items {
  margin-bottom: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid #ecf0f1;
}

.item-name {
  color: #2c3e50;
  font-weight: 500;
}

.item-qty {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-left: 0.5rem;
}

.item-price {
  color: #27ae60;
  font-weight: 600;
}

.summary-totals {
  padding-top: 1rem;
  border-top: 2px solid #ecf0f1;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  color: #2c3e50;
}

.summary-row.total {
  font-size: 1.3rem;
  font-weight: 700;
  color: #27ae60;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #ecf0f1;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  width: 100%;
  text-align: center;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 2px solid #ecf0f1;
}

.modal-header h2 {
  color: #27ae60;
  margin: 0;
}

.modal-body {
  padding: 1.5rem;
  color: #2c3e50;
}

.modal-body p {
  margin: 0.75rem 0;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #ecf0f1;
}

.btn-modal {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.btn-modal:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .checkout-content {
    grid-template-columns: 1fr;
  }

  .order-summary {
    sticky: unset;
  }
}
</style>
