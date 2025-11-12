<script setup>
const props = defineProps({
  email: {
    type: String,
    default: '',
  },
  password: {
    type: String,
    default: '',
  },
  canSubmit: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  success: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['update:email', 'update:password', 'submit'])

const handleSubmit = () => {
  if (!props.canSubmit || props.loading) return
  emit('submit')
}
</script>

<template>
  <section class="rounded-3xl bg-white/95 p-6 shadow-lg ring-1 ring-slate-200 sm:p-8">
    <h2 class="text-xl font-semibold text-slate-900">Sign in</h2>
    <p class="mt-1 text-sm text-slate-600">Test credentials <strong>admin@example.com</strong> and <strong>password</strong> to sign in.</p>

    <form class="mt-6 space-y-5" @submit.prevent="handleSubmit">
      <div>
        <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
        <input
          id="email"
          class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none ring-0 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
          type="email"
          :value="email"
          placeholder="you@example.com"
          autocomplete="email"
          required
          @input="emit('update:email', $event.target.value)"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
        <input
          id="password"
          class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none ring-0 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
          type="password"
          :value="password"
          placeholder="••••••••"
          autocomplete="current-password"
          required
          @input="emit('update:password', $event.target.value)"
        />
      </div>

      <button
        class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
        type="submit"
        :disabled="!canSubmit"
      >
        <svg
          v-if="loading"
          class="mr-2 h-4 w-4 animate-spin"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </button>
    </form>

    <p v-if="error" class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>
    <p v-if="success" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </p>
  </section>
</template>
