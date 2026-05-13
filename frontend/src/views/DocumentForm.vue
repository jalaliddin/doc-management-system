<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import api from '@/plugins/axios'

const route = useRoute()
const router = useRouter()
const deptId = computed(() => route.params.id)

const department    = ref(null)
const organizations = ref([])
const signatories   = ref([])
const leaders       = ref([])
const templates     = ref([])

const orgType     = ref('yuqori')
const orgId       = ref(null)
const leaderId    = ref(null)
const signatoryId = ref(null)
const templateId  = ref(null)
const docDate     = ref(new Date().toISOString().split('T')[0])
const textContent = ref('')

const manualOrg      = ref('')
const manualPosition = ref('')
const manualName     = ref('')

const loading       = ref(false)
const pageLoading   = ref(true)
const snackbar      = ref(false)
const snackbarText  = ref('')
const snackbarColor = ref('error')
const formRef       = ref(null)

const filteredOrgs       = computed(() => organizations.value.filter(o => o.type === orgType.value))
// Quyi/boshqa: faqat bir nechta rahbar bo'lsa dropdown ko'rsatiladi
const showLeaderDropdown = computed(() =>
  orgType.value === 'yuqori' || (orgType.value !== 'yuqori' && leaders.value.length > 1)
)

watch(orgType, () => {
  orgId.value = null
  leaderId.value = null
  leaders.value = []
})

watch(orgId, async (val) => {
  leaderId.value = null
  leaders.value = []
  if (val) {
    const res = await api.get(`/organizations/${val}/leaders`)
    leaders.value = res.data
    if (leaders.value.length === 1) leaderId.value = leaders.value[0].id
  }
})

onMounted(async () => {
  try {
    const [deptRes, orgRes, sigRes, tplRes] = await Promise.all([
      api.get(`/departments/${deptId.value}`),
      api.get('/organizations'),
      api.get('/signatories'),
      api.get('/templates'),
    ])
    department.value    = deptRes.data
    organizations.value = orgRes.data
    signatories.value   = sigRes.data.filter(s => s.is_active)
    templates.value     = tplRes.data
    if (signatories.value.length === 1) signatoryId.value = signatories.value[0].id
    const active = tplRes.data.find(t => t.is_active)
    if (active) templateId.value = active.id
  } catch {
    showMsg('Ma\'lumotlarni yuklashda xato', 'error')
    router.push('/')
  } finally {
    pageLoading.value = false
  }
})

function showMsg(msg, color = 'error') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

async function generateDocument() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (!templateId.value && templates.value.length === 0) {
    showMsg('Shablon topilmadi. Admin panelda shablon yuklang.', 'error')
    return
  }

  loading.value = true
  try {
    const response = await api.post('/documents/generate', {
      department_id:          Number(deptId.value),
      organization_id:        orgId.value,
      organization_leader_id: leaderId.value || null,
      signatory_id:           signatoryId.value,
      template_id:            templateId.value || null,
      document_date:          docDate.value,
      text_content:           textContent.value,
      manual_org:             manualOrg.value || null,
      manual_position:        manualPosition.value || null,
      manual_name:            manualName.value || null,
    }, { responseType: 'blob' })

    const cd = response.headers['content-disposition'] || ''
    const match = cd.match(/filename="?([^";\n]+)"?/)
    const filename = match ? match[1] : 'hujjat.docx'

    const url = URL.createObjectURL(new Blob([response.data]))
    const a = document.createElement('a')
    a.href = url; a.download = filename; a.click()
    URL.revokeObjectURL(url)

    showMsg('Hujjat muvaffaqiyatli yaratildi!', 'success')
  } catch (err) {
    const msg = err.response?.data?.message || 'Hujjat yaratishda xato'
    showMsg(msg, 'error')
  } finally {
    loading.value = false
  }
}

const orgTypeOptions = [
  { value: 'yuqori', label: 'Yuqori turuvchi' },
  { value: 'quyi',   label: 'Quyi tashkilotlar' },
  { value: 'boshqa', label: 'Boshqa tashkilotlar' },
]
const req = v => !!v || 'Majburiy maydon'
</script>

