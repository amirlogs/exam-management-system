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
// api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
//   config.headers.set('Authorization', `Bearer ${getToken()}`)
//   return config
// })

// api.interceptors.response.use(
//   (response: AxiosResponse) => response,
//   (error) => Promise.reject(error),
// )

export default api
