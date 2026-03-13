<template>
  <div class="cart-page">
    <!-- Header -->
    <div class="cart-header">
      <div class="header-content">
        <button @click="$router.back()" class="btn-back">
          <span class="back-icon">←</span>
          <span>Volver</span>
        </button>
        <h1 class="page-title">🛒 Tu Carrito</h1>
        <div class="header-spacer"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="cart-content">
      <!-- Empty State -->
      <div v-if="cart.items.length === 0" class="empty-cart">
        <div class="empty-icon">🛍️</div>
        <h2>Tu carrito está vacío</h2>
        <p>¡Agrega algunos productos deliciosos para empezar!</p>
        <router-link to="/restaurants" class="btn-browse">
          <span>Explorar menú</span>
          <span class="btn-arrow">→</span>
        </router-link>
      </div>

      <!-- Cart Items -->
      <div v-else class="cart-layout">
        <!-- Items List -->
        <div class="items-section">
          <div class="section-header">
            <h2>Productos ({{ cart.items.length }})</h2>
          </div>

          <div class="items-list">
            <div 
              v-for="item in cart.items" 
              :key="item.id" 
              class="cart-item"
            >
              <div class="item-details">
                <h3 class="item-name">{{ item.name }}</h3>
                <p v-if="item.description" class="item-description">
                  {{ item.description }}
                </p>
                <p class="item-price">${{ item.price.toFixed(2) }} c/u</p>
              </div>

              <div class="item-actions">
                <div class="quantity-controls">
                  <button 
                    @click="decreaseQuantity(item.id)" 
                    class="qty-btn"
                    :disabled="item.quantity <= 1"
                  >
                    <span>−</span>
                  </button>
                  <input
                    v-model.number="item.quantity"
                    @change="updateQuantity(item.id, item.quantity)"
                    type="number"
                    min="1"
                    class="qty-input"
                  />
                  <button 
                    @click="increaseQuantity(item.id)" 
                    class="qty-btn"
                  >
                    <span>+</span>
                  </button>
                </div>

                <div class="item-subtotal">
                  ${{ (item.price * item.quantity).toFixed(2) }}
                </div>

                <button 
                  @click="removeItem(item.id)" 
                  class="btn-remove"
                  title="Eliminar producto"
                >
                  <span>🗑️</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="summary-section">
          <div class="summary-card">
            <h3 class="summary-title">Resumen del pedido</h3>

            <div class="summary-details">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span>${{ cart.total.toFixed(2) }}</span>
              </div>
              <div class="summary-row">
                <span>Impuesto (10%):</span>
                <span>${{ (cart.total * 0.1).toFixed(2) }}</span>
              </div>
              <div class="summary-divider"></div>
              <div class="summary-row summary-total">
                <span>Total:</span>
                <span>${{ totalWithFees.toFixed(2) }}</span>
              </div>
            </div>

            <router-link to="/checkout" class="btn-checkout">
              <span>Proceder al pago</span>
              <span class="checkout-arrow">→</span>
            </router-link>

            <router-link to="/restaurants" class="btn-continue-shopping">
              Seguir comprando
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useCartStore } from '../../stores/cart'

const cart = useCartStore()

const totalWithFees = computed(() => {
  return cart.total * 1.1
})

function decreaseQuantity(productId) {
  const item = cart.items.find(i => i.id === productId)
  if (item && item.quantity > 1) {
    cart.updateQuantity(productId, item.quantity - 1)
  }
}

function increaseQuantity(productId) {
  const item = cart.items.find(i => i.id === productId)
  if (item) {
    cart.updateQuantity(productId, item.quantity + 1)
  }
}

function updateQuantity(productId, quantity) {
  if (quantity < 1) return
  cart.updateQuantity(productId, Number(quantity))
}

function removeItem(productId) {
  if (confirm('¿Deseas eliminar este producto del carrito?')) {
    cart.removeItem(productId)
  }
}
</script>

<style scoped>
.cart-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding-bottom: 2rem;
}

/* Header */
.cart-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem 0;
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
.cart-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

/* Empty State */
.empty-cart {
  background: white;
  border-radius: 20px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  margin: 3rem auto;
}

.empty-icon {
  font-size: 6rem;
  margin-bottom: 1.5rem;
  opacity: 0.3;
}

