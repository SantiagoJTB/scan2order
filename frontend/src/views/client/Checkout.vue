<template>
  <div class="checkout-container">
    <div class="header">
      <h1>Pago</h1>
      <p>Completa tu compra</p>
    </div>

    <div class="checkout-content">
      <div class="checkout-form">
        <div class="section">
          <h2>Tipo de consumo</h2>
          <div class="order-types">
            <label class="payment-option">
              <input v-model="formData.orderType" type="radio" value="local" required />
              <span>Consumir en el local</span>
            </label>
            <label class="payment-option">
              <input v-model="formData.orderType" type="radio" value="takeaway" />
              <span>Para llevar</span>
            </label>
          </div>
        </div>

        <div class="section">
          <h2>Datos adicionales</h2>
          <div class="form-group">
            <label for="address">Dirección:</label>
            <input
              v-model="formData.address"
              type="text"
              id="address"
              placeholder="Referencia opcional"
            />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="city">Ciudad:</label>
              <input
                v-model="formData.city"
                type="text"
                id="city"
                placeholder="Ciudad (opcional)"
              />
            </div>
            <div class="form-group">
              <label for="postalcode">Código postal:</label>
              <input
                v-model="formData.postalcode"
                type="text"
                id="postalcode"
                placeholder="Código postal (opcional)"
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
            <label class="payment-option">
              <input v-model="formData.paymentMethod" type="radio" value="table" />
              <span>Pago en mesa</span>
            </label>
          </div>

          <!-- Card payment form (Stripe Elements) -->
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
              <label>Datos de la tarjeta:</label>
              <div ref="cardElementRef" class="stripe-card-element"></div>
            </div>
            <p v-if="stripePublicKeyMissing" class="stripe-hint stripe-error">
              Configura VITE_STRIPE_KEY para habilitar pagos con tarjeta.
            </p>
            <p v-else class="stripe-hint">
              Usa una tarjeta de prueba de Stripe (ej: 4242 4242 4242 4242).
            </p>
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
            <span>Servicio:</span>
            <span>$0.00</span>
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
          <p>{{ successMessage }}</p>
        </div>
        <div class="modal-footer">
          <button @click="goHome" class="btn-modal">Volver al menú</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch, onBeforeUnmount } from 'vue'
import { useCartStore } from '../../stores/cart'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import { loadStripe } from '@stripe/stripe-js'

const cart = useCartStore()
const auth = useAuthStore()
const router = useRouter()
const isLoading = ref(false)
const error = ref(null)
const showSuccess = ref(false)
const orderNumber = ref(null)
const stripeClientSecret = ref(null)
const stripe = ref(null)
const stripeElements = ref(null)
const stripeCardElement = ref(null)
const cardElementRef = ref(null)

const formData = ref({
  orderType: 'local',
  address: '',
  city: '',
  postalcode: '',
  paymentMethod: 'card',
  cardName: ''
})

const stripePublicKey = import.meta.env.VITE_STRIPE_KEY
const stripePublicKeyMissing = computed(() => !stripePublicKey)

const totalAmount = computed(() => {
  const tax = cart.total * 0.1
  const service = 0
  return cart.total + tax + service
})

const successMessage = computed(() => {
  if (formData.value.orderType === 'takeaway') {
    return 'Tu pedido estará listo para recoger en aproximadamente 20-30 minutos.'
  }
  return 'Tu pedido será preparado para consumir en el local en aproximadamente 20-30 minutos.'
})

async function processPayment() {
  isLoading.value = true
  error.value = null

  try {
    const orderId = await createOrderWithItems()
    orderNumber.value = String(orderId)

    // Use test payment endpoint to complete the payment
    // This works for both card and cash methods
    if (auth.token) {
      await createTestPayment(orderId)
    }

    showSuccess.value = true
    cart.clear()
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

function goHome() {
  showSuccess.value = false
  router.push('/orders')
}

async function createOrderWithItems() {
  if (!auth.token) {
    throw new Error('Debes iniciar sesión para completar el pago')
  }

  if (!cart.items.length) {
    throw new Error('Tu carrito está vacío')
  }

  const restaurantId = cart.items[0]?.restaurantId
  if (!restaurantId) {
    throw new Error('No se pudo determinar el restaurante del pedido')
  }

  const details = [formData.value.address, formData.value.city, formData.value.postalcode]
    .filter(Boolean)
    .join(', ')
  const notes = details
    ? `Tipo de consumo: ${formData.value.orderType === 'takeaway' ? 'Para llevar' : 'Consumir en local'}. Referencia: ${details}`
    : `Tipo de consumo: ${formData.value.orderType === 'takeaway' ? 'Para llevar' : 'Consumir en local'}`

  const orderResponse = await fetch('/api/orders', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      restaurant_id: restaurantId,
      user_id: auth.user?.id || null,
      type: formData.value.orderType === 'takeaway' ? 'delivery' : 'local',
      status: 'pending',
      total: totalAmount.value,
      notes
    })
  })

  const orderContentType = orderResponse.headers.get('content-type') || ''
  const orderData = orderContentType.includes('application/json') ? await orderResponse.json() : null

  if (!orderResponse.ok || !orderData?.id) {
    throw new Error(orderData?.message || 'No se pudo crear la orden')
  }

  const orderId = orderData.id

  for (const item of cart.items) {
    const itemResponse = await fetch('/api/order-items', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        order_id: orderId,
        product_id: item.id,
        quantity: item.quantity
      })
    })

    if (!itemResponse.ok) {
      throw new Error('No se pudieron crear los productos del pedido')
    }
  }

  localStorage.setItem('checkout_order_id', String(orderId))
  return orderId
}

