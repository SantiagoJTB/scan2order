import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Security from '../../src/views/admin/Security.vue'
import { useAuthStore } from '../../src/stores/auth'

describe('Security.vue unauthorized view', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('shows access denied for non-superadmin roles', () => {
    const auth = useAuthStore()
    auth.user = { role: { name: 'admin' } }
    auth.token = 'token-test'
    auth.initialized = true

    const wrapper = mount(Security)

    expect(wrapper.text()).toContain('Acceso denegado')
    expect(wrapper.text()).toContain('Solo superadmin puede ver este panel')
  })
})
