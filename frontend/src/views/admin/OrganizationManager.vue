<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/plugins/axios'

const organizations = ref([])
const loading = ref(true)
const activeTab = ref('yuqori')

const tabs = [
  { value: 'yuqori', label: 'Yuqori turuvchi' },
  { value: 'quyi', label: 'Quyi tashkilotlar' },
  { value: 'boshqa', label: 'Boshqa tashkilotlar' },
]

const filteredOrgs = computed(() =>
  organizations.value.filter(o => o.type === activeTab.value)
)

// --- Org dialogs ---
const orgDialog = ref(false)
const orgDeleteDialog = ref(false)
const orgSaving = ref(false)
const orgDeleting = ref(false)
const editingOrg = ref(null)
const orgForm = ref({ name: '', type: 'yuqori' })
const orgFormRef = ref(null)

// --- Leader dialogs ---
const leaderDialog = ref(false)
const leaderDeleteDialog = ref(false)
const leaderSaving = ref(false)
const leaderDeleting = ref(false)
const editingLeader = ref(null)
const selectedOrg = ref(null)
const leaderForm = ref({ position: '', full_name: '' })
const leaderFormRef = ref(null)

// --- Expand state ---
const expandedOrgs = ref({})

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const requiredRule = v => !!v?.trim() || 'Majburiy maydon'

onMounted(fetchAll)

async function fetchAll() {
  loading.value = true
  try {
    const res = await api.get('/organizations')
    organizations.value = res.data
  } finally {
    loading.value = false
  }
}

function showMsg(msg, color = 'success') {
  snackbarText.value = msg
  snackbarColor.value = color
  snackbar.value = true
}

function openCreateOrg() {
  editingOrg.value = null
  orgForm.value = { name: '', type: activeTab.value }
  orgDialog.value = true
}

function openEditOrg(org) {
  editingOrg.value = org
  orgForm.value = { name: org.name, type: org.type }
  orgDialog.value = true
}

function openDeleteOrg(org) {
  editingOrg.value = org
  orgDeleteDialog.value = true
}

async function saveOrg() {
  const { valid } = await orgFormRef.value.validate()
  if (!valid) return
  orgSaving.value = true
  try {
    if (editingOrg.value) {
      const res = await api.put(`/admin/organizations/${editingOrg.value.id}`, orgForm.value)
      const idx = organizations.value.findIndex(o => o.id === editingOrg.value.id)
      if (idx !== -1) organizations.value[idx] = res.data
      showMsg('Tashkilot yangilandi')
    } else {
      const res = await api.post('/admin/organizations', orgForm.value)
      organizations.value.push(res.data)
      showMsg('Tashkilot qo\'shildi')
    }
    orgDialog.value = false
  } catch (err) {
    showMsg(err.response?.data?.message || 'Xato', 'error')
  } finally {
    orgSaving.value = false
  }
}

async function confirmDeleteOrg() {
  orgDeleting.value = true
  try {
    await api.delete(`/admin/organizations/${editingOrg.value.id}`)
    organizations.value = organizations.value.filter(o => o.id !== editingOrg.value.id)
    orgDeleteDialog.value = false
    showMsg('Tashkilot o\'chirildi')
  } catch {
    showMsg('O\'chirishda xato', 'error')
  } finally {
    orgDeleting.value = false
  }
}

function openCreateLeader(org) {
  selectedOrg.value = org
  editingLeader.value = null
  leaderForm.value = { position: '', full_name: '' }
  leaderDialog.value = true
}

function openEditLeader(org, leader) {
  selectedOrg.value = org
  editingLeader.value = leader
  leaderForm.value = { position: leader.position, full_name: leader.full_name }
  leaderDialog.value = true
}

function openDeleteLeader(org, leader) {
  selectedOrg.value = org
  editingLeader.value = leader
  leaderDeleteDialog.value = true
}

