import { test, expect } from '@playwright/test'

test('staff usa siempre su restaurante asignado en staff y cocina', async ({ page }) => {
  const assignedRestaurantId = 7

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 70,
        name: 'Staff Demo',
        email: 'staff.demo@test.local',
        role: { name: 'staff' },
        restaurant_id: assignedRestaurantId,
        restaurant_name: 'Restaurante Siete',
      }),
    })
  })

  await page.route(`**/api/restaurants/${assignedRestaurantId}`, async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: assignedRestaurantId,
        name: 'Restaurante Siete',
        address: 'Calle 7',
      }),
    })
  })

  await page.route('**/api/orders', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 701,
          restaurant_id: assignedRestaurantId,
          restaurant: { id: assignedRestaurantId, name: 'Restaurante Siete' },
          status: 'pending',
          type: 'local',
          total: 25,
          order_items: [],
          payments: [],
        },
      ]),
    })
  })

  await page.addInitScript((restaurantId) => {
    localStorage.setItem('token', 'token-staff-test')
    localStorage.setItem('user', JSON.stringify({
      id: 70,
      name: 'Staff Demo',
      email: 'staff.demo@test.local',
      role: { name: 'staff' },
      restaurant_id: restaurantId,
      restaurant_name: 'Restaurante Siete',
    }))
  }, assignedRestaurantId)

  await page.goto('/staff')
  await expect(page).toHaveURL(`/staff/${assignedRestaurantId}`)
  await expect(page.getByRole('heading', { name: '🍽️ Restaurante Siete' })).toBeVisible()

  await page.goto('/cocina/99')
  await expect(page).toHaveURL(`/cocina/${assignedRestaurantId}`)
})
