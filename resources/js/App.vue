<template>
  <div id="app">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container">
        <router-link class="navbar-brand fw-bold" to="/">
          🏫 School Attendance System
        </router-link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <router-link class="nav-link" to="/">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/students">
                <i class="bi bi-people me-1"></i>Students
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/attendance">
                <i class="bi bi-clipboard-check me-1"></i>Attendance
              </router-link>
            </li>
          </ul>

          <div class="navbar-nav">
            <span class="navbar-text me-3">
              <i class="bi bi-person-circle me-1"></i>{{ user?.name }}
            </span>
            <button class="btn btn-outline-light btn-sm" @click="logout">
              <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="container-fluid py-4">
      <div class="container">
        <router-view />
      </div>
    </main>

    <!-- Loading Spinner -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from './services/api'

const router = useRouter()
const user = ref(null)
const loading = ref(false)

const logout = () => {
  localStorage.removeItem('auth_token')
  router.push('/login')
}

const fetchUser = async () => {
  try {
    const response = await authApi.get('/user')
    user.value = response.data
  } catch (error) {
    console.error('Failed to fetch user:', error)
    logout()
  }
}

onMounted(() => {
  fetchUser()
})
</script>

<style>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.navbar-brand {
  font-size: 1.5rem;
}

.router-link-active {
  font-weight: 600;
}
</style>
