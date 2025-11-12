<script setup>
import { computed, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import TransactionModal from '@/components/TransactionModal.vue'
import TransactionsTable from '@/components/TransactionsTable.vue'
import Toast from '@/components/Toast.vue'
import useAuth from '@/composables/useAuth'
import useTransaction from '@/composables/useTransaction'

const router = useRouter()
const auth = useAuth()

const {
  users,
  usersError,
  transactions,
  transactionsLoading,
  transactionsError,
  transactionModal,
  fetchUsers,
  fetchTransactions,
  openTransactionModal,
  closeTransactionModal,
  submitTransaction,
  resetState,
  formattedBalance,
  incomingTransfer,
} = useTransaction()

const toast = reactive({
  open: false,
  title: '',
  message: '',
})

let toastTimeout = null

const profileName = computed(() => auth.user.value?.name ?? 'Account holder')
const profileEmail = computed(() => auth.user.value?.email ?? '')

const formatCurrency = (value) => {
  const amount = Number.parseFloat(value ?? 0) || 0
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}


const handleLogout = () => {
  resetState()
  auth.logout()
  router.replace({ name: 'login' })
}

const handleNewTransaction = () => {
  openTransactionModal()
}

const handleRecipientSearch = (term) => {
  fetchUsers({ search: term })
}

const showToast = (title, message) => {
  toast.title = title
  toast.message = message
  toast.open = true
  if (toastTimeout) clearTimeout(toastTimeout)
  toastTimeout = setTimeout(() => {
    toast.open = false
  }, 4000)
}

const handleTransactionSubmit = async (payload) => {
  const transaction = await submitTransaction(payload)
  if (transaction) {
    closeTransactionModal()
    showToast('Transaction complete', 'Transaction submitted successfully.')
  }
}

watch(
  incomingTransfer,
  (transaction) => {
    if (!transaction) return
    const senderName = transaction.sender?.name ?? 'A contact'
    const amount = formatCurrency(transaction.amount)
    showToast('Transfer received', `${senderName} sent you ${amount}.`)
    incomingTransfer.value = null
  },
  { flush: 'post' },
)

onMounted(() => {
  if (auth.isAuthenticated.value) {
    fetchTransactions()
  }
})

</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
      <section class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-6 text-white shadow-xl">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm uppercase tracking-wide text-white/70">Welcome back</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ profileName }}</h1>
            <p class="text-sm text-white/70">{{ profileEmail }}</p>
          </div>
          <div class="text-left sm:text-right">
            <p class="text-sm uppercase tracking-wide text-white/70">Current balance</p>
            <p class="mt-2 text-4xl font-semibold">{{ formattedBalance }}</p>
          </div>
        </div>
      </section>

      <TransactionsTable
        :transactions="transactions"
        :loading="transactionsLoading"
        :error="transactionsError"
        @logout="handleLogout"
        @new-transaction="handleNewTransaction"
      />
    </div>

    <TransactionModal
      :open="transactionModal.open"
      :users="users"
      :selected-user="transactionModal.user"
      :error="transactionModal.error || usersError"
      :success="transactionModal.success"
      :submitting="transactionModal.loading"
      @close="closeTransactionModal"
      @submit="handleTransactionSubmit"
      @search="handleRecipientSearch"
    />

    <Toast v-model="toast.open" :title="toast.title" :message="toast.message" />
  </div>
</template>
