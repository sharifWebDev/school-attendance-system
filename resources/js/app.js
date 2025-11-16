// resources/js/app.js
import { createApp } from 'vue';
import router from './router';
import '../css/app.css';

// Import Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// Create Vue app
const app = createApp({});

// Use router
app.use(router);

// Mount the app
app.mount('#app');
