import { createRouter, createWebHistory } from 'vue-router';
import Health from '../components/Health.vue';
import Login from '../components/Login.vue';
import Register from '../components/Register.vue';
import Restaurants from '../components/Restaurants.vue';
import Products from '../components/Products.vue';
import Orders from '../components/Orders.vue';
import OrderDetail from '../components/OrderDetail.vue';
import Categories from '../components/Categories.vue';
import Tables from '../components/Tables.vue';

const routes = [
  { path: '/', component: Health },
  { path: '/health', component: Health },
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/restaurants', component: Restaurants, meta: { requiresAuth: true } },
  { path: '/categories', component: Categories, meta: { requiresAuth: true } },
  { path: '/products', component: Products, meta: { requiresAuth: true } },
  { path: '/tables', component: Tables, meta: { requiresAuth: true } },
  { path: '/orders', component: Orders, meta: { requiresAuth: true } },
  { path: '/orders/:id', component: OrderDetail, props: true, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// global guard
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('api_token');
    if (!token) {
      return next('/login');
    }
  }
  next();
});

export default router;
