import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '../services/api'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchOrders = async () => {
    loading.value = true
    error.value = null
    try {
      const { data } = await apiClient.get('/orders')
      orders.value = data || []
    } catch (err) {
      error.value = err.message
      orders.value = []
    } finally {
      loading.value = false
    }
  }

  const createOrder = async (payload) => {
    try {
      const result = await apiClient.post('/orders', payload)
      await fetchOrders()
      return result
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  const updateOrderStatus = async (id, status) => {
    try {
      await apiClient.put(`/orders/${id}`, { status })
      await fetchOrders()
      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  const deleteOrder = async (id) => {
    try {
      await apiClient.delete(`/orders/${id}`)
      await fetchOrders()
      return true
    } catch (err) {
      error.value = err.message
      return false
    }
  }

  return {
    orders,
    loading,
    error,
    fetchOrders,
    createOrder,
    updateOrderStatus,
    deleteOrder
  }
})
