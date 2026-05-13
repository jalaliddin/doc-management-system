<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/plugins/axios'

const templates   = ref([])
const loading     = ref(true)
const uploading   = ref(false)
const deleting    = ref(false)
const dialog      = ref(false)
const deleteDialog = ref(false)
const selectedTpl = ref(null)

const newName = ref('')
const newFile = ref(null)
const formRef = ref(null)

const snackbar      = ref(false)
const snackbarText  = ref('')
const snackbarColor = ref('success')

const placeholders = [
  { key: '${DOC_NUMBER}',         desc: 'Hujjat indeksi (masalan: АТ/)' },
  { key: '${DATE}',               desc: 'Sana (masalan: 13.05.2026-yil)' },
  { key: '${RECIPIENT_ORG}',      desc: 'Tashkilot nomi (to\'liq)' },
  { key: '${RECIPIENT_POSITION}', desc: 'Qabul qiluvchi lavozimi' },
  { key: '${RECIPIENT_NAME}',     desc: 'Qabul qiluvchi qisqartma (A.A. Familiya)' },
  { key: '${GREETING}',           desc: 'Hurmatli, Ism Otasining-ismi!' },
  { key: '${SIGNATORY_POSITION}', desc: 'Imzolovchi lavozimi' },
  { key: '${SIGNATORY_NAME}',     desc: 'Imzolovchi FISH' },
  { key: '${EXECUTOR_NAME}',      desc: 'Ijrochi (bo\'lim rahbari FISH)' },
  { key: '${EXECUTOR_PHONE}',     desc: 'Ichki telefon' },
  { key: '${TEXT}',               desc: 'Hujjat asosiy matni' },
  { key: '${MANUAL_ORG}',         desc: 'Qo\'lda: boshqarma nomi' },
  { key: '${MANUAL_POSITION}',    desc: 'Qo\'lda: rahbar lavozimi' },
  { key: '${MANUAL_NAME}',        desc: 'Qo\'lda: rahbar qisqartma (A.A. Familiya)' },
]

onMounted(fetchTemplates)

async function fetchTemplates() {
  loading.value = true
  try {
    const res = await api.get('/templates')
    templates.value = res.data
  } finally {
    loading.value = false
  }
}

function showMsg(msg, color = 'success') {
  snackbarText.value = msg; snackbarColor.value = color; snackbar.value = true
}

async function upload() {
  const { valid } = await formRef.value.validate()
  if (!valid || !newFile.value) return

  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('name', newName.value)
    fd.append('file', newFile.value)

    const res = await api.post('/admin/templates', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    templates.value.unshift(res.data)
    dialog.value = false
    newName.value = ''
    newFile.value = null
    showMsg('Shablon yuklandi')
  } catch (err) {
    showMsg(err.response?.data?.message || 'Yuklashda xato', 'error')
  } finally {
    uploading.value = false
  }
}

async function activate(tpl) {
  try {
    await api.post(`/admin/templates/${tpl.id}/activate`)
    templates.value.forEach(t => { t.is_active = t.id === tpl.id })
    showMsg(`"${tpl.name}" faollashtirildi`)
  } catch {
    showMsg('Xato yuz berdi', 'error')
  }
}

function openDelete(tpl) {
  selectedTpl.value = tpl
  deleteDialog.value = true
}

async function confirmDelete() {
  deleting.value = true
  try {
    await api.delete(`/admin/templates/${selectedTpl.value.id}`)
    templates.value = templates.value.filter(t => t.id !== selectedTpl.value.id)
    deleteDialog.value = false
    showMsg('Shablon o\'chirildi')
  } catch {
    showMsg('O\'chirishda xato', 'error')
  } finally {
    deleting.value = false
  }
}

function copyKey(key) {
  navigator.clipboard.writeText(key)
  showMsg(`${key} nusxalandi`)
}
</script>

