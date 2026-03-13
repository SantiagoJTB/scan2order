import { test, expect } from '@playwright/test'

async function apiRequest(request, method, path, token, body) {
  const response = await request.fetch(`http://localhost:8080${path}`, {
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

test('flujo real: cocina -> caja -> informes con datos persistidos', async ({ page, request }) => {
  const suffix = Date.now()
  const adminEmail = `admin.real.${suffix}@scan2order.local`
  const adminPassword = 'Admin123!'
  const staffPassword = 'Staff123!'
  const restaurantName = `Rest E2E ${suffix}`

  const superLogin = await apiRequest(request, 'POST', '/api/login', null, {
    email: 'superadmin@scan2order.local',
    password: 'superadmin123',
  })
  const superToken = superLogin.token

  const createdUsers = await apiRequest(request, 'POST', '/api/users', superToken, {
    name: `Admin E2E ${suffix}`,
    email: adminEmail,
    password: adminPassword,
    staff_password: staffPassword,
    role: 'admin',
  })

  const adminUser = createdUsers.users.find((user) => user.role?.name === 'admin')
  const staffUser = createdUsers.users.find((user) => user.role?.name === 'staff')

  expect(adminUser).toBeTruthy()
  expect(staffUser).toBeTruthy()

  const createdRestaurant = await apiRequest(request, 'POST', '/api/restaurants', superToken, {
    name: restaurantName,
    address: 'Calle Real 123',
    phone: '600000000',
    active: true,
  })

  const restaurantId = createdRestaurant.id

  await apiRequest(request, 'PUT', `/api/restaurants/${restaurantId}/admins`, superToken, {
    admin_ids: [adminUser.id],
  })

  await apiRequest(request, 'PUT', `/api/restaurants/${restaurantId}/staffs`, superToken, {
    staff_ids: [staffUser.id],
  })

  const category = await apiRequest(request, 'POST', '/api/categories', superToken, {
    restaurant_id: restaurantId,
    name: `Categoría E2E ${suffix}`,
  })

  const catalog = await apiRequest(request, 'POST', `/api/restaurants/${restaurantId}/catalogs`, superToken, {
    name: `Cat E2E ${suffix}`,
    active: true,
    order: 1,
  })

  const section = await apiRequest(request, 'POST', `/api/restaurants/${restaurantId}/catalogs/${catalog.id}/sections`, superToken, {
    name: `Sec E2E ${suffix}`,
    active: true,
    order: 1,
  })

  const product = await apiRequest(
    request,
    'POST',
    `/api/restaurants/${restaurantId}/catalogs/${catalog.id}/sections/${section.id}/products`,
    superToken,
    {
      name: `Producto E2E ${suffix}`,
      price: 15,
      active: true,
      category_id: category.id,
    },
  )

  const staffLogin = await apiRequest(request, 'POST', '/api/login', null, {
    email: staffUser.email,
    password: staffPassword,
  })
  const staffToken = staffLogin.token

  const order = await apiRequest(request, 'POST', '/api/orders', staffToken, {
    restaurant_id: restaurantId,
    type: 'local',
    status: 'pending',
    total: 30,
    notes: 'Orden E2E real',
  })

  await apiRequest(request, 'POST', '/api/order-items', staffToken, {
    order_id: order.id,
    product_id: product.id,
    quantity: 2,
  })

  const staffMe = await apiRequest(request, 'GET', '/api/me', staffToken)

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
  }, { token: staffToken, user: staffMe })

  await page.goto(`/cocina/${restaurantId}`)
  await expect(page.getByRole('heading', { name: `Orden #${order.id}` })).toBeVisible()
  await page.getByRole('button', { name: 'Iniciar preparación' }).click()
  await page.getByRole('button', { name: 'Marcar lista' }).click()
  await expect(page.getByText('No hay órdenes nuevas en este momento.')).toBeVisible()

  await page.goto(`/caja/${restaurantId}`)
  await expect(page.getByRole('heading', { name: `Orden #${order.id}` })).toBeVisible()
  await page.getByRole('button', { name: 'Efectivo' }).click()
  await expect(page.getByText('No hay órdenes pendientes de pago en este momento.')).toBeVisible()
  await page.getByRole('button', { name: '📋 Ver historial del día' }).click()
  await expect(page.getByRole('heading', { name: `Orden #${order.id}` })).toBeVisible()

  const orderAfterPayment = await apiRequest(request, 'GET', `/api/orders/${order.id}`, staffToken)
  expect(String(orderAfterPayment.status).toLowerCase()).toBe('paid')
  expect(Array.isArray(orderAfterPayment.payments)).toBe(true)
  expect(orderAfterPayment.payments.some((payment) => String(payment.status).toLowerCase() === 'succeeded')).toBe(true)

  const adminLogin = await apiRequest(request, 'POST', '/api/login', null, {
    email: adminEmail,
    password: adminPassword,
  })
  const adminToken = adminLogin.token
  const adminMe = await apiRequest(request, 'GET', '/api/me', adminToken)

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
  }, { token: adminToken, user: adminMe })

  await page.goto('/admin/reports')

  const restaurantCard = page.locator('.restaurant-summary-card').filter({ hasText: restaurantName }).first()
  await expect(restaurantCard).toBeVisible()
  await expect(restaurantCard).toContainText('Pedidos:')
  await expect(restaurantCard).toContainText('1')
  await expect(restaurantCard).toContainText('Ventas:')
  await expect(restaurantCard).toContainText('$30.00')
})