async function createTestPayment(orderId) {
  const response = await fetch(`/api/orders/${orderId}/payments/test`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      amount: totalAmount.value,
      currency: 'eur',
      method: formData.value.paymentMethod
    })
  })

  const contentType = response.headers.get('content-type') || ''
  const data = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(data?.message || 'No se pudo procesar el pago')
  }
}

async function initStripeElements() {
  if (stripePublicKeyMissing.value) {
    throw new Error('Stripe no está configurado en el frontend')
  }

  if (!stripe.value) {
    stripe.value = await loadStripe(stripePublicKey)
  }

  if (!stripe.value) {
    throw new Error('No se pudo inicializar Stripe')
  }

  if (!stripeElements.value) {
    stripeElements.value = stripe.value.elements()
  }

  await nextTick()

  if (!stripeCardElement.value) {
    stripeCardElement.value = stripeElements.value.create('card', {
      hidePostalCode: true,
      style: {
        base: {
          fontSize: '16px',
          color: '#2c3e50',
          '::placeholder': {
            color: '#95a5a6'
          }
        }
      }
    })
  }

  if (cardElementRef.value && !cardElementRef.value.hasChildNodes()) {
    stripeCardElement.value.mount(cardElementRef.value)
  }
}

function destroyStripeElement() {
  if (stripeCardElement.value) {
    stripeCardElement.value.unmount()
  }
}

async function startStripePayment(orderId) {
  await initStripeElements()

  if (!stripeCardElement.value) {
    throw new Error('No se pudo inicializar el formulario de tarjeta')
  }

  const response = await fetch(`/api/orders/${orderId}/payments/stripe`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      amount: totalAmount.value,
      currency: 'eur'
    })
  })

  const contentType = response.headers.get('content-type') || ''
  const data = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(data?.message || 'No se pudo iniciar el pago con Stripe')
  }

  stripeClientSecret.value = data?.client_secret || null

  if (!stripeClientSecret.value) {
    throw new Error('Stripe no devolvió client_secret')
  }

  const { error: stripeError, paymentIntent } = await stripe.value.confirmCardPayment(
    stripeClientSecret.value,
    {
      payment_method: {
        card: stripeCardElement.value,
        billing_details: {
          name: formData.value.cardName
        }
      }
    }
  )

  if (stripeError) {
    throw new Error(stripeError.message || 'No se pudo confirmar el pago con Stripe')
  }

  if (!paymentIntent || paymentIntent.status !== 'succeeded') {
    throw new Error('El pago no fue completado por Stripe')
  }

  orderNumber.value = String(orderId)
}

async function createCashPayment(orderId) {
  const response = await fetch(`/api/orders/${orderId}/payments/cash`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${auth.token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      amount: totalAmount.value,
      currency: 'eur'
    })
  })

  const contentType = response.headers.get('content-type') || ''
  const data = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(data?.message || 'No se pudo crear el pago en efectivo')
  }
}

watch(() => formData.value.paymentMethod, async (method) => {
  if (method === 'card') {
    try {
      await initStripeElements()
    } catch (err) {
      error.value = err.message
    }
  } else {
    destroyStripeElement()
  }
})

onBeforeUnmount(() => {
  destroyStripeElement()
})
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

.order-types {
  display: flex;
  flex-direction: column;
  gap: 1rem;
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

.stripe-card-element {
  background: white;
  border: 2px solid #ecf0f1;
  border-radius: 5px;
  padding: 0.75rem;
  min-height: 44px;
}

.stripe-hint {
  margin: 0.75rem 0 0;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.stripe-error {
  color: #c0392b;
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
