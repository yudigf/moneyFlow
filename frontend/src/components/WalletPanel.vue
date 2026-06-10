<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api.js'

const wallets = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingWallet = ref(null)

const form = ref({ name: '', type: 'bank', balance: 0 })

const totalBalance = computed(() => {
  return wallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0)
})

const walletTypeIcons = {
  bank: '🏦',
  'e-wallet': '📱',
  cash: '💵',
}

const walletTypeColors = {
  bank: 'from-blue-500/20 to-blue-600/10 border-blue-500/30',
  'e-wallet': 'from-purple-500/20 to-purple-600/10 border-purple-500/30',
  cash: 'from-emerald-500/20 to-emerald-600/10 border-emerald-500/30',
}

async function fetchWallets() {
  loading.value = true
  try {
    const res = await api.get('/wallets')
    wallets.value = res.data.data
  } catch (err) {
    console.error('Failed to fetch wallets:', err)
  } finally {
    loading.value = false
  }
}

function openCreateForm() {
  editingWallet.value = null
  form.value = { name: '', type: 'bank', balance: 0 }
  showForm.value = true
}

function openEditForm(wallet) {
  editingWallet.value = wallet
  form.value = { name: wallet.name, type: wallet.type, balance: parseFloat(wallet.balance) }
  showForm.value = true
}

async function saveWallet() {
  try {
    const payload = { ...form.value, balance: form.value.balance || 0 }
    if (editingWallet.value) {
      await api.put(`/wallets/${editingWallet.value.id}`, payload)
    } else {
      await api.post('/wallets', payload)
    }
    showForm.value = false
    await fetchWallets()
  } catch (err) {
    console.error('Failed to save wallet:', err)
    alert(err.response?.data?.message || 'Gagal menyimpan wallet')
  }
}

async function deleteWallet(wallet) {
  if (!confirm(`Hapus wallet "${wallet.name}"?`)) return
  try {
    await api.delete(`/wallets/${wallet.id}`)
    await fetchWallets()
  } catch (err) {
    console.error('Failed to delete wallet:', err)
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

onMounted(fetchWallets)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">Dompet Saya</h2>
        <p class="text-surface-400 text-sm mt-1">Kelola semua dompet dan saldo kamu</p>
      </div>
      <button
        @click="openCreateForm"
        class="flex items-center gap-2 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium transition-all duration-200 cursor-pointer hover:shadow-lg hover:shadow-primary-600/25"
      >
        <span class="text-lg">+</span>
        Tambah Dompet
      </button>
    </div>

    <!-- Total Balance Card -->
    <div class="bg-gradient-to-br from-primary-600/30 to-primary-800/20 border border-primary-500/20 rounded-2xl p-6 mb-6 backdrop-blur-sm">
      <p class="text-primary-300 text-sm font-medium mb-1">Total Saldo</p>
      <p class="text-3xl font-bold text-white">{{ formatCurrency(totalBalance) }}</p>
      <p class="text-primary-400 text-xs mt-2">{{ wallets.length }} dompet aktif</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Wallet Cards Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="wallet in wallets"
        :key="wallet.id"
        :class="walletTypeColors[wallet.type]"
        class="bg-gradient-to-br border rounded-2xl p-5 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ walletTypeIcons[wallet.type] }}</span>
            <div>
              <h3 class="font-semibold text-white">{{ wallet.name }}</h3>
              <span class="text-xs text-surface-400 capitalize">{{ wallet.type }}</span>
            </div>
          </div>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="openEditForm(wallet)"
              class="p-1.5 rounded-lg hover:bg-white/10 text-surface-400 hover:text-white transition-colors cursor-pointer"
              title="Edit"
            >✏️</button>
            <button
              @click="deleteWallet(wallet)"
              class="p-1.5 rounded-lg hover:bg-red-500/20 text-surface-400 hover:text-red-400 transition-colors cursor-pointer"
              title="Hapus"
            >🗑️</button>
          </div>
        </div>
        <p class="text-xl font-bold text-white">{{ formatCurrency(parseFloat(wallet.balance)) }}</p>
      </div>
    </div>

    <!-- Modal Form -->
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showForm = false">
        <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6 w-full max-w-md shadow-2xl">
          <h3 class="text-xl font-bold text-white mb-5">
            {{ editingWallet ? 'Edit Dompet' : 'Tambah Dompet Baru' }}
          </h3>
          <form @submit.prevent="saveWallet" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Nama Dompet</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="contoh: BCA, DANA, Uang Tunai"
                class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white placeholder-surface-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Tipe</label>
              <select
                v-model="form.type"
                class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
              >
                <option value="bank">🏦 Bank</option>
                <option value="e-wallet">📱 E-Wallet</option>
                <option value="cash">💵 Cash</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Saldo Awal</label>
              <input
                v-model.number="form.balance"
                type="number"
                min="0"
                step="1000"
                class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white placeholder-surface-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
              />
            </div>
            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="showForm = false"
                class="flex-1 bg-surface-700 hover:bg-surface-600 text-surface-300 px-4 py-2.5 rounded-xl font-medium transition-colors cursor-pointer"
              >Batal</button>
              <button
                type="submit"
                class="flex-1 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium transition-all duration-200 cursor-pointer hover:shadow-lg hover:shadow-primary-600/25"
              >{{ editingWallet ? 'Simpan' : 'Tambah' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
