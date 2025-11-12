<script setup>
import { computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginForm from '../components/LoginForm.vue'
import useAuth from '../composables/useAuth'

const router = useRouter()
const route = useRoute()
const auth = useAuth()
const { loginLoading, loginError, successMessage } = auth

const loginForm = reactive({
  email: '',
  password: '',
})

const canSubmitLogin = computed(
  () => loginForm.email.trim().length > 0 && loginForm.password.trim().length > 0 && !loginLoading.value,
)

const handleLogin = async () => {
  if (!canSubmitLogin.value) return
  const success = await auth.login({
    email: loginForm.email,
    password: loginForm.password,
  })

  if (success) {
    loginForm.password = ''
    const redirectPath = typeof route.query.redirect === 'string' ? route.query.redirect : undefined
    router.replace(redirectPath ?? { name: 'home' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8 items-center justify-center">
      <LoginForm
        :email="loginForm.email"
        :password="loginForm.password"
        :can-submit="canSubmitLogin"
        :loading="loginLoading"
        :error="loginError"
        :success="successMessage"
        @update:email="(value) => (loginForm.email = value)"
        @update:password="(value) => (loginForm.password = value)"
        @submit="handleLogin"
      />
    </div>
  </div>
</template>
