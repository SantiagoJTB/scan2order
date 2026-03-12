<template>
  <div class="reports-container" v-if="canAccessReports">
    <div class="header">
      <h1>📊 Informes</h1>
      <p>Métricas clave de ventas y consumo</p>
    </div>

    <div class="toolbar">
      <label>
        Últimos días:
        <select v-model.number="rangeDays" @change="buildReports">
          <option :value="7">7 días</option>
          <option :value="15">15 días</option>
          <option :value="30">30 días</option>
        </select>
      </label>
      <button class="btn-refresh" @click="fetchOrders" :disabled="isLoading">
        {{ isLoading ? 'Cargando...' : 'Actualizar' }}
      </button>
    </div>

    <div class="kpis">
      <div class="kpi-card">
        <span class="kpi-label">Pedidos analizados</span>
        <span class="kpi-value">{{ kpis.ordersCount }}</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Ventas totales</span>
        <span class="kpi-value">${{ kpis.totalSales.toFixed(2) }}</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Ticket promedio</span>
        <span class="kpi-value">${{ kpis.averageTicket.toFixed(2) }}</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Hora pico</span>
        <span class="kpi-value">{{ peakHourLabel }}</span>
      </div>
    </div>

    <div class="sections-grid">
      <section class="report-card">
        <h2>🕒 Horas con más ventas</h2>
        <div v-if="topHours.length === 0" class="empty">Sin datos</div>
        <ul v-else>
          <li v-for="hour in topHours" :key="hour.hour">
            <span>{{ hour.hour }}:00</span>
            <strong>{{ hour.orders }} pedidos</strong>
            <em>${{ hour.sales.toFixed(2) }}</em>
          </li>
        </ul>
      </section>

      <section class="report-card">
        <h2>🍽️ Productos más vendidos</h2>
        <div v-if="topProducts.length === 0" class="empty">Sin datos</div>
        <ul v-else>
          <li v-for="product in topProducts" :key="product.name">
            <span>{{ product.name }}</span>
            <strong>{{ product.quantity }} uds</strong>
          </li>
        </ul>
      </section>

      <section class="report-card full-width">
        <h2>📅 Ventas por día</h2>
        <div v-if="salesByDay.length === 0" class="empty">Sin datos</div>
        <ul v-else class="daily-list">
          <li v-for="day in salesByDay" :key="day.dateKey">
            <span>{{ day.displayDate }}</span>
            <strong>{{ day.orders }} pedidos</strong>
            <em>${{ day.sales.toFixed(2) }}</em>
          </li>
        </ul>
      </section>
    </div>

    <section class="report-card restaurant-reports-section">
      <div class="restaurant-reports-header">
        <h2>🏬 Informes por restaurante</h2>
        <label>
          Ver detalle de:
          <select v-model.number="selectedRestaurantId">
            <option :value="0">Seleccionar restaurante</option>
            <option v-for="report in restaurantReports" :key="report.restaurantId" :value="report.restaurantId">
              {{ report.restaurantName }}
            </option>
          </select>
        </label>
      </div>

      <div v-if="restaurantReports.length === 0" class="empty">Sin datos</div>

      <div v-else class="restaurant-summary-grid">
        <article v-for="report in restaurantReports" :key="report.restaurantId" class="restaurant-summary-card">
          <h3>{{ report.restaurantName }}</h3>
          <p><strong>Pedidos:</strong> {{ report.ordersCount }}</p>
          <p><strong>Ventas:</strong> ${{ report.totalSales.toFixed(2) }}</p>
          <p><strong>Ticket promedio:</strong> ${{ report.averageTicket.toFixed(2) }}</p>
        </article>
      </div>

      <div v-if="selectedRestaurantReport" class="restaurant-detail-grid">
        <section class="report-card">
          <h2>🕒 Horas pico · {{ selectedRestaurantReport.restaurantName }}</h2>
          <div v-if="selectedRestaurantReport.topHours.length === 0" class="empty">Sin datos</div>
          <ul v-else>
            <li v-for="hour in selectedRestaurantReport.topHours" :key="`r-${selectedRestaurantReport.restaurantId}-${hour.hour}`">
              <span>{{ hour.hour }}:00</span>
              <strong>{{ hour.orders }} pedidos</strong>
              <em>${{ hour.sales.toFixed(2) }}</em>
            </li>
          </ul>
        </section>

        <section class="report-card">
          <h2>🍽️ Top productos · {{ selectedRestaurantReport.restaurantName }}</h2>
          <div v-if="selectedRestaurantReport.topProducts.length === 0" class="empty">Sin datos</div>
          <ul v-else>
            <li v-for="product in selectedRestaurantReport.topProducts" :key="`r-${selectedRestaurantReport.restaurantId}-${product.name}`">
              <span>{{ product.name }}</span>
              <strong>{{ product.quantity }} uds</strong>
            </li>
          </ul>
        </section>

        <section class="report-card full-width">
          <h2>📅 Ventas por día · {{ selectedRestaurantReport.restaurantName }}</h2>
          <div v-if="selectedRestaurantReport.salesByDay.length === 0" class="empty">Sin datos</div>
          <ul v-else class="daily-list">
            <li v-for="day in selectedRestaurantReport.salesByDay" :key="`r-${selectedRestaurantReport.restaurantId}-${day.dateKey}`">
              <span>{{ day.displayDate }}</span>
              <strong>{{ day.orders }} pedidos</strong>
              <em>${{ day.sales.toFixed(2) }}</em>
            </li>
          </ul>
        </section>
      </div>
    </section>
  </div>

  <div v-else class="reports-container unauthorized">
    <h2>⛔ Acceso denegado</h2>
    <p>Solo Admin y Superadmin pueden ver informes.</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { getOrderCollectionReferenceDate, isOrderCollected } from '../../utils/orderPayments'

