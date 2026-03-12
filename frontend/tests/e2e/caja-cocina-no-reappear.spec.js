import { test, expect } from '@playwright/test'

test('pedido cobrado no reaparece en caja al completar en cocina', async ({ page }) => {
  const now = new Date().toISOString()

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 50,
        name: 'Staff Test',
        role: { name: 'staff' },
        restaurant_id: 1,
        restaurant_name: 'Casa Dana',
      }),
    })
  })

  await page.route('**/api/orders', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 101,
          restaurant_id: 1,
          restaurant: { id: 1, name: 'Casa Dana' },
          status: 'completed',
          type: 'local',
          total: 35,
          updated_at: now,
          order_items: [],
          payments: [{ status: 'succeeded', paid_at: now, amount: 35, method: 'cash' }],
        },
        {
          id: 102,
          restaurant_id: 1,
          restaurant: { id: 1, name: 'Casa Dana' },
          status: 'completed',
          type: 'delivery',
          total: 18,
          updated_at: now,
          order_items: [],
          payments: [{ status: 'pending', amount: 18, method: 'cash' }],
        },
      ]),
    })
  })

  await page.addInitScript(() => {
    localStorage.setItem('token', 'token-test')
    localStorage.setItem('user', JSON.stringify({
      id: 50,
      name: 'Staff Test',
      role: { name: 'staff' },
      restaurant_id: 1,
      restaurant_name: 'Casa Dana',
    }))
  })

  await page.goto('/caja/1')

  await expect(page.getByText('Orden #102')).toBeVisible()
  await expect(page.getByText('Orden #101')).not.toBeVisible()

  await page.getByRole('button', { name: '📋 Ver historial del día' }).click()
  await expect(page.getByText('Orden #101')).toBeVisible()
})
