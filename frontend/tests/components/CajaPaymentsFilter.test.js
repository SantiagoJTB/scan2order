import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'
import Payments from '../../src/views/caja/Payments.vue'

vi.mock('vue-router', () => ({
  useRoute: () => ({ params: {} })
}))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0))

describe('Caja Payments.vue pending filter', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
  })

  it('does not show already collected orders in pending list', async () => {
    const auth = useAuthStore()
    auth.user = { role: { name: 'staff' } }
    auth.token = 'token-test'
    sessionStorage.setItem('scan2order_operator', 'Operador Test')

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ([
        {
          id: 1,
          restaurant_id: 1,
          status: 'completed',
          total: 22,
          type: 'local',
          updated_at: '2026-03-09T12:00:00.000Z',
          payments: [{ status: 'succeeded', paid_at: '2026-03-09T11:30:00.000Z' }],
          order_items: []
        },
        {
          id: 2,
          restaurant_id: 1,
          status: 'completed',
          total: 18,
          type: 'delivery',
          updated_at: '2026-03-09T12:10:00.000Z',
          payments: [{ status: 'pending' }],
          order_items: []
        }
      ]),
      headers: { get: () => 'application/json' }
    })

    const wrapper = mount(Payments)
    await flushPromises()
    await flushPromises()

    expect(wrapper.text()).not.toContain('Orden #1')
    expect(wrapper.text()).toContain('Orden #2')

    wrapper.unmount()
    global.fetch.mockRestore()
  })
})