const auth = useAuthStore()
const canAccessReports = computed(() => auth.hasAnyRole(['admin', 'superadmin']))
const isLoading = ref(false)
const rangeDays = ref(30)
const orders = ref([])

const kpis = ref({
  ordersCount: 0,
  totalSales: 0,
  averageTicket: 0,
  peakHour: null
})

const topHours = ref([])
const topProducts = ref([])
const salesByDay = ref([])
const restaurantReports = ref([])
const selectedRestaurantId = ref(0)

const selectedRestaurantReport = computed(() => {
  if (!selectedRestaurantId.value) return null
  return restaurantReports.value.find(r => r.restaurantId === selectedRestaurantId.value) || null
})

const peakHourLabel = computed(() => {
  if (kpis.value.peakHour === null || kpis.value.peakHour === undefined) return 'Sin datos'
  const hour = String(kpis.value.peakHour).padStart(2, '0')
  return `${hour}:00`
})

function getOrderItems(order) {
  if (Array.isArray(order.order_items)) return order.order_items
  if (Array.isArray(order.orderItems)) return order.orderItems
  return []
}

function getOrderDate(order) {
  const value = getOrderCollectionReferenceDate(order) || order.updated_at || order.created_at
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : date
}

function isCountableOrder(order) {
  const status = String(order.status || '').toLowerCase()
  return !['cancelled'].includes(status) && isOrderCollected(order)
}

function inRange(date) {
  const now = new Date()
  const start = new Date(now)
  start.setHours(0, 0, 0, 0)
  start.setDate(start.getDate() - (rangeDays.value - 1))
  return date >= start && date <= now
}

function buildReportFromEntries(entries, restaurantMeta = null) {
  let totalSales = 0
  const hoursMap = new Map()
  const productsMap = new Map()
  const daysMap = new Map()

  for (const entry of entries) {
    const order = entry.order
    const date = entry.date
    const amount = Number(order.total || 0)

    totalSales += amount

    const hour = date.getHours()
    const currentHour = hoursMap.get(hour) || { hour, orders: 0, sales: 0 }
    currentHour.orders += 1
    currentHour.sales += amount
    hoursMap.set(hour, currentHour)

    const dateKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    const currentDay = daysMap.get(dateKey) || {
      dateKey,
      displayDate: date.toLocaleDateString('es-ES'),
      orders: 0,
      sales: 0
    }
    currentDay.orders += 1
    currentDay.sales += amount
    daysMap.set(dateKey, currentDay)

    for (const item of getOrderItems(order)) {
      const name = item?.product?.name || `Producto #${item?.product_id || 'N/A'}`
      const quantity = Number(item?.quantity || 0)
      if (quantity <= 0) continue
      productsMap.set(name, (productsMap.get(name) || 0) + quantity)
    }
  }

  const hours = Array.from(hoursMap.values()).sort((a, b) => b.orders - a.orders)
  const products = Array.from(productsMap.entries())
    .map(([name, quantity]) => ({ name, quantity }))
    .sort((a, b) => b.quantity - a.quantity)
  const days = Array.from(daysMap.values()).sort((a, b) => b.dateKey.localeCompare(a.dateKey))

  return {
    restaurantId: Number(restaurantMeta?.id || 0),
    restaurantName: restaurantMeta?.name || 'Global',
    ordersCount: entries.length,
    totalSales,
    averageTicket: entries.length ? totalSales / entries.length : 0,
    peakHour: hours.length ? hours[0].hour : null,
    topHours: hours.slice(0, 6),
    topProducts: products.slice(0, 10),
    salesByDay: days
  }
}

