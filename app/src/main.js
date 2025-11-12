import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import EchoPlugin from '@/plugins/echo'

createApp(App).use(router).use(EchoPlugin).mount('#app')
