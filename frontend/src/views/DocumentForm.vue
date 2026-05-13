<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import api from '@/plugins/axios'

const route = useRoute()
const router = useRouter()

const deptId = computed(() => route.params.id)

const department = ref(null)
const organizations = ref([])
const signatories = ref([])
const leaders = ref([])

const orgType = ref('yuqori')
const orgId = ref(null)
const leaderId = ref(null)
const signatoryId = ref(null)
const docNumber = ref('')
const docDate = ref(new Date().toISOString().split('T')[0])
const textContent = ref('')
const geminiKey = ref('')

const loading = ref(false)
const pageLoading = ref(true)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('error')

const showLeaderField = computed(() => orgType.value === 'yuqori')

const filteredOrgs = computed(() =>
  organizations.value.filter(o => o.type === orgType.value)
)

watch(orgType, () => {
  orgId.value = null
  leaderId.value = null
  leaders.value = []
})

watch(orgId, async (val) => {
  leaderId.value = null
  leaders.value = []
  if (val && orgType.value === 'yuqori') {
    const res = await api.get(`/organizations/${val}/leaders`)
    leaders.value = res.data
    if (leaders.value.length === 1) leaderId.value = leaders.value[0].id
  }
})

onMounted(async () => {
  try {
    const [deptRes, orgRes, sigRes] = await Promise.all([
      api.get(`/departments/${deptId.value}`),
      api.get('/organizations'),
      api.get('/signatories'),
    ])
    department.value = deptRes.data
    organizations.value = orgRes.data
    signatories.value = sigRes.data.filter(s => s.is_active)
    if (signatories.value.length === 1) signatoryId.value = signatories.value[0].id
  } catch {
    showMessage('Ma\'lumotlarni yuklashda xato yuz berdi', 'error')
    router.push('/')
  } finally {
    pageLoading.value = false
  }
})

function showMessage(msg, color = 'error') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

const formRef = ref(null)

async function generateDocument() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const payload = {
      department_id: Number(deptId.value),
      organization_id: orgId.value,
      organization_leader_id: leaderId.value || null,
      signatory_id: signatoryId.value,
      document_number: docNumber.value,
      document_date: docDate.value,
      text_content: textContent.value,
      gemini_api_key: geminiKey.value || undefined,
    }

    const response = await api.post('/documents/generate', payload, {
      responseType: 'blob',
    })

    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename="?([^";\n]+)"?/)
    const filename = filenameMatch ? filenameMatch[1] : 'hujjat.docx'

    const url = URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.click()
    URL.revokeObjectURL(url)

    showMessage('Hujjat muvaffaqiyatli yaratildi!', 'success')
  } catch (err) {
    const msg = err.response?.data?.message || 'Hujjat yaratishda xato yuz berdi'
    showMessage(msg, 'error')
  } finally {
    loading.value = false
  }
}

const orgTypeOptions = [
  { value: 'yuqori', label: 'Yuqori turuvchi tashkilot' },
  { value: 'quyi', label: 'Quyi tashkilotlar' },
  { value: 'boshqa', label: 'Boshqa tashkilotlar' },
]

const requiredRule = v => !!v || 'Bu maydon to\'ldirilishi shart'
const requiredNumber = v => (!!v && v.toString().trim() !== '') || 'Raqam kiritilishi shart'
</script>

