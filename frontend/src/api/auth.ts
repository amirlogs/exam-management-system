import api from './axios'

export const login = async (email: string, password: string) => {
  return await api.post('/auth/login', { email, password })
}

export const logout = async () => {
  return await api.post('/auth/logout')
}

export const fetchCurrentUser = async () => {
  return await api.get('/auth/me')
}
