<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import LoginForm from './components/LoginForm.vue'
import TransferModal from './components/TransferModal.vue'
import UsersTable from './components/UsersTable.vue'
import http, { clearAuthToken, setAuthToken } from './services/http'

const STORAGE_KEY = 'sanctumDashboardToken'
const LOGIN_ENDPOINT = import.meta.env?.VITE_LOGIN_ENDPOINT ?? '/auth/login'
const USERS_ENDPOINT = import.meta.env?.VITE_USERS_ENDPOINT ?? '/recipients'
const TRANSFER_ENDPOINT = import.meta.env?.VITE_TRANSFER_ENDPOINT ?? '/transactions'

const loginForm = reactive({
  email: '',
  password: '',
})

const token = ref(getStoredToken())
const loginError = ref('')
const usersError = ref('')
const loginLoading = ref(false)
const usersLoading = ref(false)
const successMessage = ref('')
const users = ref([])
const lastFetchedAt = ref(null)
const transferModal = reactive({
  open: false,
  loading: false,
  error: '',
  success: '',
  user: null,
})

const isAuthenticated = computed(() => Boolean(token.value))
const canSubmitLogin = computed(
  () => loginForm.email.trim().length > 0 && loginForm.password.trim().length > 0 && !loginLoading.value,
)

function getStoredToken() {
  if (typeof window === 'undefined') return ''
  return window.localStorage.getItem(STORAGE_KEY) ?? ''
}

function persistToken(value) {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(STORAGE_KEY, value)
}

function clearStoredToken() {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(STORAGE_KEY)
}

function resetSession() {
  token.value = ''
  clearStoredToken()
  users.value = []
  closeTransferModal()
  clearAuthToken()
}

function openTransferModal(user) {
  transferModal.open = true
  transferModal.user = user ?? null
  transferModal.error = ''
  transferModal.success = ''
}

function closeTransferModal() {
  transferModal.open = false
  transferModal.loading = false
  transferModal.error = ''
  transferModal.success = ''
  transferModal.user = null
}

async function loginUser() {
  if (!canSubmitLogin.value) return

  loginError.value = ''
  usersError.value = ''
  successMessage.value = ''
  loginLoading.value = true

  try {
    const response = await http.post(
      LOGIN_ENDPOINT,
      {
        email: loginForm.email,
        password: loginForm.password,
      },
      {
        headers: {
          'Content-Type': 'application/json',
        },
      },
    )

    const payload = response.data ?? {}
    const receivedToken = extractToken(payload)

    if (!receivedToken) {
      throw new Error('Login succeeded but no access token was returned.')
    }

    token.value = receivedToken
    persistToken(receivedToken)
    setAuthToken(receivedToken)
    successMessage.value = 'You are now signed in.'
    await fetchUsers()
  } catch (error) {
    console.error(error)
    resetSession()
    loginError.value = extractAxiosMessage(error, 'Unexpected error while signing in.')
  } finally {
    loginLoading.value = false
  }
}

async function fetchUsers() {
  if (!token.value) return

  usersLoading.value = true
  usersError.value = ''

  try {
    const response = await http.get(USERS_ENDPOINT)

    users.value = normalizeUsers(response.data)
    lastFetchedAt.value = new Date()
  } catch (error) {
    console.error(error)
    if (error?.response?.status === 401) {
      resetSession()
      usersError.value = 'Your session expired. Please sign in again.'
      return
    }
    usersError.value = extractAxiosMessage(error, 'Unexpected error while loading users.')
  } finally {
    usersLoading.value = false
  }
}

function logout() {
  resetSession()
  successMessage.value = ''
  loginError.value = ''
  usersError.value = ''
}

async function handleTransferSubmit(payload) {
  transferModal.loading = true
  transferModal.error = ''
  transferModal.success = ''

  try {
    await http.post(TRANSFER_ENDPOINT, payload)
    transferModal.success = 'Transfer submitted successfully.'
  } catch (error) {
    transferModal.error = extractAxiosMessage(error, 'Could not submit the transfer.')
    return
  } finally {
    transferModal.loading = false
  }
}

function extractToken(payload) {
  if (!payload) return ''
  return payload.token ?? payload.access_token ?? payload?.data?.token ?? ''
}

function normalizeUsers(payload) {
  if (!payload) return []
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.users)) return payload.users
  return []
}

function extractAxiosMessage(error, fallback) {
  if (error?.response?.data?.message) return error.response.data.message
  if (error?.response?.data?.error) return error.response.data.error
  if (typeof error?.message === 'string' && error.message.length > 0) return error.message
  return fallback
}

const formattedLastFetched = computed(() => {
  if (!lastFetchedAt.value) return ''
  return lastFetchedAt.value.toLocaleString()
})

onMounted(() => {
  if (token.value) {
    setAuthToken(token.value)
    fetchUsers()
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">


      <LoginForm
        v-if="!isAuthenticated"
        :email="loginForm.email"
        :password="loginForm.password"
        :can-submit="canSubmitLogin"
        :loading="loginLoading"
        :error="loginError"
        :success="successMessage"
        @update:email="(value) => (loginForm.email = value)"
        @update:password="(value) => (loginForm.password = value)"
        @submit="loginUser"
      />

      <UsersTable
        v-else
        :users="users"
        :loading="usersLoading"
        :error="usersError"
        :last-fetched="formattedLastFetched"
        @refresh="fetchUsers"
        @logout="logout"
        @transfer="openTransferModal"
      />
    </div>

    <TransferModal
      :open="transferModal.open"
      :users="users"
      :selected-user="transferModal.user"
      :error="transferModal.error"
      :success="transferModal.success"
      :submitting="transferModal.loading"
      @close="closeTransferModal"
      @submit="handleTransferSubmit"
    />
  </div>
</template>