<template>
  <div class="app-layout">
    <AppSidebar mode="public" />

    <main class="main-content">
      <div class="page-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            size="small"
            @click="router.push('/')"
          />
          <div>
            <h1 class="page-title">Yangi hujjat yaratish</h1>
            <p v-if="department" style="font-size:13px; color:var(--text-secondary); margin:2px 0 0;">
              {{ department.name }} — {{ department.index_code }}
            </p>
          </div>
        </div>
      </div>

      <div class="page-body" style="max-width:780px;">
        <div v-if="pageLoading" style="display:flex; justify-content:center; padding:60px;">
          <v-progress-circular indeterminate color="primary" size="48" />
        </div>

        <v-form v-else ref="formRef" @submit.prevent="generateDocument">
          <!-- Tashkilot turi -->
          <div class="form-card" style="margin-bottom:16px;">
            <div class="form-section">
              <div class="form-section-title">1. Tashkilot turi</div>
              <v-radio-group v-model="orgType" inline hide-details>
                <v-radio
                  v-for="opt in orgTypeOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                  color="primary"
                />
              </v-radio-group>
            </div>

            <!-- Tashkilot tanlash -->
            <div class="form-section">
              <div class="form-section-title">2. Tashkilotni tanlang</div>
              <v-select
                v-model="orgId"
                :items="filteredOrgs"
                item-title="name"
                item-value="id"
                label="Tashkilot"
                variant="outlined"
                density="comfortable"
                :rules="[requiredRule]"
                :no-data-text="`${orgType === 'yuqori' ? 'Yuqori turuvchi' : orgType === 'quyi' ? 'Quyi' : 'Boshqa'} tashkilotlar topilmadi`"
              />
            </div>

            <!-- Rahbar (faqat yuqori turuvchida) -->
            <div v-if="showLeaderField" class="form-section">
              <div class="form-section-title">3. Qabul qiluvchi rahbar</div>
              <v-select
                v-model="leaderId"
                :items="leaders"
                :item-title="item => `${item.position} — ${item.full_name}`"
                item-value="id"
                label="Rahbar"
                variant="outlined"
                density="comfortable"
                :rules="showLeaderField ? [requiredRule] : []"
                :disabled="!orgId || leaders.length === 0"
                :no-data-text="orgId ? 'Rahbarlar topilmadi' : 'Avval tashkilotni tanlang'"
              />
            </div>
          </div>

          <!-- Imzolovchi va raqam -->
          <div class="form-card" style="margin-bottom:16px;">
            <div class="form-section">
              <div class="form-section-title">{{ showLeaderField ? '4' : '3' }}. Imzolovchi</div>
              <v-select
                v-model="signatoryId"
                :items="signatories"
                :item-title="item => `${item.position} — ${item.full_name}`"
                item-value="id"
                label="Imzolovchi"
                variant="outlined"
                density="comfortable"
                :rules="[requiredRule]"
              />
            </div>

            <div class="form-section">
              <div class="form-section-title">{{ showLeaderField ? '5' : '4' }}. Hujjat raqami va sanasi</div>
              <v-row>
                <v-col cols="12" sm="5">
                  <v-text-field
                    v-model="docNumber"
                    :label="`Raqam (Prefiks: ${department?.index_code || '...'})`"
                    variant="outlined"
                    density="comfortable"
                    :rules="[requiredNumber]"
                    :hint="`To'liq raqam: ${department?.index_code || ''}${docNumber || '...'}`"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="12" sm="7">
                  <v-text-field
                    v-model="docDate"
                    label="Sana"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    :rules="[requiredRule]"
                  />
                </v-col>
              </v-row>
            </div>
          </div>

          <!-- Matn -->
          <div class="form-card" style="margin-bottom:16px;">
            <div class="form-section">
              <div class="form-section-title">{{ showLeaderField ? '6' : '5' }}. Hujjat matni</div>
              <v-textarea
                v-model="textContent"
                label="Hujjat matni"
                variant="outlined"
                rows="7"
                auto-grow
                :rules="[requiredRule]"
                hint="Matn Gemini AI tomonidan grammatik jihatdan tuzatiladi (agar API kalit mavjud bo'lsa)"
                persistent-hint
              />
            </div>

            <div class="form-section">
              <div class="form-section-title" style="display:flex; align-items:center; gap:8px;">
                <v-icon size="14" color="var(--accent-primary)">mdi-robot-outline</v-icon>
                Gemini API kalit (ixtiyoriy)
              </div>
              <v-text-field
                v-model="geminiKey"
                label="Gemini API kalit"
                variant="outlined"
                density="comfortable"
                placeholder="AIza..."
                hint="Bo'sh qoldirsa, .env dagi kalit ishlatiladi"
                persistent-hint
                :type="'password'"
              />
            </div>
          </div>

          <!-- Submit -->
          <v-btn
            type="submit"
            class="btn-primary"
            size="large"
            block
            :loading="loading"
            prepend-icon="mdi-download"
          >
            Word hujjatini yuklab olish
          </v-btn>
        </v-form>
      </div>
    </main>

    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      timeout="4000"
      location="bottom right"
    >
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">Yopish</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>
