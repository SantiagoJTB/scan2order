export function isPaymentSucceeded(payment) {
  const paymentStatus = String(payment?.status || '').toLowerCase()
  return paymentStatus === 'succeeded' || Boolean(payment?.paid_at)
}

export function isOrderCollected(order) {
  const status = String(order?.status || '').toLowerCase()
  if (status === 'paid') return true

  const payments = Array.isArray(order?.payments) ? order.payments : []
  return payments.some(isPaymentSucceeded)
}

export function isOrderPendingPayment(order) {
  const status = String(order?.status || '').toLowerCase()
  if (status === 'cancelled') return false
  return !isOrderCollected(order)
}

export function getOrderCollectionReferenceDate(order) {
  const payments = Array.isArray(order?.payments) ? order.payments : []
  const collectedPayment = payments.find(isPaymentSucceeded)
  return collectedPayment?.paid_at || collectedPayment?.created_at || order?.updated_at || null
}

export function isSameDay(dateLike, referenceDate = new Date()) {
  if (!dateLike) return false
  const date = new Date(dateLike)
  if (Number.isNaN(date.getTime())) return false

  const ref = new Date(referenceDate)
  return date.getFullYear() === ref.getFullYear()
    && date.getMonth() === ref.getMonth()
    && date.getDate() === ref.getDate()
}

export function isOrderCollectedToday(order, referenceDate = new Date()) {
  if (!isOrderCollected(order)) return false
  const reference = getOrderCollectionReferenceDate(order)
  return isSameDay(reference, referenceDate)
}
