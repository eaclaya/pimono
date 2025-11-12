import { computed, ref } from 'vue'
import http, { clearAuthToken, setAuthToken } from '@/services/http'

const STORAGE_KEY = 'token'
const USER_STORAGE_KEY = 'user'


const getStoredToken = () => {
  if (typeof window === 'undefined') return ''
  return window.localStorage.getItem(STORAGE_KEY) ?? ''
}

const getStoredUser = () => {
  if (typeof window === 'undefined') return null
  try {
    const stored = window.localStorage.getItem(USER_STORAGE_KEY)
    return stored ? JSON.parse(stored) : null
  } catch (error) {
    console.error('Failed to parse stored user data:', error)
    return null
  }
}

const token = ref(getStoredToken())
const user = ref(getStoredUser())
const loginError = ref('')
const loginLoading = ref(false)
const successMessage = ref('')

if (token.value) {
  setAuthToken(token.value)
}

const isAuthenticated = computed(() => Boolean(token.value))


const persistToken = (value) => {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(STORAGE_KEY, value)
}

const persistUser = (userData) => {
  if (typeof window === 'undefined') return
  if (!userData) return
  window.localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(userData))
}

const clearStoredToken = () => {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(STORAGE_KEY)
}

const clearStoredUser = () => {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(USER_STORAGE_KEY)
}

const setToken = (value) => {
  token.value = value
  if (value) {
    persistToken(value)
    setAuthToken(value)
    return
  }
  clearStoredToken()
  clearAuthToken()
}

const login = async ({ email, password }) => {
  if (loginLoading.value) return false

  loginError.value = ''
  successMessage.value = ''
  loginLoading.value = true

  try {
    const response = await http.post('/auth/login', { email, password })

    const payload = response.data ?? {}
    const receivedToken = extractToken(payload)
    const receivedUser = extractUser(payload)

    if (!receivedToken) {
      throw new Error('Login succeeded but no access token was returned.')
    }

    setToken(receivedToken)

    // Store user data from login response
    if (receivedUser) {
      user.value = receivedUser
      persistUser(receivedUser)
    }

    successMessage.value = 'You are now signed in.'
    return true
  } catch (error) {
    console.error(error)
    loginError.value = extractAxiosMessage(error, 'Unexpected error while signing in.')
    return false
  } finally {
    loginLoading.value = false
  }
}

const updateUser = (updates) => {
  if (!user.value) return

  const updatedUser = {
    ...user.value,
    ...updates,
  }

  user.value = updatedUser
  persistUser(updatedUser)
}

const logout = () => {
  setToken('')
  user.value = null
  clearStoredUser()
  successMessage.value = ''
  loginError.value = ''
}

const extractToken = (payload) => {
  if (!payload) return ''
  return payload.token ?? payload.access_token ?? payload?.data?.token ?? ''
}

const extractUser = (payload) => {
  if (!payload) return null
  // Check for user object in various possible locations
  return payload.user ?? payload?.data?.user ?? null
}

const extractAxiosMessage = (error, fallback) => {
  if (error?.response?.data?.message) return error.response.data.message
  if (error?.response?.data?.error) return error.response.data.error
  if (typeof error?.message === 'string' && error.message.length > 0) return error.message
  return fallback
}

export default function useAuth() {
  return {
    token,
    user,
    isAuthenticated,
    loginError,
    loginLoading,
    successMessage,
    login,
    logout,
    updateUser,
  }
}
