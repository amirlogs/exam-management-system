import axios from 'axios'

export const login = async (email: string, password: string) => {
  try {
    // const response = await api.post('/login', { email, password })
    // return response.data
    const response = await axios.post('/login', { email, password })
    return response.data
  } catch (error) {
    console.error(error)
    throw error
  }
}