async function saveLeader() {
  const { valid } = await leaderFormRef.value.validate()
  if (!valid) return
  leaderSaving.value = true
  try {
    if (editingLeader.value) {
      const res = await api.put(
        `/admin/organizations/${selectedOrg.value.id}/leaders/${editingLeader.value.id}`,
        leaderForm.value
      )
      const orgIdx = organizations.value.findIndex(o => o.id === selectedOrg.value.id)
      if (orgIdx !== -1) {
        const lIdx = organizations.value[orgIdx].leaders.findIndex(l => l.id === editingLeader.value.id)
        if (lIdx !== -1) organizations.value[orgIdx].leaders[lIdx] = res.data
      }
      showMsg('Rahbar yangilandi')
    } else {
      const res = await api.post(
        `/admin/organizations/${selectedOrg.value.id}/leaders`,
        leaderForm.value
      )
      const orgIdx = organizations.value.findIndex(o => o.id === selectedOrg.value.id)
      if (orgIdx !== -1) organizations.value[orgIdx].leaders.push(res.data)
      showMsg('Rahbar qo\'shildi')
    }
    leaderDialog.value = false
  } catch (err) {
    showMsg(err.response?.data?.message || 'Xato', 'error')
  } finally {
    leaderSaving.value = false
  }
}

async function confirmDeleteLeader() {
  leaderDeleting.value = true
  try {
    await api.delete(
      `/admin/organizations/${selectedOrg.value.id}/leaders/${editingLeader.value.id}`
    )
    const orgIdx = organizations.value.findIndex(o => o.id === selectedOrg.value.id)
    if (orgIdx !== -1) {
      organizations.value[orgIdx].leaders = organizations.value[orgIdx].leaders.filter(
        l => l.id !== editingLeader.value.id
      )
    }
    leaderDeleteDialog.value = false
    showMsg('Rahbar o\'chirildi')
  } catch {
    showMsg('O\'chirishda xato', 'error')
  } finally {
    leaderDeleting.value = false
  }
}

function toggleExpand(orgId) {
  expandedOrgs.value[orgId] = !expandedOrgs.value[orgId]
}
</script>

