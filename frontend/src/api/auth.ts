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

export const allowedRoutes = async (workspace: string) => {
  return await api.get('/me/allowed-routes', { params: { workspace}})
}

export const setWorkspace = async (workspace: string) => {
  return await api.post('/me/workspace', { workspace })
}
