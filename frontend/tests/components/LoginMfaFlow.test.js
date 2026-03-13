import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { vi } from 'vitest'
import Login from '../../src/views/Login.vue'
import { useAuthStore } from '../../src/stores/auth'

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
  useRoute: () => ({ query: {} }),
}))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0))

describe('Login.vue MFA email flow', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('switches to MFA step when backend requires mfa', async () => {
    const auth = useAuthStore()
    const mfaError = new Error('MFA code is required for superadmin')
    mfaError.mfaRequired = true
    auth.login = vi.fn().mockRejectedValueOnce(mfaError)

    const wrapper = mount(Login, {
      global: {
        stubs: {
          RouterLink: { template: '<a><slot /></a>' },
        },
      },
    })

    await wrapper.find('#email').setValue('superadmin@scan2order.local')
    await wrapper.find('#password').setValue('superadmin123')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(auth.login).toHaveBeenCalledWith('superadmin@scan2order.local', 'superadmin123', null)
    expect(wrapper.find('#mfa').exists()).toBe(true)
    expect(wrapper.text()).toContain('Revisa tu email e ingresa el código de 6 dígitos')
    expect(wrapper.text()).toContain('Verificar código')
  })

  it('submits MFA code in second step', async () => {
    const auth = useAuthStore()
    const mfaError = new Error('MFA required')
    mfaError.mfaRequired = true
    auth.login = vi
      .fn()
      .mockRejectedValueOnce(mfaError)
      .mockResolvedValueOnce(undefined)

    const wrapper = mount(Login, {
      global: {
        stubs: {
          RouterLink: { template: '<a><slot /></a>' },
        },
      },
    })

    await wrapper.find('#email').setValue('superadmin@scan2order.local')
    await wrapper.find('#password').setValue('superadmin123')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    await wrapper.find('#mfa').setValue('123456')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(auth.login).toHaveBeenNthCalledWith(2, 'superadmin@scan2order.local', 'superadmin123', '123456')
  })

  it('resends MFA code and shows confirmation message', async () => {
    const auth = useAuthStore()
    const mfaError = new Error('MFA required')
    mfaError.mfaRequired = true
    auth.login = vi
      .fn()
      .mockRejectedValueOnce(mfaError)
      .mockRejectedValueOnce(mfaError)

    const wrapper = mount(Login, {
      global: {
        stubs: {
          RouterLink: { template: '<a><slot /></a>' },
        },
      },
    })

    await wrapper.find('#email').setValue('superadmin@scan2order.local')
    await wrapper.find('#password').setValue('superadmin123')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const resendButton = wrapper.findAll('button').find((btn) => btn.text().includes('Reenviar código MFA'))
    expect(resendButton).toBeTruthy()

    await resendButton.trigger('click')
    await flushPromises()

    expect(auth.login).toHaveBeenNthCalledWith(2, 'superadmin@scan2order.local', 'superadmin123')
    expect(wrapper.text()).toContain('Código reenviado. Revisa tu correo.')
  })
})
