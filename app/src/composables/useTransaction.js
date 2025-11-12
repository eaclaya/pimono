import { computed, reactive, ref, watch } from 'vue'
import http from '../services/http'
import useAuth from './useAuth'

const recipients = ref([])
const recipientsLoading = ref(false)
const recipientsError = ref('')

const transactions = ref([])
const transactionsLoading = ref(false)
const transactionsError = ref('')
const transactionsMeta = ref(null)
const incomingTransfer = ref(null)

const transactionModal = reactive({
  open: false,
  loading: false,
  error: '',
  success: '',
  user: null,
})

let pusher = null

const resetState = () => {
  recipients.value = []
  recipientsError.value = ''
  recipientsLoading.value = false
  transactions.value = []
  transactionsError.value = ''
  transactionsLoading.value = false
  transactionsMeta.value = null
  transactionModal.open = false
  transactionModal.loading = false
  transactionModal.error = ''
  transactionModal.success = ''
  transactionModal.user = null
  incomingTransfer.value = null
}

export default function useTransaction() {
  const auth = useAuth()

  const formattedBalance = computed(() => {
    if (!auth.user.value?.balance) return '$0.00'
    const balance = parseFloat(auth.user.value.balance)
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(balance)
  })

  const handleUnauthorized = () => {
    resetState()
    auth.logout()
  }

  const fetchUsers = async (options = {}) => {
    if (!auth.isAuthenticated.value) return

    recipientsLoading.value = true
    recipientsError.value = ''

    try {
      const params = {}
      const search = options?.search ?? options?.query ?? options?.q
      if (typeof search === 'string' && search.trim().length > 0) {
        params.q = search.trim()
      }

      const response = await http.get('/receivers', { params })
      recipients.value = response.data?.data ?? []
    } catch (error) {
      console.error(error)
      if (error?.response?.status === 401) {
        recipientsError.value = 'Your session expired. Please sign in again.'
        handleUnauthorized()
        return
      }
      recipientsError.value = extractAxiosMessage(error, 'Unexpected error while loading recipients.')
    } finally {
      recipientsLoading.value = false
    }
  }

  const fetchTransactions = async (params = {}) => {
    if (!auth.isAuthenticated.value) return

    transactionsLoading.value = true
    transactionsError.value = ''

    try {
      const response = await http.get('/transactions', { params })
      transactions.value = response.data?.data ?? []
      transactionsMeta.value = response.data?.meta ?? null
    } catch (error) {
      console.error(error)
      if (error?.response?.status === 401) {
        transactionsError.value = 'Your session expired. Please sign in again.'
        handleUnauthorized()
        return
      }
      transactionsError.value = extractAxiosMessage(error, 'Unexpected error while loading transactions.')
    } finally {
      transactionsLoading.value = false
    }
  }

  const syncUserBalance = (transaction) => {
    if (!auth.user.value || !transaction) return

    const currentBalance = parseFloat(auth.user.value.balance)
    const transactionAmount = parseFloat(transaction.amount)
    let newBalance = currentBalance

    if (transaction.sender.id == auth.user.value.id) {
      newBalance = currentBalance - transactionAmount
    } else if (transaction.receiver.id == auth.user.value.id) {
      newBalance = currentBalance + transactionAmount
    }

    auth.updateUser({ balance: newBalance.toFixed(2) })
  }

  const disconnectEcho = () => {
    const echo = window.Echo
    if (echo) {
      echo.disconnect()
    }
  }

  const handleIncomingTransaction = (transaction) => {
    if (!transaction) return
    const existingIndex = transactions.value.findIndex((item) => item.id === transaction.id)
    if (existingIndex >= 0) {
      transactions.value.splice(existingIndex, 1, transaction)
    } else {
      transactions.value = [transaction, ...transactions.value]
    }

    syncUserBalance(transaction)

    if (transaction?.receiver?.id == auth.user.value?.id) {
      incomingTransfer.value = { ...transaction }
    }
  }

  const subscribe = () => {
    if (!auth.token.value || pusher || !auth.user.value) return
    const echo = window.Echo
    if (!echo) return

    const userId = auth.user.value.id ?? auth.user.value.sub
    if (!userId) {
      console.error('Cannot subscribe to Pusher: user ID not found in stored user data')
      return
    }
    const channelName = `users.${userId}`
    pusher = echo.private(channelName)
      .listen('.TransactionCreated', (payload) => {
        handleIncomingTransaction(payload.transaction)
      })
  }

  const unsubscribe = () => {
    if (pusher) {
      pusher.stopListening('.TransactionCreated')
      pusher = null
    }
    disconnectEcho()
  }

  watch(
    () => auth.token.value,
    (token) => {
      if (token) {
        subscribe()
      } else {
        unsubscribe()
      }
    },
    { immediate: true },
  )

  const openTransactionModal = (user) => {
    transactionModal.open = true
    transactionModal.user = user ?? null
    transactionModal.error = ''
    transactionModal.success = ''
  }

  const closeTransactionModal = () => {
    transactionModal.open = false
    transactionModal.loading = false
    transactionModal.error = ''
    transactionModal.success = ''
    transactionModal.user = null
  }

  const submitTransaction = async (payload) => {
    transactionModal.loading = true
    transactionModal.error = ''
    transactionModal.success = ''

    try {
      const response = await http.post('/transactions', payload)
      const transaction = response.data?.data ?? response.data ?? null
      transactionModal.success = 'Transaction submitted successfully.'

      return transaction
    } catch (error) {
      transactionModal.error = extractAxiosMessage(error, 'Could not submit the transaction.')
      return null
    } finally {
      transactionModal.loading = false
    }
  }

  return {
    users: recipients,
    usersLoading: recipientsLoading,
    usersError: recipientsError,
    transactions,
    transactionsLoading,
    transactionsError,
    transactionsMeta,
    incomingTransfer,
    transactionModal,
    formattedBalance,
    fetchUsers,
    fetchTransactions,
    submitTransaction,
    resetState,
    openTransactionModal,
    closeTransactionModal,
  }
}

const extractAxiosMessage = (error, fallback) => {
  if (error?.response?.data?.message) return error.response.data.message
  if (error?.response?.data?.error) return error.response.data.error
  if (typeof error?.message === 'string' && error.message.length > 0) return error.message
  return fallback
}
