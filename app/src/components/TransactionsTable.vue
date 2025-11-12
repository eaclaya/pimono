<script setup>
import useAuth from '../composables/useAuth'


const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['logout', 'new-transaction'])
const auth = useAuth()
const { user } = auth

const formatDate = (value) => {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString()
}

const formatAmount = (transaction) => {
  if (!transaction) return '—'
  const number = Number(transaction.amount)
  if (Number.isNaN(number)) return transaction.amount
  const sign = transaction.receiver.id == user.id ? '+' : '-'
  return sign + new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(number)
}
</script>

<template>
  <section class="flex flex-1 flex-col rounded-3xl bg-white/95 p-4 shadow-lg ring-1 ring-slate-200 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Transactions</h2>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
          type="button"
          :disabled="loading"
          @click="emit('new-transaction')"
        >
          New transaction
        </button>
        <button
          class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          type="button"
          @click="emit('logout')"
        >
          Sign out
        </button>
      </div>
    </div>

    <p v-if="error" class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div class="mt-4 flex-1 overflow-hidden rounded-2xl border border-slate-100">
      <div class="max-h-[520px] overflow-auto">
        <table class="min-w-full divide-y divide-slate-100 text-left text-sm text-slate-700">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Sender</th>
              <th class="px-4 py-3">Receiver</th>
              <th class="px-4 py-3 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="loading">
              <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="4">
                Loading transactions…
              </td>
            </tr>
            <tr v-else-if="transactions.length === 0">
              <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="4">
                No transactions found yet.
              </td>
            </tr>
            <template v-else>
              <tr v-for="transaction in transactions" :key="transaction.id" class="transition hover:bg-slate-50/70">
                <td class="px-4 py-3 font-medium text-slate-900">
                  {{ formatDate(transaction.created_at) }}
                </td>
                <td class="px-4 py-3 text-slate-600">
                  <div class="flex flex-col">
                    <span>{{ transaction.sender?.name ?? '—' }}</span>
                    <span class="text-xs text-slate-400">{{ transaction.sender?.email ?? '' }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-600">
                  <div class="flex flex-col">
                    <span>{{ transaction.receiver?.name ?? '—' }}</span>
                    <span class="text-xs text-slate-400">{{ transaction.receiver?.email ?? '' }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">

                  {{ formatAmount(transaction) }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