<template>
  <div class="app-layout">
    <AppSidebar mode="public" />

    <main class="main-content">
      <div class="page-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
          <div>
            <h1 class="page-title">Yangi hujjat</h1>
            <p v-if="department" style="font-size:13px;color:var(--text-secondary);margin:2px 0 0;">
              {{ department.name }} — {{ department.index_code }}
            </p>
          </div>
        </div>
      </div>

      <div class="page-body" style="max-width:760px;">

        <v-alert
          v-if="!pageLoading && templates.length === 0"
          type="warning" variant="tonal" style="margin-bottom:16px;border-radius:10px;"
        >
          Shablon yuklanmagan. Admin panelda <strong>Shablonlar</strong> bo'limiga o'ting.
        </v-alert>

        <div v-if="pageLoading" style="display:flex;justify-content:center;padding:60px;">
          <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <v-form v-else ref="formRef" @submit.prevent="generateDocument">

          <!-- 1. Tashkilot turi va rahbar -->
          <div class="form-card" style="margin-bottom:14px;">
            <div class="form-section">
              <div class="form-section-title">1. Tashkilot turi</div>
              <v-radio-group v-model="orgType" inline hide-details>
                <v-radio v-for="o in orgTypeOptions" :key="o.value"
                  :label="o.label" :value="o.value" color="primary" />
              </v-radio-group>
            </div>

            <div class="form-section">
              <div class="form-section-title">2. Tashkilot</div>
              <v-select v-model="orgId" :items="filteredOrgs"
                item-title="name" item-value="id"
                label="Tashkilotni tanlang" variant="outlined" density="comfortable"
                :rules="[req]" />
            </div>

            <!-- Yuqori: majburiy dropdown; Quyi/boshqa: faqat bir nechta rahbar bo'lsa -->
            <div v-if="showLeaderDropdown" class="form-section">
              <div class="form-section-title">3. Qabul qiluvchi rahbar</div>
              <v-select v-model="leaderId" :items="leaders"
                :item-title="i => `${i.position} — ${i.full_name}`"
                item-value="id" label="Rahbar" variant="outlined" density="comfortable"
                :rules="[req]" :disabled="!orgId"
                :no-data-text="orgId ? 'Rahbar topilmadi' : 'Avval tashkilotni tanlang'" />
            </div>
          </div>

          <!-- Imzolovchi va sana -->
          <div class="form-card" style="margin-bottom:14px;">
            <div class="form-section">
              <div class="form-section-title">4. Imzolovchi</div>
              <v-select v-model="signatoryId" :items="signatories"
                :item-title="i => `${i.position} — ${i.full_name}`"
                item-value="id" label="Imzolovchi" variant="outlined" density="comfortable"
                :rules="[req]" />
            </div>

            <div class="form-section">
              <div class="form-section-title">5. Sana</div>
              <v-text-field v-model="docDate" label="Sana" type="date"
                variant="outlined" density="comfortable" :rules="[req]"
                style="max-width:280px;" />
            </div>
          </div>

          <!-- Shablon tanlash (birdan ko'p bo'lsa) -->
          <div v-if="templates.length > 1" class="form-card" style="margin-bottom:14px;">
            <div class="form-section">
              <div class="form-section-title">6. Shablon</div>
              <v-select v-model="templateId" :items="templates"
                item-title="name" item-value="id"
                label="Shablon tanlang" variant="outlined" density="comfortable">
                <template #item="{ item, props }">
                  <v-list-item v-bind="props">
                    <template #append>
                      <v-chip v-if="item.raw.is_active" size="x-small" color="success">Faol</v-chip>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </div>
          </div>

          <!-- Hujjat matni -->
          <div class="form-card" style="margin-bottom:14px;">
            <div class="form-section">
              <div class="form-section-title">{{ templates.length > 1 ? '7' : '6' }}. Hujjat matni</div>
              <v-textarea v-model="textContent" label="Matn" variant="outlined"
                rows="7" auto-grow :rules="[req]"
                hint="Matn shablondagi ${TEXT} joyiga yoziladi" persistent-hint />
            </div>
          </div>

          <!-- Qo'shimcha qabul qiluvchi (ixtiyoriy) -->
          <div class="form-card" style="margin-bottom:16px;">
            <div class="form-section">
              <div class="form-section-title" style="display:flex;align-items:center;gap:8px;">
                {{ templates.length > 1 ? '8' : '7' }}. Qo'shimcha qabul qiluvchi
                <v-chip size="x-small" color="secondary" variant="tonal">ixtiyoriy</v-chip>
              </div>
              <div style="font-size:12px;color:var(--text-secondary);margin-bottom:12px;">
                Shablondagi
                <code style="background:#EFF6FF;color:#1D4ED8;padding:1px 5px;border-radius:4px;">${MANUAL_ORG}</code>,
                <code style="background:#EFF6FF;color:#1D4ED8;padding:1px 5px;border-radius:4px;">${MANUAL_POSITION}</code>,
                <code style="background:#EFF6FF;color:#1D4ED8;padding:1px 5px;border-radius:4px;">${MANUAL_NAME}</code>
                joylari uchun
              </div>
              <v-text-field v-model="manualOrg"
                label="Boshqarma nomi" variant="outlined" density="comfortable" class="mb-3" />
              <v-text-field v-model="manualPosition"
                label="Rahbar lavozimi" variant="outlined" density="comfortable" class="mb-3" />
              <v-text-field v-model="manualName"
                label="Rahbar F.I.Sh. (Familiya Ism Otasining-ismi)"
                variant="outlined" density="comfortable"
                hint="Avtomatik qisqartiriladi: A.A. Familiya" persistent-hint />
            </div>
          </div>

          <v-btn type="submit" class="btn-primary" size="large" block
            :loading="loading" prepend-icon="mdi-download">
            Word yuklab olish
          </v-btn>
        </v-form>
      </div>
    </main>

    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">OK</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>
