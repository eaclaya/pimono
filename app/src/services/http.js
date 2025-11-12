import axios from 'axios'

const API_BASE_URL = import.meta.env?.VITE_API_URL

if (!API_BASE_URL) {
  throw new Error('VITE_API_URL is not defined')
}

let authToken = ''

export const http = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
  withCredentials: true,
})

http.interceptors.request.use((config) => {
  if (authToken) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${authToken}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error),
)

export function setAuthToken(token) {
  authToken = token || ''
}

export function clearAuthToken() {
  authToken = ''
}

export default http
