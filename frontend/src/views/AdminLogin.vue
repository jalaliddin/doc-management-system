<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const showPass = ref(false)

async function handleLogin() {
  error.value = ''
  if (!username.value || !password.value) {
    error.value = 'Login va parolni kiriting'
    return
  }
  loading.value = true
  try {
    await auth.login(username.value, password.value)
    router.push('/admin/departments')
  } catch (err) {
    error.value = err.response?.data?.message || 'Login yoki parol noto\'g\'ri'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div style="min-height:100vh; background:var(--surface-bg); display:flex; align-items:center; justify-content:center; padding:20px;">
    <div style="width:100%; max-width:400px;">
      <!-- Logo -->
      <div style="text-align:center; margin-bottom:32px;">
        <div style="width:60px; height:60px; background:linear-gradient(135deg, #0096C7, #00B4D8); border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
          <v-icon color="white" size="28">mdi-shield-account</v-icon>
        </div>
        <h2 style="font-size:22px; font-weight:700; color:var(--text-primary); margin:0 0 4px;">Admin Panel</h2>
        <p style="font-size:14px; color:var(--text-secondary); margin:0;">Urganchtransgaz Hujjat Tizimi</p>
      </div>

      <!-- Card -->
      <div class="form-card">
        <div class="form-section">
          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            density="compact"
            style="margin-bottom:16px; border-radius:8px;"
          >
            {{ error }}
          </v-alert>

          <v-form @submit.prevent="handleLogin">
            <v-text-field
              v-model="username"
              label="Login"
              prepend-inner-icon="mdi-account-outline"
              variant="outlined"
              density="comfortable"
              autocomplete="username"
              style="margin-bottom:12px;"
            />
            <v-text-field
              v-model="password"
              label="Parol"
              :type="showPass ? 'text' : 'password'"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="showPass ? 'mdi-eye-off' : 'mdi-eye'"
              variant="outlined"
              density="comfortable"
              autocomplete="current-password"
              style="margin-bottom:20px;"
              @click:append-inner="showPass = !showPass"
            />
            <v-btn
              type="submit"
              class="btn-primary"
              size="large"
              block
              :loading="loading"
            >
              Kirish
            </v-btn>
          </v-form>
        </div>
      </div>

      <div style="text-align:center; margin-top:20px;">
        <RouterLink to="/" style="font-size:13px; color:var(--text-secondary); text-decoration:none;">
          <v-icon size="14">mdi-arrow-left</v-icon>
          Bosh sahifaga qaytish
        </RouterLink>
      </div>
    </div>
  </div>
</template>