<template>
  <AdminLayout title="Word Shablonlar">
    <template #header-actions>
      <v-btn class="btn-primary" prepend-icon="mdi-upload" @click="dialog = true">
        Shablon yuklash
      </v-btn>
    </template>

    <v-row>
      <!-- Shablonlar ro'yxati -->
      <v-col cols="12" md="7">
        <div v-if="loading" style="display:flex;justify-content:center;padding:40px;">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <div v-else-if="!templates.length"
          style="background:#fff;border:1px solid var(--border-color);border-radius:12px;padding:48px;text-align:center;">
          <v-icon size="52" color="#CBD5E1">mdi-file-word-outline</v-icon>
          <p style="color:var(--text-secondary);margin-top:10px;">
            Hali shablon yuklanmagan.<br>
            <strong>Shablon yuklash</strong> tugmasini bosing.
          </p>
        </div>

        <div v-else style="display:flex;flex-direction:column;gap:10px;">
          <div v-for="tpl in templates" :key="tpl.id"
            style="background:#fff;border:1px solid var(--border-color);border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:12px;">
            <v-icon size="32" color="#0096C7">mdi-file-word</v-icon>
            <div style="flex:1;min-width:0;">
              <div style="font-size:15px;font-weight:500;color:var(--text-primary);">{{ tpl.name }}</div>
              <div style="font-size:12px;color:var(--text-secondary);">
                {{ new Date(tpl.created_at).toLocaleDateString('uz-UZ') }}
              </div>
            </div>
            <v-chip v-if="tpl.is_active" color="success" size="small" variant="tonal">Faol</v-chip>
            <v-btn v-else size="small" variant="tonal" color="primary" @click="activate(tpl)">
              Faollashtirish
            </v-btn>
            <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="openDelete(tpl)" />
          </div>
        </div>
      </v-col>

      <!-- Placeholder lar spravkasi -->
      <v-col cols="12" md="5">
        <div style="background:#fff;border:1px solid var(--border-color);border-radius:12px;overflow:hidden;">
          <div style="padding:14px 18px;border-bottom:1px solid var(--border-color);background:#F8FAFC;">
            <div style="font-size:13px;font-weight:600;color:var(--accent-primary);text-transform:uppercase;letter-spacing:0.8px;">
              Shablon uchun placeholder lar
            </div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
              Word faylingizga quyidagilarni kiriting
            </div>
          </div>
          <div style="padding:8px 0;">
            <div v-for="p in placeholders" :key="p.key"
              style="display:flex;align-items:center;padding:7px 14px;gap:10px;cursor:pointer;transition:background 0.15s;"
              @mouseenter="$event.currentTarget.style.background='#F8FAFC'"
              @mouseleave="$event.currentTarget.style.background=''"
              @click="copyKey(p.key)">
              <code style="font-size:12px;background:#EFF6FF;color:#1D4ED8;padding:2px 7px;border-radius:5px;white-space:nowrap;font-family:monospace;">
                {{ p.key }}
              </code>
              <span style="font-size:13px;color:var(--text-secondary);flex:1;">{{ p.desc }}</span>
              <v-icon size="14" color="#94A3B8">mdi-content-copy</v-icon>
            </div>
          </div>
          <div style="padding:10px 14px;border-top:1px solid var(--border-color);background:#FFFBEB;">
            <div style="font-size:12px;color:#92400E;">
              <v-icon size="14" color="#F59E0B">mdi-information</v-icon>
              Placeholder ni bosing — clipboard ga nusxalanadi
            </div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Upload dialog -->
    <v-dialog v-model="dialog" max-width="460" persistent>
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 4px;font-size:17px;font-weight:600;">
          Shablon yuklash
        </v-card-title>
        <v-card-subtitle style="padding:0 24px 12px;font-size:13px;">
          Faqat <strong>.docx</strong> format qabul qilinadi
        </v-card-subtitle>
        <v-card-text style="padding:8px 24px;">
          <v-form ref="formRef">
            <v-text-field v-model="newName" label="Shablon nomi"
              variant="outlined" density="comfortable" class="mb-3"
              :rules="[v => !!v || 'Nom kiritilishi shart']" />
            <v-file-input v-model="newFile" label=".docx fayl tanlang"
              accept=".docx" variant="outlined" density="comfortable"
              prepend-icon="" prepend-inner-icon="mdi-file-word"
              :rules="[v => !!v || 'Fayl tanlang']" />
          </v-form>
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px;gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Bekor</v-btn>
          <v-btn class="btn-primary" :loading="uploading" @click="upload">Yuklash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 8px;">O'chirish</v-card-title>
        <v-card-text>
          <b>{{ selectedTpl?.name }}</b> shablonini o'chirishni tasdiqlaysizmi?
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px;gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Bekor</v-btn>
          <v-btn color="error" :loading="deleting" @click="confirmDelete">O'chirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
      {{ snackbarText }}
    </v-snackbar>
  </AdminLayout>
</template>
