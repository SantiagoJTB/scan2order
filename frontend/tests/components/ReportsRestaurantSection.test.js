import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'
import Reports from '../../src/views/admin/Reports.vue'

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0))

describe('Reports.vue restaurant reports section', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('renders restaurant reports section with fetched data', async () => {
    const auth = useAuthStore()
    auth.user = { role: { name: 'admin' } }
    auth.token = 'token-test'
    auth.initialized = true

    const nowIso = new Date().toISOString()

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ([
        {
          id: 1,
          restaurant_id: 99,
          restaurant: { id: 99, name: 'Casa Dana' },
          status: 'paid',
          total: 35,
          created_at: nowIso,
          updated_at: nowIso,
          order_items: [
            { quantity: 2, product: { name: 'Croquetas Caseras' } }
          ]
        }
      ]),
      headers: { get: () => 'application/json' }
    })

    const wrapper = mount(Reports)
    await flushPromises()
    await flushPromises()

    expect(wrapper.text()).toContain('Informes por restaurante')
    expect(wrapper.text()).toContain('Casa Dana')
    expect(wrapper.text()).toContain('Pedidos analizados')

    global.fetch.mockRestore()
  })
})