.empty-cart h2 {
  color: #2c3e50;
  margin: 0 0 0.5rem;
  font-size: 1.8rem;
}

.empty-cart p {
  color: #7f8c8d;
  margin: 0 0 2rem;
  font-size: 1.1rem;
}

.btn-browse {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-browse:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.btn-arrow {
  transition: transform 0.3s ease;
}

.btn-browse:hover .btn-arrow {
  transform: translateX(3px);
}

/* Cart Layout */
.cart-layout {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
  align-items: start;
}

/* Items Section */
.items-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.section-header h2 {
  color: #2c3e50;
  margin: 0 0 1.5rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.cart-item {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1.5rem;
  padding: 1.5rem;
  border: 2px solid #f0f0f0;
  border-radius: 16px;
  transition: all 0.3s ease;
}

.cart-item:hover {
  border-color: #667eea;
  box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15);
  transform: translateY(-2px);
}

.item-details {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.3rem;
}

.item-name {
  color: #2c3e50;
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
}

.item-description {
  color: #7f8c8d;
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.4;
}

.item-price {
  color: #27ae60;
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
}

.item-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.8rem;
  justify-content: space-between;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8f9fa;
  padding: 0.4rem;
  border-radius: 50px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: white;
  color: #667eea;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.qty-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  transform: scale(1.05);
}

.qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.qty-input {
  width: 50px;
  text-align: center;
  border: none;
  background: transparent;
  font-size: 1rem;
  font-weight: 700;
  color: #2c3e50;
}

.qty-input:focus {
  outline: none;
}

.item-subtotal {
  font-size: 1.2rem;
  font-weight: 700;
  color: #27ae60;
}

.btn-remove {
  background: linear-gradient(135deg, #fee 0%, #fdd 100%);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2rem;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(231, 76, 60, 0.1);
}

.btn-remove:hover {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

/* Summary Section */
.summary-section {
  position: sticky;
  top: 7rem;
}

.summary-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.summary-card:hover {
  border-color: #667eea;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
}

.summary-title {
  color: #2c3e50;
  margin: 0 0 1.5rem;
  font-size: 1.4rem;
  font-weight: 700;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  color: #7f8c8d;
  font-size: 1rem;
}

.summary-divider {
  height: 1px;
  background: #ecf0f1;
  margin: 0.5rem 0;
}

.summary-total {
  font-size: 1.4rem;
  font-weight: 700;
  color: #2c3e50;
}

.summary-total span:last-child {
  color: #27ae60;
}

.btn-checkout {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 1.2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 50px;
  font-size: 1.1rem;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  margin-bottom: 1rem;
}

.btn-checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.checkout-arrow {
  transition: transform 0.3s ease;
}

.btn-checkout:hover .checkout-arrow {
  transform: translateX(3px);
}

.btn-continue-shopping {
  display: block;
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #ecf0f1 100%);
  color: #7f8c8d;
  border: 2px solid transparent;
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-continue-shopping:hover {
  background: linear-gradient(135deg, #ecf0f1 0%, #dfe4e8 100%);
  color: #2c3e50;
  border-color: #667eea;
  transform: translateY(-1px);
}

.trust-badges {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 2rem;
}

.badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  padding: 1.2rem 0.8rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.badge:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 25px rgba(102, 126, 234, 0.15);
  border-color: #667eea;
}

.badge-icon {
  font-size: 2rem;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.badge-text {
  font-size: 0.8rem;
  color: #7f8c8d;
  font-weight: 700;
  text-align: center;
}

/* Responsive */
@media (max-width: 1024px) {
  .cart-layout {
    grid-template-columns: 1fr;
  }

  .summary-section {
    position: static;
  }
}

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

  .cart-item {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .item-image {
    width: 60px;
    height: 60px;
  }

  .item-actions {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }

  .trust-badges {
    flex-direction: column;
  }

  .badge {
    flex-direction: row;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .cart-content {
    padding: 1rem;
  }

  .items-section,
  .summary-card {
    padding: 1.5rem;
  }

  .empty-cart {
    padding: 3rem 1.5rem;
  }

  .empty-icon {
    font-size: 4rem;
  }

  .item-name {
    font-size: 1rem;
  }

  .item-description {
    font-size: 0.85rem;
  }
}
</style>
