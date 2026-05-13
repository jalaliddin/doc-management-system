<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  mode: {
    type: String,
    default: 'public', // 'public' | 'admin'
  },
})

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const adminNavItems = [
  { label: 'Bo\'limlar', icon: 'mdi-office-building', to: '/admin/departments' },
  { label: 'Tashkilotlar', icon: 'mdi-domain', to: '/admin/organizations' },
  { label: 'Imzolovchilar', icon: 'mdi-pen', to: '/admin/signatories' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/admin/login')
}
</script>

<template>
  <aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="icon-wrap">
        <v-icon color="white" size="22">mdi-file-document-multiple</v-icon>
      </div>
      <div class="company-name">Urganchtransgaz</div>
      <div class="app-title">Hujjat Tayyorlash Tizimi</div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <template v-if="mode === 'public'">
        <div class="sidebar-section-title">Navigatsiya</div>
        <RouterLink to="/" class="sidebar-item">
          <v-icon size="18">mdi-home-outline</v-icon>
          Bosh sahifa
        </RouterLink>
        <div class="sidebar-section-title" style="margin-top:8px">Admin</div>
        <RouterLink to="/admin/login" class="sidebar-item">
          <v-icon size="18">mdi-shield-account-outline</v-icon>
          Admin panel
        </RouterLink>
      </template>

      <template v-if="mode === 'admin'">
        <div class="sidebar-section-title">Boshqaruv</div>
        <RouterLink
          v-for="item in adminNavItems"
          :key="item.to"
          :to="item.to"
          class="sidebar-item"
        >
          <v-icon size="18">{{ item.icon }}</v-icon>
          {{ item.label }}
        </RouterLink>
      </template>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <template v-if="mode === 'admin'">
        <button
          class="sidebar-item"
          style="width:100%; background:none; border:none; cursor:pointer; text-align:left; border-radius:8px;"
          @click="handleLogout"
        >
          <v-icon size="18" color="rgba(255,100,100,0.8)">mdi-logout</v-icon>
          <span style="color: rgba(255,120,120,0.85)">Chiqish</span>
        </button>
      </template>
      <template v-else>
        <div style="font-size:11px; color:rgba(255,255,255,0.3); line-height:1.5;">
          AT xizmati<br>J.S. Saidov
        </div>
      </template>
    </div>
  </aside>
</template>
