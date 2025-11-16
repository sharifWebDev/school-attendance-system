<template>
  <div class="login-container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <i class="bi bi-building display-1 text-primary"></i>
              <h2 class="mt-3 fw-bold">School Attendance</h2>
              <p class="text-muted">Sign in to your account</p>
            </div>

            <form @submit.prevent="login">
              <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control form-control-lg"
                  :class="{ 'is-invalid': errors.email }"
                  placeholder="Enter your email"
                  required
                />
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email[0] }}
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <input
                  v-model="form.password"
                  type="password"
                  class="form-control form-control-lg"
                  :class="{ 'is-invalid': errors.password }"
                  placeholder="Enter your password"
                  required
                />
                <div v-if="errors.password" class="invalid-feedback">
                  {{ errors.password[0] }}
                </div>
              </div>

              <button
                type="submit"
                class="btn btn-primary btn-lg w-100"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ loading ? 'Signing in...' : 'Sign In' }}
              </button>
            </form>

            <div v-if="error" class="alert alert-danger mt-3" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
            </div>

            <div class="text-center mt-4">
              <small class="text-muted">
                Demo System - Use your admin credentials
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '../services/api'

const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')
const errors = ref({})

const login = async () => {
  loading.value = true
  error.value = ''
  errors.value = {}

  try {
    const response = await authApi.post('/login', form)

    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      router.push('/')
    }
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors
    } else if (err.response?.status === 401) {
      error.value = 'Invalid credentials. Please try again.'
    } else {
      error.value = 'An error occurred. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

.min-vh-100 {
  min-height: 100vh;
}

.card {
  border-radius: 1rem;
}

.form-control {
  border-radius: 0.5rem;
}

.btn {
  border-radius: 0.5rem;
}
</style>
