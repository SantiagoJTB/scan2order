import { test, expect } from '@playwright/test'

test('superadmin solo puede asignar staff del admin del restaurante', async ({ page }) => {
  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 1,
        name: 'Super Admin',
        role: { name: 'superadmin' },
      }),
    })
  })

  await page.route('**/api/restaurants', async (route) => {
    if (route.request().method() !== 'GET') {
      return route.continue()
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 1,
          name: 'Casa Dana',
          address: 'Plaza Mayor',
          phone: '555-1111',
          active: true,
          created_at: new Date().toISOString(),
          admins: [{ id: 10, name: 'Admin Dana', email: 'admin.dana@test.local' }],
          staffs: [{ id: 200, name: 'Staff Ocupado', email: 'staff.ocupado@test.local' }],
        },
        {
          id: 2,
          name: 'Otro Local',
          address: 'Calle 2',
          phone: '555-2222',
          active: true,
          created_at: new Date().toISOString(),
          admins: [{ id: 20, name: 'Otro Admin', email: 'otro.admin@test.local' }],
          staffs: [{ id: 201, name: 'Staff Otro', email: 'staff.otro@test.local' }],
        },
      ]),
    })
  })

  await page.route('**/api/users', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        { id: 10, name: 'Admin Dana', email: 'admin.dana@test.local', role: { name: 'admin' } },
        { id: 20, name: 'Otro Admin', email: 'otro.admin@test.local', role: { name: 'admin' } },
        { id: 101, name: 'Staff Dana', email: 'staff.dana@test.local', created_by: 10, role: { name: 'staff' } },
        { id: 102, name: 'Staff No Dana', email: 'staff.no.dana@test.local', created_by: 20, role: { name: 'staff' } },
        { id: 201, name: 'Staff Otro', email: 'staff.otro@test.local', created_by: 20, role: { name: 'staff' } },
      ]),
    })
  })

  await page.addInitScript(() => {
    localStorage.setItem('token', 'token-test')
    localStorage.setItem('user', JSON.stringify({
      id: 1,
      name: 'Super Admin',
      role: { name: 'superadmin' },
    }))
  })

  await page.goto('/admin/restaurants')

  await page.locator('summary', { hasText: 'Más opciones' }).first().click()
  await page.getByRole('button', { name: 'Asignar staff' }).first().click()

  await expect(page.getByText('No pertenece al admin del restaurante').first()).toBeVisible()

  const foreignStaffCheckbox = page
    .locator('label.admin-item', { hasText: 'Staff No Dana' })
    .locator('input[type="checkbox"]')

  await expect(foreignStaffCheckbox).toBeDisabled()
})
