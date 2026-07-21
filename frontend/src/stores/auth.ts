import * as authapi from '@/api/auth'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('userAuth', () => {
  const user = ref<Record<string, any> | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const error = ref('')
  const loading = ref(false)

  const login = async (email: string, password: string) => {
    error.value = ''
    loading.value = true
    try {
      const response = await authapi.login(email, password)
      const { token: authToken, user: userData } = response.data.data
      user.value = userData
      token.value = authToken
      localStorage.setItem('auth_token', authToken)

      return true
    } catch (err) {
      error.value = 'Invalid email or password. Please try again.'
      console.error(err)
      return false
    } finally {
      loading.value = false
    }
  }

  return { user, error, loading, login }
})
