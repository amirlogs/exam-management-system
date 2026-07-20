import * as authapi from '@/api/auth'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('userAuth', () => {
  const user = ref({})

  const login = (email: string, password: string) => {
    try {
      const response = authapi.login(email, password)
      console.log(response)
      user.value = response
    } catch (error) {
      console.log(error)
    }
  }

  return { user, login }
})
