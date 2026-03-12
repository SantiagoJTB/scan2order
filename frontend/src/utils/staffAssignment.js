export function buildStaffAssignments(restaurants = []) {
  const assignments = new Map()

  restaurants.forEach((restaurant) => {
    if (!Array.isArray(restaurant?.staffs)) return

    restaurant.staffs.forEach((staff) => {
      assignments.set(Number(staff.id), {
        id: Number(restaurant.id),
        name: restaurant.name || `Restaurante #${restaurant.id}`
      })
    })
  })

  return assignments
}

export function mapStaffOptionsWithAssignment({
  staffOptions = [],
  restaurants = [],
  currentRestaurantId = null,
  selectedAdminId = null,
  isSuperadmin = false,
}) {
  const assignments = buildStaffAssignments(restaurants)

  return staffOptions.map((staff) => {
    const assignment = assignments.get(Number(staff.id))
    const assignedRestaurantId = assignment?.id ?? null
    const assignedRestaurantName = assignment?.name ?? null

    const isAssignedInOtherRestaurant = assignedRestaurantId && Number(assignedRestaurantId) !== Number(currentRestaurantId)
    const belongsToSelectedAdmin = selectedAdminId ? Number(staff.created_by) === Number(selectedAdminId) : true

    let disabledReason = null
    if (isSuperadmin && selectedAdminId && !belongsToSelectedAdmin) {
      disabledReason = 'No pertenece al admin del restaurante'
    }

    return {
      ...staff,
      assignedRestaurantId,
      assignedRestaurantName,
      disabled: Boolean(isAssignedInOtherRestaurant || (isSuperadmin && selectedAdminId && !belongsToSelectedAdmin)),
      disabledReason,
    }
  })
}
