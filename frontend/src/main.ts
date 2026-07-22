import './assets/main.css'

import { createPinia } from 'pinia'
import { createApp } from 'vue'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
app.use(createPinia())
const authStore = useAuthStore()

authStore.initializeAuth().finally(() => {
  app.use(router)
  app.mount('#app')
})
