<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import api from '@/plugins/axios'

const router = useRouter()
const departments = ref([])
const loading = ref(true)
const searchQuery = ref('')

const filtered = computed(() => {
  if (!searchQuery.value) return departments.value
  const q = searchQuery.value.toLowerCase()
  return departments.value.filter(d =>
    d.name.toLowerCase().includes(q) ||
    d.index_code.toLowerCase().includes(q)
  )
})

onMounted(async () => {
  try {
    const res = await api.get('/departments')
    departments.value = res.data
  } finally {
    loading.value = false
  }
})

function openForm(dept) {
  router.push({ name: 'DocumentForm', params: { id: dept.id } })
}
</script>

<script>
import { computed } from 'vue'
</script>

<template>
  <div class="app-layout">
    <AppSidebar mode="public" />

    <main class="main-content">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Bo'limlar va Xizmatlar</h1>
          <p style="font-size:13px; color:var(--text-secondary); margin:2px 0 0;">
            Hujjat yaratish uchun bo'limni tanlang
          </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <v-text-field
            v-model="searchQuery"
            placeholder="Qidirish..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            style="min-width:220px;"
            bg-color="white"
          />
        </div>
      </div>

      <!-- Body -->
      <div class="page-body">
        <!-- Stats bar -->
        <div style="display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap;">
          <div style="background:#fff; border:1px solid var(--border-color); border-radius:10px; padding:12px 20px; display:flex; align-items:center; gap:10px;">
            <div style="width:38px; height:38px; background:rgba(0,150,199,0.1); border-radius:9px; display:flex; align-items:center; justify-content:center;">
              <v-icon color="#0096C7" size="20">mdi-office-building</v-icon>
            </div>
            <div>
              <div style="font-size:20px; font-weight:700; color:var(--text-primary);">{{ departments.length }}</div>
              <div style="font-size:12px; color:var(--text-secondary);">Bo'limlar</div>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="dept-grid">
          <v-skeleton-loader
            v-for="n in 6"
            :key="n"
            type="card"
            style="border-radius:14px;"
          />
        </div>

        <!-- Empty -->
        <div v-else-if="!filtered.length" style="text-align:center; padding:60px 20px;">
          <v-icon size="60" color="#CBD5E1">mdi-folder-open-outline</v-icon>
          <p style="color:var(--text-secondary); margin-top:12px;">
            {{ searchQuery ? 'Hech narsa topilmadi' : 'Bo\'limlar mavjud emas' }}
          </p>
        </div>

        <!-- Cards -->
        <div v-else class="dept-grid">
          <div
            v-for="dept in filtered"
            :key="dept.id"
            class="dept-card"
            @click="openForm(dept)"
          >
            <div class="dept-index-badge">{{ dept.index_code }}</div>
            <h3 class="dept-name">{{ dept.name }}</h3>
            <div class="dept-meta">
              <v-icon size="14" color="#94A3B8">mdi-account-outline</v-icon>
              {{ dept.head_name }}
            </div>
            <div v-if="dept.head_phone" class="dept-meta">
              <v-icon size="14" color="#94A3B8">mdi-phone-outline</v-icon>
              Ichki: {{ dept.head_phone }}
            </div>
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border-color);">
              <v-btn
                class="btn-primary"
                size="small"
                block
                @click.stop="openForm(dept)"
              >
                <v-icon size="16" class="mr-1">mdi-file-plus-outline</v-icon>
                Hujjat yaratish
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
