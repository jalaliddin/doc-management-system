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
const showLeaderDropdown = computed(() =>
  orgType.value === 'yuqori' || leaders.value.length > 1
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

// Preview uchun yordamchi funksiyalar
function abbreviateName(fullName) {
  if (!fullName) return ''
  const parts = fullName.trim().split(/\s+/)
  if (parts.length < 2) return fullName
  const last = parts[0]
  const first = parts[1][0].toUpperCase() + '.'
  const patronymic = parts[2] ? parts[2][0].toUpperCase() + '.' : ''
  return first + patronymic + ' ' + last
}

function greetingName(fullName) {
  if (!fullName) return ''
  const parts = fullName.trim().split(/\s+/)
  if (parts.length < 2) return fullName
  return [parts[1], parts[2]].filter(Boolean).join(' ')
}

function formatDate(dateStr) {
  if (!dateStr) return '__.__.____-yil'
  const d = new Date(dateStr)
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = d.getFullYear()
  return `${dd}.${mm}.${yyyy}-yil`
}

// Hujjat preview ma'lumotlari
const selectedOrg       = computed(() => organizations.value.find(o => o.id === orgId.value))
const selectedLeader    = computed(() => leaders.value.find(l => l.id === leaderId.value))
const selectedSignatory = computed(() => signatories.value.find(s => s.id === signatoryId.value))

const preview = computed(() => ({
  docNumber:         department.value?.index_code || '___',
  date:              formatDate(docDate.value),
  recipientOrg:      selectedOrg.value?.name || '',
  recipientPosition: selectedLeader.value?.position || '',
  recipientName:     abbreviateName(selectedLeader.value?.full_name),
  greeting:          orgType.value === 'yuqori' && selectedLeader.value
                       ? 'Hurmatli, ' + greetingName(selectedLeader.value.full_name) + '!'
                       : '',
  text:              textContent.value,
  signatoryPosition: selectedSignatory.value?.position || '',
  signatoryName:     selectedSignatory.value?.full_name || '',
  executorName:      department.value?.head_name || '',
  executorPhone:     department.value?.head_phone || '',
  manualOrg:         manualOrg.value,
  manualPosition:    manualPosition.value,
  manualName:        abbreviateName(manualName.value),
}))

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

    <main class="main-content" style="overflow:auto;">
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

      <v-alert
        v-if="!pageLoading && templates.length === 0"
        type="warning" variant="tonal"
        style="margin:0 24px 16px;border-radius:10px;"
      >
        Shablon yuklanmagan. Admin panelda <strong>Shablonlar</strong> bo'limiga o'ting.
      </v-alert>

      <div v-if="pageLoading" style="display:flex;justify-content:center;padding:60px;">
        <v-progress-circular indeterminate color="primary" size="48" />
      </div>

      <!-- Ikki ustunli layout: chap=form, o'ng=preview -->
      <div v-else style="display:flex;gap:20px;padding:0 24px 24px;align-items:flex-start;">

        <!-- CHAP: Form -->
        <div style="flex:0 0 420px;min-width:0;">
          <v-form ref="formRef" @submit.prevent="generateDocument">

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

              <div v-if="showLeaderDropdown" class="form-section">
                <div class="form-section-title">3. Qabul qiluvchi rahbar</div>
                <v-select v-model="leaderId" :items="leaders"
                  :item-title="i => `${i.position} — ${i.full_name}`"
                  item-value="id" label="Rahbar" variant="outlined" density="comfortable"
                  :rules="[req]" :disabled="!orgId"
                  :no-data-text="orgId ? 'Rahbar topilmadi' : 'Avval tashkilotni tanlang'" />
              </div>
            </div>

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
                  variant="outlined" density="comfortable" :rules="[req]" />
              </div>
            </div>

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

            <div class="form-card" style="margin-bottom:14px;">
              <div class="form-section">
                <div class="form-section-title">{{ templates.length > 1 ? '7' : '6' }}. Hujjat matni</div>
                <v-textarea v-model="textContent" label="Matn" variant="outlined"
                  rows="6" auto-grow :rules="[req]" />
              </div>
            </div>

            <div class="form-card" style="margin-bottom:16px;">
              <div class="form-section">
                <div class="form-section-title" style="display:flex;align-items:center;gap:8px;">
                  {{ templates.length > 1 ? '8' : '7' }}. Qo'shimcha qabul qiluvchi
                  <v-chip size="x-small" color="secondary" variant="tonal">ixtiyoriy</v-chip>
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

        <!-- O'NG: Live preview -->
        <div style="flex:1;min-width:0;position:sticky;top:20px;">
          <div style="background:#E5E7EB;border-radius:10px;padding:12px;">
            <div style="font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;text-align:center;">
              Hujjat ko'rinishi
            </div>

            <!-- A4 varaq -->
            <div class="doc-preview">

              <!-- Bosh qism: raqam va sana -->
              <div style="display:flex;justify-content:space-between;margin-bottom:24px;">
                <div style="font-size:11pt;">
                  <span style="border-bottom:1px solid #333;">{{ preview.docNumber }}</span>
                  <span style="color:#aaa;"> №</span>
                </div>
                <div style="font-size:11pt;">{{ preview.date }}</div>
              </div>

              <!-- Qabul qiluvchi -->
              <div style="text-align:right;margin-bottom:20px;min-height:60px;">
                <div v-if="preview.recipientOrg" style="font-size:10.5pt;font-weight:600;">{{ preview.recipientOrg }}</div>
                <div v-if="preview.recipientPosition" style="font-size:10.5pt;">{{ preview.recipientPosition }}</div>
                <div v-if="preview.recipientName" style="font-size:10.5pt;">{{ preview.recipientName }}</div>
                <div v-if="!preview.recipientOrg && !preview.recipientName"
                  style="font-size:10pt;color:#CBD5E1;font-style:italic;">
                  Tashkilot va rahbar tanlanmagan
                </div>
              </div>

              <!-- Hurmatli -->
              <div v-if="preview.greeting" style="font-size:11pt;margin-bottom:14px;">
                {{ preview.greeting }}
              </div>

              <!-- Matn -->
              <div style="font-size:11pt;line-height:1.7;margin-bottom:24px;min-height:80px;text-align:justify;">
                <span v-if="preview.text" style="white-space:pre-wrap;">{{ preview.text }}</span>
                <span v-else style="color:#CBD5E1;font-style:italic;">Hujjat matni...</span>
              </div>

              <!-- Qo'shimcha blok -->
              <div v-if="preview.manualOrg || preview.manualPosition || preview.manualName"
                style="margin-bottom:20px;padding:10px;background:#F8FAFC;border-left:3px solid #CBD5E1;">
                <div v-if="preview.manualOrg" style="font-size:10.5pt;font-weight:600;">{{ preview.manualOrg }}</div>
                <div v-if="preview.manualPosition" style="font-size:10.5pt;">{{ preview.manualPosition }}</div>
                <div v-if="preview.manualName" style="font-size:10.5pt;">{{ preview.manualName }}</div>
              </div>

              <!-- Imzolovchi -->
              <div style="display:flex;justify-content:space-between;margin-top:32px;">
                <div style="font-size:10.5pt;">
                  <div>{{ preview.signatoryPosition || '_______________' }}</div>
                  <div style="margin-top:4px;">{{ preview.signatoryName || '' }}</div>
                </div>
                <div style="font-size:10pt;color:#6B7280;text-align:right;">
                  <div v-if="preview.executorName">Ijrochi: {{ preview.executorName }}</div>
                  <div v-if="preview.executorPhone">Tel: {{ preview.executorPhone }}</div>
                </div>
              </div>

            </div>
          </div>
        </div>

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

<style scoped>
.doc-preview {
  background: white;
  border-radius: 4px;
  padding: 40px 44px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  font-family: 'Times New Roman', Times, serif;
  min-height: 500px;
}
</style>
