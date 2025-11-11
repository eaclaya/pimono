<script setup>
const props = defineProps({
  users: {
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
  lastFetched: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['refresh', 'logout', 'transfer'])

const handleTransfer = (user) => {
  emit('transfer', user)
}
</script>

<template>
  <section class="flex flex-1 flex-col rounded-3xl bg-white/95 p-4 shadow-lg ring-1 ring-slate-200 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">Recipients</h2>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-70"
          type="button"
          :disabled="loading"
          @click="emit('refresh')"
        >
          <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992M2.985 14.652h4.992M16.5 8.25v-2.25A2.25 2.25 0 0014.25 3.75h-4.5A2.25 2.25 0 007.5 6v12a2.25 2.25 0 002.25 2.25h4.5A2.25 2.25 0 0016.5 18v-2.25" />
          </svg>
          {{ loading ? 'Refreshing...' : 'Refresh' }}
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
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Email</th>
              <th class="px-4 py-3">Balance</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="loading">
              <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="5">
                Loading users…
              </td>
            </tr>
            <tr v-else-if="users.length === 0">
              <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="5">
                No users were returned by the API yet.
              </td>
            </tr>
            <template v-else>
              <tr v-for="user in users" :key="user.id ?? user.email ?? user.name" class="transition hover:bg-slate-50/70">
                <td class="px-4 py-3 font-medium text-slate-900">
                  {{ user.name ?? '—' }}
                </td>
                <td class="px-4 py-3 text-slate-600">
                  {{ user.email ?? '—' }}
                </td>
                <td class="px-4 py-3">
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ user.balance ?? '—' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white transition hover:bg-slate-800"
                    type="button"
                    @click="handleTransfer(user)"
                  >
                    Send
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
