import '@fontsource-variable/jetbrains-mono'
import '@fontsource/lora/500.css'
import '@fontsource/lora/600.css'
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import './assets/main.css'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
app.use(createPinia())
const authStore = useAuthStore()

authStore.initializeAuth(router).finally(() => {
  app.use(router)
  app.mount('#app')
})
