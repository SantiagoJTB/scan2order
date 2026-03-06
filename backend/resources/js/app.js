import './bootstrap';
import axios from 'axios';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';

// configure axios base URL and token
axios.defaults.baseURL = '/';
const token = localStorage.getItem('api_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// redirect to login on unauthorized responses
axios.interceptors.response.use(
    res => res,
    err => {
        if (err.response && err.response.status === 401) {
            localStorage.removeItem('api_token');
            delete axios.defaults.headers.common['Authorization'];
            window.location = '/login';
        }
        return Promise.reject(err);
    }
);

const app = createApp(App);
app.use(router);
app.mount('#app');
