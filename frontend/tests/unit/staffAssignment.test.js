import { mapStaffOptionsWithAssignment } from '../../src/utils/staffAssignment'

describe('staffAssignment utils', () => {
  const staffOptions = [
    { id: 1, name: 'Staff A', email: 'a@test.local', created_by: 10 },
    { id: 2, name: 'Staff B', email: 'b@test.local', created_by: 20 },
    { id: 3, name: 'Staff C', email: 'c@test.local', created_by: 10 },
  ]

  const restaurants = [
    { id: 100, name: 'Local A', staffs: [{ id: 1 }] },
    { id: 200, name: 'Local B', staffs: [{ id: 2 }] },
  ]

  it('marks staff assigned in other restaurant as disabled', () => {
    const result = mapStaffOptionsWithAssignment({
      staffOptions,
      restaurants,
      currentRestaurantId: 100,
      selectedAdminId: 10,
      isSuperadmin: true,
    })

    const staffB = result.find((s) => s.id === 2)
    expect(staffB.disabled).toBe(true)
    expect(staffB.assignedRestaurantName).toBe('Local B')
  })

  it('marks staff not belonging to selected admin as disabled for superadmin', () => {
    const result = mapStaffOptionsWithAssignment({
      staffOptions,
      restaurants,
      currentRestaurantId: 100,
      selectedAdminId: 10,
      isSuperadmin: true,
    })

    const staffB = result.find((s) => s.id === 2)
    expect(staffB.disabled).toBe(true)
  })

  it('keeps available staff enabled', () => {
    const result = mapStaffOptionsWithAssignment({
      staffOptions,
      restaurants,
      currentRestaurantId: 100,
      selectedAdminId: 10,
      isSuperadmin: true,
    })

    const staffC = result.find((s) => s.id === 3)
    expect(staffC.disabled).toBe(false)
  })
})
