<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/plugins/axios'

const signatories = ref([])
const loading = ref(true)
const dialog = ref(false)
const deleteDialog = ref(false)
const saving = ref(false)
const deleting = ref(false)
const editingItem = ref(null)
const form = ref({ position: '', full_name: '', is_active: true })
const formRef = ref(null)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const headers = [
  { title: 'Lavozim', key: 'position', sortable: true },
  { title: 'FISH', key: 'full_name', sortable: true },
  { title: 'Holat', key: 'is_active', sortable: false, width: '100px' },
  { title: 'Amallar', key: 'actions', sortable: false, align: 'end', width: '120px' },
]

const requiredRule = v => !!v?.trim() || 'Majburiy maydon'

onMounted(fetchSignatories)

async function fetchSignatories() {
  loading.value = true
  try {
    const res = await api.get('/signatories')
    signatories.value = res.data
  } finally {
    loading.value = false
  }
}

function showMsg(msg, color = 'success') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

function openCreate() {
  editingItem.value = null
  form.value = { position: '', full_name: '', is_active: true }
  dialog.value = true
}

function openEdit(item) {
  editingItem.value = item
  form.value = { position: item.position, full_name: item.full_name, is_active: item.is_active }
  dialog.value = true
}

function openDelete(item) {
  editingItem.value = item
  deleteDialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  saving.value = true
  try {
    if (editingItem.value) {
      const res = await api.put(`/admin/signatories/${editingItem.value.id}`, form.value)
      const idx = signatories.value.findIndex(s => s.id === editingItem.value.id)
      if (idx !== -1) signatories.value[idx] = res.data
      showMsg('Imzolovchi yangilandi')
    } else {
      const res = await api.post('/admin/signatories', form.value)
      signatories.value.push(res.data)
      showMsg('Imzolovchi qo\'shildi')
    }
    dialog.value = false
  } catch (err) {
    showMsg(err.response?.data?.message || 'Xato', 'error')
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  deleting.value = true
  try {
    await api.delete(`/admin/signatories/${editingItem.value.id}`)
    signatories.value = signatories.value.filter(s => s.id !== editingItem.value.id)
    deleteDialog.value = false
    showMsg('Imzolovchi o\'chirildi')
  } catch {
    showMsg('O\'chirishda xato', 'error')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <AdminLayout title="Imzolovchilar">
    <template #header-actions>
      <v-btn class="btn-primary" prepend-icon="mdi-plus" @click="openCreate">
        Qo'shish
      </v-btn>
    </template>

    <div class="data-table-wrapper">
      <v-data-table
        :headers="headers"
        :items="signatories"
        :loading="loading"
        hover
        density="comfortable"
        loading-text="Yuklanmoqda..."
        no-data-text="Imzolovchilar mavjud emas"
      >
        <template #item.is_active="{ item }">
          <v-chip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Faol' : 'Nofaol' }}
          </v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small" color="primary" @click="openEdit(item)" />
          <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="openDelete(item)" />
        </template>
      </v-data-table>
    </div>

    <!-- Dialog -->
    <v-dialog v-model="dialog" max-width="500" persistent>
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 0; font-size:17px; font-weight:600;">
          {{ editingItem ? 'Imzolovchini tahrirlash' : 'Yangi imzolovchi qo\'shish' }}
        </v-card-title>
        <v-card-text style="padding:16px 24px;">
          <v-form ref="formRef">
            <v-text-field
              v-model="form.position"
              label="Lavozim"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-text-field
              v-model="form.full_name"
              label="FISH (To'liq ismi sharifi)"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-switch
              v-model="form.is_active"
              label="Faol"
              color="success"
              hide-details
            />
          </v-form>
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Bekor</v-btn>
          <v-btn class="btn-primary" :loading="saving" @click="save">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 8px;">O'chirish</v-card-title>
        <v-card-text><b>{{ editingItem?.full_name }}</b> ni o'chirishni tasdiqlaysizmi?</v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
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
