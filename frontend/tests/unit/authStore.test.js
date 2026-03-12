import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'

describe('auth store role helpers', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('normalizes role name from nested role object', () => {
    const store = useAuthStore()
    store.user = { role: { name: ' Admin ' } }

    expect(store.userRole).toBe('admin')
  })

  it('hasRole compares case-insensitively', () => {
    const store = useAuthStore()
    store.user = { role: { name: 'Staff' } }

    expect(store.hasRole('staff')).toBe(true)
    expect(store.hasRole('admin')).toBe(false)
  })

  it('hasAnyRole works with normalized roles', () => {
    const store = useAuthStore()
    store.user = { role: { name: 'superadmin' } }

    expect(store.hasAnyRole(['admin', 'superadmin'])).toBe(true)
    expect(store.hasAnyRole(['cliente'])).toBe(false)
  })
})
