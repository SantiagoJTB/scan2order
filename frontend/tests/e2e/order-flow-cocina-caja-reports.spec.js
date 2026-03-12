import { test, expect } from '@playwright/test'

test('flujo de pedido en cocina y caja registra eventos clave', async ({ page }) => {
  const restaurantId = 7
  const baseTime = new Date('2026-03-12T12:00:00.000Z').toISOString()
  const paidTime = new Date('2026-03-12T12:05:00.000Z').toISOString()

  const orders = [
    {
      id: 501,
      restaurant_id: restaurantId,
      restaurant: { id: restaurantId, name: 'Restaurante Siete' },
      status: 'pending',
      type: 'local',
      total: 42,
      created_at: baseTime,
      updated_at: baseTime,
      order_items: [{ id: 1, quantity: 2, product: { name: 'Croquetas' } }],
      payments: [],
      notes: 'Sin cebolla',
    },
  ]

  const statusUpdates = []
  const paymentRequests = []

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 70,
        name: 'Staff Demo',
        email: 'staff.demo@test.local',
        role: { name: 'staff' },
        restaurant_id: restaurantId,
        restaurant_name: 'Restaurante Siete',
      }),
    })
  })

  await page.route('**/api/orders', async (route) => {
    if (route.request().method() !== 'GET') {
      return route.continue()
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(orders),
    })
  })

  await page.route('**/api/orders/*', async (route) => {
    if (route.request().method() !== 'PUT') {
      return route.continue()
    }

    const request = route.request()
    const url = new URL(request.url())
    const orderId = Number(url.pathname.split('/').pop())
    const payload = request.postDataJSON()
    const nextStatus = payload?.status

    const order = orders.find((entry) => entry.id === orderId)
    if (!order || !nextStatus) {
      return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'Invalid payload' }) })
    }

    order.status = String(nextStatus)
    order.updated_at = baseTime
    statusUpdates.push({ orderId, status: order.status })

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(order),
    })
  })

  await page.route('**/api/orders/*/payments/cash', async (route) => {
    if (route.request().method() !== 'POST') {
      return route.continue()
    }

    const request = route.request()
    const url = new URL(request.url())
    const orderId = Number(url.pathname.split('/')[3])
    const payload = request.postDataJSON() || {}
    const order = orders.find((entry) => entry.id === orderId)

    if (!order) {
      return route.fulfill({ status: 404, contentType: 'application/json', body: JSON.stringify({ message: 'Order not found' }) })
    }

    const payment = {
      id: 9001,
      method: 'cash',
      status: payload.immediate ? 'succeeded' : 'pending',
      amount: payload.amount,
      paid_at: payload.immediate ? paidTime : null,
      created_at: paidTime,
    }

    order.payments = [payment]
    if (payload.immediate) {
      order.status = 'paid'
      order.updated_at = paidTime
    }

    paymentRequests.push({ orderId, payload })

    await route.fulfill({
      status: 201,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Cash payment created successfully', payment }),
    })
  })

  await page.addInitScript(({ restaurantId: rid }) => {
    localStorage.setItem('token', 'token-staff-flow')
    localStorage.setItem('user', JSON.stringify({
      id: 70,
      name: 'Staff Demo',
      email: 'staff.demo@test.local',
      role: { name: 'staff' },
      restaurant_id: rid,
      restaurant_name: 'Restaurante Siete',
    }))
  }, { restaurantId })

  await page.goto(`/cocina/${restaurantId}`)
  await expect(page.getByText('Orden #501')).toBeVisible()

  await page.getByRole('button', { name: 'Iniciar preparación' }).click()
  await expect(page.getByText('En preparación')).toBeVisible()

  await page.getByRole('button', { name: 'Marcar lista' }).click()
  await expect(page.getByText('No hay órdenes nuevas en este momento.')).toBeVisible()

  await page.goto(`/caja/${restaurantId}`)
  await expect(page.getByText('Orden #501')).toBeVisible()

  await page.getByRole('button', { name: 'Marcar como cobrada' }).click()
  await expect(page.getByText('No hay órdenes pendientes de pago en este momento.')).toBeVisible()

  await page.getByRole('button', { name: '📋 Ver historial del día' }).click()
  await expect(page.getByRole('heading', { name: 'Orden #501' })).toBeVisible()
  await expect(page.getByText('✓ Cobrada')).toBeVisible()

  expect(statusUpdates).toEqual([
    { orderId: 501, status: 'processing' },
    { orderId: 501, status: 'completed' },
    { orderId: 501, status: 'paid' },
  ])

  expect(paymentRequests).toHaveLength(1)
  expect(paymentRequests[0]).toEqual({
    orderId: 501,
    payload: {
      amount: 42,
      immediate: true,
    },
  })

  expect(orders[0].status).toBe('paid')
  expect(orders[0].payments[0].status).toBe('succeeded')
  expect(orders[0].payments[0].paid_at).toBe(paidTime)
})

test('informes admin usan solo pedidos cobrados con evidencia de pago', async ({ page }) => {
  const now = new Date('2026-03-12T13:00:00.000Z').toISOString()

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 11,
        name: 'Admin Demo',
        role: { name: 'admin' },
      }),
    })
  })

  await page.route('**/api/orders', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 801,
          restaurant_id: 7,
          restaurant: { id: 7, name: 'Restaurante Siete' },
          status: 'paid',
          total: 42,
          created_at: now,
          updated_at: now,
          order_items: [{ id: 1, quantity: 2, product: { name: 'Croquetas' } }],
          payments: [{ status: 'succeeded', paid_at: now, amount: 42, method: 'cash' }],
        },
        {
          id: 802,
          restaurant_id: 7,
          restaurant: { id: 7, name: 'Restaurante Siete' },
          status: 'completed',
          total: 25,
          created_at: now,
          updated_at: now,
          order_items: [{ id: 2, quantity: 1, product: { name: 'Tortilla' } }],
          payments: [],
        },
        {
          id: 803,
          restaurant_id: 7,
          restaurant: { id: 7, name: 'Restaurante Siete' },
          status: 'cancelled',
          total: 99,
          created_at: now,
          updated_at: now,
          order_items: [{ id: 3, quantity: 4, product: { name: 'Refresco' } }],
          payments: [],
        },
      ]),
    })
  })

  await page.addInitScript(() => {
    localStorage.setItem('token', 'token-admin-flow')
    localStorage.setItem('user', JSON.stringify({
      id: 11,
      name: 'Admin Demo',
      role: { name: 'admin' },
    }))
  })

  await page.goto('/admin/reports')

  const ordersKpi = page.locator('.kpis .kpi-card').nth(0).locator('.kpi-value')
  const salesKpi = page.locator('.kpis .kpi-card').nth(1).locator('.kpi-value')

  await expect(ordersKpi).toHaveText('1')
  await expect(salesKpi).toHaveText('$42.00')
  await expect(page.getByRole('heading', { name: 'Restaurante Siete' })).toBeVisible()
})
