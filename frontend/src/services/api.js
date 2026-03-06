// API client using native Fetch API (no external dependencies)
const baseURL = '/api'

const apiClient = {
  async request(method, endpoint, data = null) {
    try {
      const options = {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }

      // Add token if exists
      const token = localStorage.getItem('token')
      if (token) {
        options.headers.Authorization = `Bearer ${token}`
      }

      if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
        options.body = JSON.stringify(data)
      }

      const response = await fetch(`${baseURL}${endpoint}`, options)

      if (!response.ok) {
        const error = await response.json().catch(() => ({}))
        throw {
          status: response.status,
          message: error.message || `HTTP ${response.status}`
        }
      }

      return await response.json()
    } catch (error) {
      console.error(`API Error [${method} ${endpoint}]:`, error)
      throw error
    }
  },

  get(endpoint) {
    return this.request('GET', endpoint)
  },

  post(endpoint, data) {
    return this.request('POST', endpoint, data)
  },

  put(endpoint, data) {
    return this.request('PUT', endpoint, data)
  },

  patch(endpoint, data) {
    return this.request('PATCH', endpoint, data)
  },

  delete(endpoint) {
    return this.request('DELETE', endpoint)
  }
}

export default apiClient
