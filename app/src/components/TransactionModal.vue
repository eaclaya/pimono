<script setup>
import { computed, reactive, ref, watch } from 'vue'

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

const emit = defineEmits(['close', 'submit', 'search'])

const form = reactive({
  receiver_id: '',
  amount: '',
})

const searchMode = ref('search')
const searchTerm = ref('')

const filteredUsers = computed(() => {
  if (searchMode.value !== 'search') return props.users
  const term = searchTerm.value.trim().toLowerCase()
  if (!term) return props.users
  return props.users.filter((user) => {
    const name = user?.name?.toLowerCase() ?? ''
    const email = user?.email?.toLowerCase() ?? ''
    return name.includes(term) || email.includes(term)
  })
})

let searchDebounce
const triggerSearch = (value) => {
  if (searchMode.value !== 'search') return
  emit('search', value)
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.receiver_id = props.selectedUser?.id ?? ''
      form.amount = ''
      searchTerm.value = props.selectedUser?.name ?? props.selectedUser?.email ?? ''
    } else {
      searchTerm.value = ''
      triggerSearch('')
    }
  },
)

watch(
  () => props.selectedUser,
  (user) => {
    if (props.open && user?.id) {
      form.receiver_id = user.id
      searchTerm.value = user.name ?? user.email ?? ''
    }
  },
)

watch(
  () => searchTerm.value,
  (value) => {
    if (!props.open || searchMode.value !== 'search') return
    if (searchDebounce) clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => {
      triggerSearch(value.trim())
    }, 300)
  },
)

const handleSuggestionSelect = (user) => {
  form.receiver_id = user?.id ?? ''
  searchTerm.value = user?.name ?? user?.email ?? ''
}

const handleModeChange = (mode) => {
  if (searchMode.value === mode) return
  searchMode.value = mode
  form.receiver_id = ''
  if (mode === 'search') {
    triggerSearch(searchTerm.value.trim())
  } else {
    searchTerm.value = ''
    triggerSearch('')
  }
}

const handleSubmit = () => {
  if (props.submitting) return
  if (!form.receiver_id || !form.amount) return

  emit('submit', {
    receiver_id: form.receiver_id,
    amount: Number(form.amount),
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
            aria-label="Close transaction modal"
            @click="emit('close')"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="handleSubmit">
          <div class="space-y-4">
            <div class="flex items-center gap-3 text-sm font-medium text-slate-700">
              <span>Recipient mode:</span>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="rounded-full px-3 py-1 text-xs font-semibold transition"
                  :class="searchMode === 'search' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                  @click="handleModeChange('search')"
                >
                  Search Recipient
                </button>
                <button
                  type="button"
                  class="rounded-full px-3 py-1 text-xs font-semibold transition"
                  :class="searchMode === 'id' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                  @click="handleModeChange('id')"
                >
                  Enter ID
                </button>
              </div>
            </div>

            <div v-if="searchMode === 'search'" class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-slate-700" for="recipient-search">Search recipients</label>
                <input
                  id="recipient-search"
                  v-model="searchTerm"
                  @input="form.receiver_id = ''"
                  class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                  type="text"
                  placeholder="Search by name or email"
                />
              </div>

              <div
                v-if="filteredUsers.length > 0 && !form.receiver_id"
                class="max-h-40 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2"
              >
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Matches</p>
                <ul class="space-y-1">
                  <li v-for="user in filteredUsers" :key="user.id">
                    <button
                      class="w-full rounded-xl px-3 py-2 text-left text-sm transition hover:bg-white"
                      type="button"
                      @click="handleSuggestionSelect(user)"
                    >
                      <span class="block font-semibold text-slate-900">{{ user.name ?? 'Unknown name' }}</span>
                      <span class="block text-xs text-slate-500">{{ user.email }}</span>
                      <span class="block text-[11px] text-slate-400">ID: {{ user.id }}</span>
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <div v-else>
              <label class="block text-sm font-medium text-slate-700" for="receiver-id">Recipient ID</label>
              <input
                id="receiver-id"
                v-model="form.receiver_id"
                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                type="text"
                placeholder="Enter the user ID"
                required
              />
            </div>
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

          <button
            class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
            type="submit"
            :disabled="submitting || !form.receiver_id"
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
            {{ submitting ? 'Sending...' : 'Send transaction' }}
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
