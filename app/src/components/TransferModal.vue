<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  users: {
    type: Array,
    default: () => [],
  },
  selectedUser: {
    type: Object,
    default: null,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  success: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  recipient_id: '',
  amount: '',
  note: '',
})

const selectedName = computed(() => props.selectedUser?.name ?? '')

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.recipient_id = props.selectedUser?.id ?? ''
      form.amount = ''
      form.note = ''
    }
  },
)

const handleSubmit = () => {
  if (props.submitting) return
  if (!form.recipient_id || !form.amount) return

  emit('submit', {
    recipient_id: form.recipient_id,
    amount: Number(form.amount),
    note: form.note,
  })
}
</script>

<template>
  <transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-6 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
    >
      <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-xl font-semibold text-slate-900">Send money</h3>
          </div>
          <button
            class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
            type="button"
            aria-label="Close transfer modal"
            @click="emit('close')"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="handleSubmit">
          <div>
            <label class="block text-sm font-medium text-slate-700" for="recipient">Recipient</label>
            <input
              type="text"
              class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              disabled
              :value="selectedName"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700" for="amount">Amount</label>
            <input
              id="amount"
              v-model="form.amount"
              class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              type="number"
              inputmode="decimal"
              step="0.01"
              placeholder="100.00"
              min="0"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700" for="note">Note (optional)</label>
            <textarea
              id="note"
              v-model="form.note"
              class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              rows="3"
              placeholder="Give context for this transfer…"
            />
          </div>

          <button
            class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
            type="submit"
            :disabled="submitting"
          >
            <svg
              v-if="submitting"
              class="mr-2 h-4 w-4 animate-spin"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            {{ submitting ? 'Sending...' : 'Send transfer' }}
          </button>
        </form>

        <p v-if="error" class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {{ error }}
        </p>
        <p v-if="success" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          {{ success }}
        </p>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