<template>
  <AdminLayout title="Tashkilotlar va Rahbarlar">
    <template #header-actions>
      <v-btn class="btn-primary" prepend-icon="mdi-plus" @click="openCreateOrg">
        Tashkilot qo'shish
      </v-btn>
    </template>

    <!-- Tabs -->
    <v-tabs v-model="activeTab" color="primary" style="margin-bottom:20px; background:#fff; border-radius:12px; border:1px solid var(--border-color);">
      <v-tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
        {{ tab.label }}
      </v-tab>
    </v-tabs>

    <div v-if="loading" style="display:flex; justify-content:center; padding:40px;">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <div v-else>
      <div v-if="!filteredOrgs.length" style="text-align:center; padding:48px; background:#fff; border-radius:12px; border:1px solid var(--border-color);">
        <v-icon size="48" color="#CBD5E1">mdi-domain</v-icon>
        <p style="color:var(--text-secondary); margin-top:8px;">Tashkilotlar mavjud emas</p>
      </div>

      <div v-else style="display:flex; flex-direction:column; gap:10px;">
        <div
          v-for="org in filteredOrgs"
          :key="org.id"
          style="background:#fff; border:1px solid var(--border-color); border-radius:12px; overflow:hidden;"
        >
          <!-- Org header -->
          <div
            style="display:flex; align-items:center; padding:14px 18px; cursor:pointer; gap:12px;"
            @click="toggleExpand(org.id)"
          >
            <v-icon size="20" color="#64748B">mdi-domain</v-icon>
            <div style="flex:1;">
              <div style="font-size:15px; font-weight:500; color:var(--text-primary);">{{ org.name }}</div>
              <div style="font-size:12px; color:var(--text-secondary);">{{ org.leaders?.length || 0 }} rahbar</div>
            </div>
            <v-btn icon size="x-small" variant="text" color="primary" @click.stop="openCreateLeader(org)">
              <v-icon>mdi-account-plus</v-icon>
            </v-btn>
            <v-btn icon size="x-small" variant="text" color="primary" @click.stop="openEditOrg(org)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon size="x-small" variant="text" color="error" @click.stop="openDeleteOrg(org)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
            <v-icon>{{ expandedOrgs[org.id] ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
          </div>

          <!-- Leaders list -->
          <div v-if="expandedOrgs[org.id]" style="border-top:1px solid var(--border-color);">
            <div v-if="!org.leaders?.length" style="padding:14px 18px; color:var(--text-secondary); font-size:13px;">
              Rahbarlar yo'q. Qo'shish uchun + tugmasini bosing.
            </div>
            <div
              v-for="leader in org.leaders"
              :key="leader.id"
              style="display:flex; align-items:center; padding:10px 18px 10px 48px; border-bottom:1px solid #F1F5F9; gap:12px;"
            >
              <v-icon size="16" color="#94A3B8">mdi-account</v-icon>
              <div style="flex:1;">
                <div style="font-size:14px; font-weight:500;">{{ leader.full_name }}</div>
                <div style="font-size:12px; color:var(--text-secondary);">{{ leader.position }}</div>
              </div>
              <v-btn icon size="x-small" variant="text" color="primary" @click="openEditLeader(org, leader)">
                <v-icon size="16">mdi-pencil</v-icon>
              </v-btn>
              <v-btn icon size="x-small" variant="text" color="error" @click="openDeleteLeader(org, leader)">
                <v-icon size="16">mdi-delete</v-icon>
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Org Dialog -->
    <v-dialog v-model="orgDialog" max-width="480" persistent>
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 0; font-size:17px; font-weight:600;">
          {{ editingOrg ? 'Tashkilotni tahrirlash' : 'Yangi tashkilot' }}
        </v-card-title>
        <v-card-text style="padding:16px 24px;">
          <v-form ref="orgFormRef">
            <v-text-field
              v-model="orgForm.name"
              label="Tashkilot nomi"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-select
              v-model="orgForm.type"
              :items="tabs"
              item-title="label"
              item-value="value"
              label="Turi"
              variant="outlined"
              density="comfortable"
            />
          </v-form>
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="orgDialog = false">Bekor</v-btn>
          <v-btn class="btn-primary" :loading="orgSaving" @click="saveOrg">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Org Delete -->
    <v-dialog v-model="orgDeleteDialog" max-width="400">
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 8px;">O'chirish</v-card-title>
        <v-card-text><b>{{ editingOrg?.name }}</b> ni o'chirishni tasdiqlaysizmi?</v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="orgDeleteDialog = false">Bekor</v-btn>
          <v-btn color="error" :loading="orgDeleting" @click="confirmDeleteOrg">O'chirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Leader Dialog -->
    <v-dialog v-model="leaderDialog" max-width="480" persistent>
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 4px; font-size:17px; font-weight:600;">
          {{ editingLeader ? 'Rahbarni tahrirlash' : 'Yangi rahbar qo\'shish' }}
        </v-card-title>
        <v-card-subtitle v-if="selectedOrg" style="padding:0 24px 12px; font-size:13px;">
          {{ selectedOrg.name }}
        </v-card-subtitle>
        <v-card-text style="padding:8px 24px;">
          <v-form ref="leaderFormRef">
            <v-text-field
              v-model="leaderForm.position"
              label="Lavozim"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
              class="mb-3"
            />
            <v-text-field
              v-model="leaderForm.full_name"
              label="FISH (To'liq ismi sharifi)"
              variant="outlined"
              density="comfortable"
              :rules="[requiredRule]"
            />
          </v-form>
        </v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="leaderDialog = false">Bekor</v-btn>
          <v-btn class="btn-primary" :loading="leaderSaving" @click="saveLeader">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Leader Delete -->
    <v-dialog v-model="leaderDeleteDialog" max-width="400">
      <v-card style="border-radius:16px;">
        <v-card-title style="padding:20px 24px 8px;">O'chirish</v-card-title>
        <v-card-text><b>{{ editingLeader?.full_name }}</b> ni o'chirishni tasdiqlaysizmi?</v-card-text>
        <v-card-actions style="padding:8px 24px 20px; gap:8px;">
          <v-spacer />
          <v-btn variant="text" @click="leaderDeleteDialog = false">Bekor</v-btn>
          <v-btn color="error" :loading="leaderDeleting" @click="confirmDeleteLeader">O'chirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
      {{ snackbarText }}
    </v-snackbar>
  </AdminLayout>
</template>
