import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const initialized = ref(false)
  const isLoading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => {
    const rawRole = user.value?.role?.name ?? user.value?.role ?? user.value?.role_name ?? null
    return typeof rawRole === 'string' ? rawRole.trim().toLowerCase() : null
  })

  // For staff users, get the linked restaurant ID
  const staffRestaurantId = computed(() => {
    if (userRole.value === 'staff' && user.value?.restaurant_id) {
      return user.value.restaurant_id
    }
    return null
  })

  const hasRole = (role) => {
    if (typeof role !== 'string') return false
    return userRole.value === role.trim().toLowerCase()
  }

  const hasAnyRole = (roles) => {
    if (!Array.isArray(roles)) return false
    const normalized = roles
      .filter((role) => typeof role === 'string')
      .map((role) => role.trim().toLowerCase())
    return normalized.includes(userRole.value)
  }

    async function fetchCurrentUser() {
      if (!token.value) return false
    
      try {
        const response = await fetch('/api/me', {
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Accept': 'application/json'
          }
        })
      
        if (!response.ok) {
          logout()
          return false
        }
      
        const data = await response.json()
        user.value = data
        localStorage.setItem('user', JSON.stringify(data))
        return true
      } catch (err) {
        console.error('Error fetching current user:', err)
        logout()
        return false
      }
    }

  async function login(email, password, mfaCode = null) {
    isLoading.value = true
    error.value = null
    try {
      const payload = { email, password }
      if (mfaCode) payload.mfa_code = String(mfaCode)

      const response = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      
      if (!response.ok) {
        const data = await response.json()
        const loginError = new Error(data.message || 'Login failed')
        loginError.mfaRequired = Boolean(data?.mfa_required)
        throw loginError
      }

      const data = await response.json()
      user.value = data.user
      token.value = data.token
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function register(name, email, password) {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch('/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          name, 
          email, 
          password,
          password_confirmation: password
        })
      })
      
      if (!response.ok) {
        const data = await response.json()
        throw new Error(data.message || 'Registration failed')
      }

      const data = await response.json()
      user.value = data.user
      token.value = data.token
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token.value}`,
            'Content-Type': 'application/json'
          }
        })
      }
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

    async function initFromStorage() {
      const storedUser = localStorage.getItem('user')
    const storedToken = localStorage.getItem('token')
      if (storedUser) {
        try {
          user.value = JSON.parse(storedUser)
        } catch {
          user.value = null
          localStorage.removeItem('user')
        }
      }
    if (storedToken) token.value = storedToken
    
      // Fetch fresh user data if token exists
      if (token.value) {
        await fetchCurrentUser()
      }

      initialized.value = true
  }

  return {
    user,
    token,
    initialized,
    isLoading,
    error,
    isAuthenticated,
    userRole,
    staffRestaurantId,
    hasRole,
    hasAnyRole,
      fetchCurrentUser,
    login,
    register,
    logout,
    initFromStorage
  }
})