function buildReports() {
  const filteredOrders = orders.value
    .filter(isCountableOrder)
    .map((order) => ({ order, date: getOrderDate(order) }))
    .filter((entry) => entry.date && inRange(entry.date))

  const globalReport = buildReportFromEntries(filteredOrders)
  topHours.value = globalReport.topHours
  topProducts.value = globalReport.topProducts
  salesByDay.value = globalReport.salesByDay

  kpis.value = {
    ordersCount: globalReport.ordersCount,
    totalSales: globalReport.totalSales,
    averageTicket: globalReport.averageTicket,
    peakHour: globalReport.peakHour
  }

  const byRestaurant = new Map()
  filteredOrders.forEach((entry) => {
    const restaurantId = Number(entry.order?.restaurant_id || 0)
    if (!restaurantId) return
    if (!byRestaurant.has(restaurantId)) {
      byRestaurant.set(restaurantId, {
        restaurant: {
          id: restaurantId,
          name: entry.order?.restaurant?.name || `Restaurante #${restaurantId}`
        },
        entries: []
      })
    }
    byRestaurant.get(restaurantId).entries.push(entry)
  })

  restaurantReports.value = Array.from(byRestaurant.values())
    .map(({ restaurant, entries }) => buildReportFromEntries(entries, restaurant))
    .sort((a, b) => b.totalSales - a.totalSales)

  if (selectedRestaurantId.value && !restaurantReports.value.some(r => r.restaurantId === selectedRestaurantId.value)) {
    selectedRestaurantId.value = 0
  }
}

async function fetchOrders() {
  if (!auth.token || !canAccessReports.value) return
  isLoading.value = true

  try {
    const response = await fetch('/api/orders', {
      headers: {
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (!response.ok) throw new Error('No se pudieron cargar órdenes')

    const data = await response.json()
    orders.value = Array.isArray(data) ? data : []
    buildReports()
  } catch (err) {
    orders.value = []
    buildReports()
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  if (!auth.initialized) {
    await auth.initFromStorage()
  }

  if (!canAccessReports.value) return
  await fetchOrders()
})
</script>

<style scoped>
.reports-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem 0;
}

.header {
  margin-bottom: 1rem;
  color: white;
}

.header h1 {
  margin: 0;
}

.header p {
  margin: 0.4rem 0 0;
}

.toolbar {
  background: white;
  border-radius: 10px;
  padding: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.toolbar label {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  color: #2c3e50;
  font-weight: 600;
}

.toolbar select {
  padding: 0.35rem 0.5rem;
  border: 1px solid #bdc3c7;
  border-radius: 6px;
}

.btn-refresh {
  border: none;
  background: #667eea;
  color: white;
  padding: 0.5rem 0.85rem;
  border-radius: 6px;
  cursor: pointer;
}

.kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.kpi-card {
  background: white;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.kpi-label {
  display: block;
  color: #7f8c8d;
  font-size: 0.86rem;
}

.kpi-value {
  display: block;
  margin-top: 0.3rem;
  color: #2c3e50;
  font-size: 1.5rem;
  font-weight: 700;
}

.sections-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.report-card {
  background: white;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.report-card h2 {
  margin: 0 0 0.75rem;
  color: #2c3e50;
  font-size: 1.05rem;
}

.report-card ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.report-card li {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 0.75rem;
  align-items: center;
  border-bottom: 1px solid #eef2f7;
  padding: 0.5rem 0;
  color: #34495e;
}

.report-card li:last-child {
  border-bottom: none;
}

.report-card li em {
  color: #2c7a7b;
  font-style: normal;
  font-weight: 700;
}

.restaurant-reports-section {
  margin-top: 1rem;
}

.restaurant-reports-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.restaurant-reports-header label {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  color: #2c3e50;
  font-weight: 600;
}

.restaurant-reports-header select {
  padding: 0.35rem 0.5rem;
  border: 1px solid #bdc3c7;
  border-radius: 6px;
}

.restaurant-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.restaurant-summary-card {
  border: 1px solid #eef2f7;
  border-radius: 8px;
  padding: 0.8rem;
  background: #fafbff;
}

.restaurant-summary-card h3 {
  margin: 0 0 0.4rem;
  color: #2c3e50;
  font-size: 1rem;
}

.restaurant-summary-card p {
  margin: 0.2rem 0;
  color: #4a5568;
}

.restaurant-detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.full-width {
  grid-column: 1 / -1;
}

.daily-list li {
  grid-template-columns: 1fr auto auto;
}

.empty {
  color: #7f8c8d;
}

.unauthorized {
  color: white;
}

@media (max-width: 900px) {
  .sections-grid {
    grid-template-columns: 1fr;
  }

  .restaurant-reports-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .restaurant-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
