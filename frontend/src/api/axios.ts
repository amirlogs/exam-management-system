import type { AxiosInstance } from 'axios'
import axios from 'axios'

const api: AxiosInstance = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
  },
})

//axios interceptors
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    console.log('interceptor adding a token ')
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      // Optional: force redirect to login
    }
    return Promise.reject(error)
  },
)

export default api
