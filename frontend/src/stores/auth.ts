import * as authapi from '@/api/auth'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('userAuth', () => {
  const user = ref({})
  const error = ref('')
  const loading = ref(false)

  const login = (email: string, password: string) => {
    error.value = ''
    loading.value = true
    try {
      const response = authapi.login(email, password)
      console.log(response)
      user.value = response
    } catch (error) {
      error.value = 'Invalid email or password. Please try again.'
    } finally {
      loading.value = false
    }
  }

  return { user, error, loading, login }
})
