import { test, expect } from '@playwright/test'

const SHOULD_RUN = process.env.PLAYWRIGHT_MFA_EMAIL_E2E === '1'
const BACKEND_URL = process.env.PLAYWRIGHT_BACKEND_URL || 'http://localhost:8080'
const MAILPIT_URL = process.env.PLAYWRIGHT_MAILPIT_URL || 'http://127.0.0.1:8025'

async function apiRequest(request, method, path, token, body) {
  const response = await request.fetch(`${BACKEND_URL}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    ...(body ? { data: body } : {}),
  })

  const contentType = response.headers()['content-type'] || ''
  const data = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok()) {
    throw new Error(`${method} ${path} failed (${response.status()}): ${JSON.stringify(data)}`)
  }

  return data
}

async function fetchMessageCodeFromMailpit(request, toAddress, containsText, timeoutMs = 20000) {
  const start = Date.now()

  while (Date.now() - start < timeoutMs) {
    const messagesResponse = await request.fetch(`${MAILPIT_URL}/api/v1/messages`, {
      headers: { Accept: 'application/json' },
    })

    if (messagesResponse.ok()) {
      const payload = await messagesResponse.json()
      const messages = Array.isArray(payload?.messages) ? payload.messages : []

      for (const message of messages) {
        const recipients = Array.isArray(message?.To) ? message.To : []
        const hasRecipient = recipients.some((entry) => {
          const address = entry?.Address || entry?.Mailbox
          return String(address || '').toLowerCase() === String(toAddress).toLowerCase()
        })

        if (!hasRecipient) continue

        const messageId = message?.ID
        if (!messageId) continue

        const detailResponse = await request.fetch(`${MAILPIT_URL}/api/v1/message/${messageId}`, {
          headers: { Accept: 'application/json' },
        })

        if (!detailResponse.ok()) continue

        const detailJson = await detailResponse.json()
        const serialized = JSON.stringify(detailJson)

        if (containsText && !serialized.toLowerCase().includes(String(containsText).toLowerCase())) {
          continue
        }

        const codeMatch = serialized.match(/\b(\d{6})\b/)
        if (codeMatch) {
          return codeMatch[1]
        }
      }
    }

    await new Promise((resolve) => setTimeout(resolve, 1000))
  }

  throw new Error(`No MFA code email found for ${toAddress} with marker "${containsText}"`)
}

test.describe('superadmin login with email MFA', () => {
  test.skip(!SHOULD_RUN, 'Set PLAYWRIGHT_MFA_EMAIL_E2E=1 to run this real Mailpit-based MFA flow')

  test('requires email MFA code after valid credentials', async ({ page, request }) => {
    const superEmail = 'superadmin@scan2order.local'
    const superPassword = 'superadmin123'

    const login = await apiRequest(request, 'POST', '/api/login', null, {
      email: superEmail,
      password: superPassword,
    })
    const token = login.token

    const me = await apiRequest(request, 'GET', '/api/me', token)

    if (!me.mfa_enabled) {
      await apiRequest(request, 'POST', '/api/mfa/setup', token, { action: 'enable' })
      const enableCode = await fetchMessageCodeFromMailpit(request, superEmail, 'activar MFA')
      await apiRequest(request, 'POST', '/api/mfa/enable', token, { mfa_code: enableCode })
    }

    await page.goto('/login')
    await page.getByLabel('Email:').fill(superEmail)
    await page.getByLabel('Contraseña:').fill(superPassword)
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page.getByLabel('Código MFA (correo):')).toBeVisible()

    const loginCode = await fetchMessageCodeFromMailpit(request, superEmail, 'iniciar sesión')
    await page.getByLabel('Código MFA (correo):').fill(loginCode)
    await page.getByRole('button', { name: 'Verificar código' }).click()

    await expect(page).toHaveURL(/\/admin/)
  })
})
