import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'
import Reports from '../../src/views/admin/Reports.vue'

describe('Reports.vue unauthorized view', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('shows access denied for non-admin roles', () => {
    const auth = useAuthStore()
    auth.user = { role: { name: 'cliente' } }
    auth.token = 'token-test'
    auth.initialized = true

    const wrapper = mount(Reports)

    expect(wrapper.text()).toContain('Acceso denegado')
    expect(wrapper.text()).toContain('Solo Admin y Superadmin pueden ver informes')
  })
})
