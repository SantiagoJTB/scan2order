import {
  isPaymentSucceeded,
  isOrderCollected,
  isOrderPendingPayment,
  isOrderCollectedToday,
} from '../../src/utils/orderPayments'

describe('orderPayments utils', () => {
  it('detects succeeded payment by status', () => {
    expect(isPaymentSucceeded({ status: 'succeeded' })).toBe(true)
    expect(isPaymentSucceeded({ status: 'pending' })).toBe(false)
  })

  it('detects succeeded payment by paid_at fallback', () => {
    expect(isPaymentSucceeded({ status: 'pending', paid_at: '2026-03-09T10:00:00Z' })).toBe(true)
  })

  it('marks order collected when status is paid', () => {
    expect(isOrderCollected({ status: 'paid', payments: [] })).toBe(true)
  })

  it('marks order pending payment when cancelled is false and no succeeded payments', () => {
    expect(isOrderPendingPayment({ status: 'processing', payments: [{ status: 'pending' }] })).toBe(true)
  })

  it('does not mark collected orders as pending in caja', () => {
    expect(isOrderPendingPayment({ status: 'completed', payments: [{ status: 'succeeded' }] })).toBe(false)
  })

  it('detects collected today by payment date', () => {
    const today = new Date('2026-03-09T12:00:00Z')
    const order = {
      status: 'completed',
      payments: [{ status: 'succeeded', paid_at: '2026-03-09T08:00:00Z' }],
      updated_at: '2026-03-08T08:00:00Z',
    }

    expect(isOrderCollectedToday(order, today)).toBe(true)
  })

  it('does not detect collected today when collection date is previous day', () => {
    const today = new Date('2026-03-09T12:00:00Z')
    const order = {
      status: 'paid',
      payments: [{ status: 'succeeded', paid_at: '2026-03-08T23:00:00Z' }],
      updated_at: '2026-03-08T23:00:00Z',
    }

    expect(isOrderCollectedToday(order, today)).toBe(false)
  })
})
