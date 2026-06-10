<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api.js'

const categories = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingCategory = ref(null)
const form = ref({ name: '', type: 'expense' })

const typeLabels = {
  expense: { label: 'Pengeluaran', icon: '📤', color: 'text-red-400 bg-red-500/15 border-red-500/30' },
  income: { label: 'Pemasukan', icon: '📥', color: 'text-emerald-400 bg-emerald-500/15 border-emerald-500/30' },
}

async function fetchCategories() {
  loading.value = true
  try {
    const res = await api.get('/categories')
    categories.value = res.data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

function openCreateForm() {
  editingCategory.value = null
  form.value = { name: '', type: 'expense' }
  showForm.value = true
}
function openEditForm(cat) {
  editingCategory.value = cat
  form.value = { name: cat.name, type: cat.type }
  showForm.value = true
}
async function saveCategory() {
  try {
    if (editingCategory.value) await api.put(`/categories/${editingCategory.value.id}`, form.value)
    else await api.post('/categories', form.value)
    showForm.value = false
    await fetchCategories()
  } catch (err) { alert(err.response?.data?.message || 'Gagal menyimpan') }
}
async function deleteCategory(cat) {
  if (!confirm(`Hapus "${cat.name}"?`)) return
  try { await api.delete(`/categories/${cat.id}`); await fetchCategories() }
  catch (err) { console.error(err) }
}
onMounted(fetchCategories)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">Kategori</h2>
        <p class="text-surface-400 text-sm mt-1">Kelola kategori pemasukan &amp; pengeluaran</p>
      </div>
      <button @click="openCreateForm" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium transition-all cursor-pointer">
        <span class="text-lg">+</span> Tambah Kategori
      </button>
    </div>
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>
    <div v-else class="space-y-3">
      <div v-for="cat in categories" :key="cat.id" class="bg-surface-800/80 border border-surface-700 rounded-xl p-4 flex items-center justify-between group hover:border-surface-600 transition-all">
        <div class="flex items-center gap-4">
          <span :class="typeLabels[cat.type]?.color" class="text-lg w-10 h-10 flex items-center justify-center rounded-xl border">{{ typeLabels[cat.type]?.icon }}</span>
          <div>
            <h3 class="font-medium text-white">{{ cat.name }}</h3>
            <span class="text-xs text-surface-400">{{ typeLabels[cat.type]?.label }}</span>
          </div>
        </div>
        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button @click="openEditForm(cat)" class="p-2 rounded-lg hover:bg-white/10 text-surface-400 hover:text-white cursor-pointer">✏️</button>
          <button @click="deleteCategory(cat)" class="p-2 rounded-lg hover:bg-red-500/20 text-surface-400 hover:text-red-400 cursor-pointer">🗑️</button>
        </div>
      </div>
      <div v-if="categories.length === 0" class="text-center text-surface-500 py-12">
        <p class="text-4xl mb-3">📂</p>
        <p>Belum ada kategori.</p>
      </div>
    </div>
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showForm = false">
        <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6 w-full max-w-md shadow-2xl">
          <h3 class="text-xl font-bold text-white mb-5">{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
          <form @submit.prevent="saveCategory" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Nama</label>
              <input v-model="form.name" type="text" required placeholder="contoh: Makan & Minum" class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white placeholder-surface-500 focus:outline-none focus:border-primary-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Tipe</label>
              <select v-model="form.type" class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors">
                <option value="expense">📤 Pengeluaran</option>
                <option value="income">📥 Pemasukan</option>
              </select>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="showForm = false" class="flex-1 bg-surface-700 hover:bg-surface-600 text-surface-300 px-4 py-2.5 rounded-xl font-medium cursor-pointer">Batal</button>
              <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium cursor-pointer">{{ editingCategory ? 'Simpan' : 'Tambah' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
