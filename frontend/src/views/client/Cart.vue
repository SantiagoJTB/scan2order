<template>
  <div class="cart-container">
    <div class="header">
      <h1>Tu Carrito</h1>
      <router-link to="/menu" class="btn-back">← Volver al menú</router-link>
    </div>

    <div class="cart-content">
      <div v-if="cart.items.length === 0" class="empty-cart">
        <p>Tu carrito está vacío</p>
        <router-link to="/menu" class="btn-continue">Continuar comprando</router-link>
      </div>

      <div v-else class="cart-items">
        <div class="cart-table-header">
          <div class="col-product">Producto</div>
          <div class="col-price">Precio</div>
          <div class="col-quantity">Cantidad</div>
          <div class="col-subtotal">Subtotal</div>
          <div class="col-action">Acción</div>
        </div>

        <div v-for="item in cart.items" :key="item.id" class="cart-item">
          <div class="col-product">{{ item.name }}</div>
          <div class="col-price">${{ item.price.toFixed(2) }}</div>
          <div class="col-quantity">
            <button @click="decreaseQuantity(item.id)" class="qty-btn">-</button>
            <input
              v-model.number="item.quantity"
              @change="updateQuantity(item.id, item.quantity)"
              type="number"
              min="1"
              class="qty-input"
            />
            <button @click="increaseQuantity(item.id)" class="qty-btn">+</button>
          </div>
          <div class="col-subtotal">${{ (item.price * item.quantity).toFixed(2) }}</div>
          <div class="col-action">
            <button @click="removeItem(item.id)" class="btn-remove">Eliminar</button>
          </div>
        </div>

        <div class="cart-summary">
          <div class="summary-row">
            <span>Subtotal:</span>
            <span>${{ cart.total.toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Impuesto (10%):</span>
            <span>${{ (cart.total * 0.1).toFixed(2) }}</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span>${{ (cart.total * 1.1).toFixed(2) }}</span>
          </div>
        </div>

        <div class="cart-actions">
          <router-link to="/menu" class="btn-continue">Seguir comprando</router-link>
          <router-link to="/checkout" class="btn-checkout">Ir al pago</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCartStore } from '../../stores/cart'

const cart = useCartStore()

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
  cart.updateQuantity(productId, Number(quantity))
}

function removeItem(productId) {
  if (confirm('¿Deseas eliminar este producto del carrito?')) {
    cart.removeItem(productId)
  }
}
</script>

<style scoped>
.cart-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  margin: 0;
}

.btn-back {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  text-decoration: none;
  font-weight: 600;
  transition: background 0.3s ease;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.3);
}

.cart-content {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.empty-cart {
  text-align: center;
  padding: 3rem 2rem;
  color: #7f8c8d;
}

.empty-cart p {
  font-size: 1.2rem;
  margin-bottom: 1.5rem;
}

.btn-continue {
  display: inline-block;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.8rem 1.5rem;
  border-radius: 5px;
  text-decoration: none;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-continue:hover {
  opacity: 0.9;
}

.cart-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cart-table-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr;
  gap: 1rem;
  padding: 1rem;
  background: #f5f6fa;
  border-radius: 5px;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.cart-item {
  display: grid;
  grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #ecf0f1;
  border-radius: 5px;
  align-items: center;
}

.col-product {
  color: #2c3e50;
  font-weight: 500;
}

.col-price, .col-subtotal {
  text-align: right;
  color: #27ae60;
  font-weight: 600;
}

.col-quantity {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
}

.qty-btn {
  width: 30px;
  height: 30px;
  border: 1px solid #ecf0f1;
  background: white;
  border-radius: 3px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.qty-btn:hover {
  border-color: #667eea;
  color: #667eea;
}

.qty-input {
  width: 50px;
  text-align: center;
  border: 1px solid #ecf0f1;
  border-radius: 3px;
  padding: 0.5rem;
}

.qty-input:focus {
  outline: none;
  border-color: #667eea;
}

.btn-remove {
  padding: 0.5rem 1rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: opacity 0.3s ease;
}

.btn-remove:hover {
  opacity: 0.8;
}

.cart-summary {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #ecf0f1;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.8rem 0;
  color: #2c3e50;
  font-size: 1rem;
}

.summary-row.total {
  font-size: 1.3rem;
  font-weight: 700;
  color: #27ae60;
  border-top: 1px solid #ecf0f1;
  padding-top: 1rem;
}

.cart-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.cart-actions > * {
  flex: 1;
  padding: 1rem;
  text-align: center;
  border-radius: 5px;
  text-decoration: none;
  font-weight: 600;
  transition: opacity 0.3s ease;
}

.btn-checkout {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-checkout:hover {
  opacity: 0.9;
}

.btn-continue {
  background: #ecf0f1;
  color: #2c3e50;
}

.btn-continue:hover {
  opacity: 0.8;
}

@media (max-width: 768px) {
  .cart-table-header,
  .cart-item {
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
  }

  .col-product {
    grid-column: 1 / -1;
  }

  .col-price::before {
    content: 'Precio: ';
    font-weight: 600;
    color: #2c3e50;
  }

  .col-subtotal::before {
    content: 'Subtotal: ';
    font-weight: 600;
    color: #2c3e50;
  }
}
</style>
