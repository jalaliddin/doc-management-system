<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/plugins/axios'

const departments = ref([])
const loading = ref(true)
const dialog = ref(false)
const deleteDialog = ref(false)
const saving = ref(false)
const deleting = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const editingItem = ref(null)
const form = ref({
  name: '',
  index_code: '',
  head_name: '',
  head_phone: '',
})

const headers = [
  { title: 'Bo\'lim nomi', key: 'name', sortable: true },
  { title: 'Indeks', key: 'index_code', sortable: false, width: '100px' },
  { title: 'Rahbar FISH', key: 'head_name', sortable: true },
  { title: 'Ichki tel', key: 'head_phone', sortable: false, width: '100px' },
  { title: 'Amallar', key: 'actions', sortable: false, align: 'end', width: '120px' },
]

const formRef = ref(null)
const requiredRule = v => !!v?.trim() || 'Majburiy maydon'

onMounted(fetchDepartments)

async function fetchDepartments() {
  loading.value = true
  try {
    const res = await api.get('/departments')
    departments.value = res.data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingItem.value = null
  form.value = { name: '', index_code: '', head_name: '', head_phone: '' }
  dialog.value = true
}

function openEdit(item) {
  editingItem.value = item
  form.value = { ...item }
  dialog.value = true
}

function openDelete(item) {
  editingItem.value = item
  deleteDialog.value = true
}

function showMsg(msg, color = 'success') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  saving.value = true
  try {
    if (editingItem.value) {
      const res = await api.put(`/admin/departments/${editingItem.value.id}`, form.value)
      const idx = departments.value.findIndex(d => d.id === editingItem.value.id)
      if (idx !== -1) departments.value[idx] = res.data
      showMsg('Bo\'lim yangilandi')
    } else {
      const res = await api.post('/admin/departments', form.value)
      departments.value.push(res.data)
      showMsg('Bo\'lim qo\'shildi')
    }
    dialog.value = false
  } catch (err) {
    showMsg(err.response?.data?.message || 'Xato yuz berdi', 'error')
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  deleting.value = true
  try {
    await api.delete(`/admin/departments/${editingItem.value.id}`)
    departments.value = departments.value.filter(d => d.id !== editingItem.value.id)
    deleteDialog.value = false
    showMsg('Bo\'lim o\'chirildi')
  } catch {
    showMsg('O\'chirishda xato', 'error')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <AdminLayout title="Bo'limlar va Xizmatlar">
    <template #header-actions>
      <v-btn class="btn-primary" prepend-icon="mdi-plus" @click="openCreate">
        Bo'lim qo'shish
      </v-btn>
    </template>

    <div class="data-table-wrapper">
      <v-data-table
        :headers="headers"
        :items="departments"
        :loading="loading"
        hover
        density="comfortable"
        loading-text="Yuklanmoqda..."
        no-data-text="Bo'limlar mavjud emas"
      >
        <template #item.index_code="{ item }">
          <span class="dept-index-badge" style="display:inline-flex;">{{ item.index_code }}</span>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small" color="primary" @click="openEdit(item)" />
          <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="openDelete(item)" />
        </template>
      </v-data-table>
    </div>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="520" persistent>
      <v-card style="border-radius:16px; overflow:hidden;">
        <v-card-title style="padding:20px 24px 0; font-size:17px; font-weight:600;">
          {{ editingItem ? 'Bo\'limni tahrirlash' : 'Yangi bo\'lim qo\'shish' }}
        </v-card-title>
        <v-card-text style="padding:16px 24px;">
          <v-form ref="formRef">
            <v-text-field
              v-model="form.name"
              label="Bo'lim nomi"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-text-field
              v-model="form.index_code"
              label="Indeks kodi (masalan: ИШ/)"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-text-field
              v-model="form.head_name"
              label="Rahbar FISH"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-text-field
              v-model="form.head_phone"
              label="Ichki telefon"
              variant="outlined"
              density="comfortable"
            />
          </v-form>
        </v-card-text>
        <v-card-actions style="padding:12px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Bekor qilish</v-btn>
          <v-btn class="btn-primary" :loading="saving" @click="save">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 8px;">O'chirish</v-card-title>
        <v-card-text>
          <b>{{ editingItem?.name }}</b> bo'limini o'chirishni tasdiqlaysizmi?
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Bekor qilish</v-btn>
          <v-btn color="error" :loading="deleting" @click="confirmDelete">O'chirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
      {{ snackbarText }}
    </v-snackbar>
  </AdminLayout>
</template>
