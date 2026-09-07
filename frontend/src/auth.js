import { reactive } from 'vue'
import axios from 'axios'

export const auth = reactive({
  user: JSON.parse(localStorage.getItem('user') || 'null'),
  token: localStorage.getItem('token') || null,
})

export const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Если токен невалиден — очищаем и редиректим на логин
      auth.token = null
      auth.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export async function login(email, password) {
  const { data } = await api.post('/login', { email, password })
  auth.token = data.token
  auth.user = data.user
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
}

export async function register(name, email, password, password_confirmation) {
  const { data } = await api.post('/register', {
    name,
    email,
    password,
    password_confirmation,
  })
  auth.token = data.token
  auth.user = data.user
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
}

export async function logout() {
  try {
    await api.post('/logout')
  } catch (e) {
    // Игнорируем ошибки при logout
  }
  auth.token = null
  auth.user = null
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}