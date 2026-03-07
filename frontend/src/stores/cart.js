import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const selectedRestaurant = ref(null)

  const total = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
  })

  const itemCount = computed(() => {
    return items.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  function addItem(product, quantity = 1) {
    const existingItem = items.value.find(item => item.id === product.id)
    if (existingItem) {
      existingItem.quantity += quantity
    } else {
      items.value.push({
        id: product.id,
        name: product.name,
        price: product.price,
        quantity,
        restaurantId: product.restaurant_id
      })
    }
  }

  function removeItem(productId) {
    items.value = items.value.filter(item => item.id !== productId)
  }

  function updateQuantity(productId, quantity) {
    const item = items.value.find(item => item.id === productId)
    if (item) {
      if (quantity <= 0) {
        removeItem(productId)
      } else {
        item.quantity = quantity
      }
    }
  }

  function clear() {
    items.value = []
    selectedRestaurant.value = null
  }

  function setRestaurant(restaurant) {
    selectedRestaurant.value = restaurant
  }

  return {
    items,
    selectedRestaurant,
    total,
    itemCount,
    addItem,
    removeItem,
    updateQuantity,
    clear,
    setRestaurant
  }
})
