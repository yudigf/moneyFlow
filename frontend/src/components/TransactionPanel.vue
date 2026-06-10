<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api.js'

const transactions = ref([])
const wallets = ref([])
const categories = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingTx = ref(null)

const form = ref({
  wallet_id: '', category_id: '', amount: '', type: 'expense',
  description: '', transaction_date: new Date().toISOString().slice(0, 10),
  destination_wallet_id: '',
})

async function fetchAll() {
  loading.value = true
  try {
    const [txRes, wRes, cRes] = await Promise.all([
      api.get('/transactions'), api.get('/wallets'), api.get('/categories'),
    ])
    transactions.value = txRes.data.data
    wallets.value = wRes.data.data
    categories.value = cRes.data.data
  } catch (err) { console.error(err) }
  finally { loading.value = false }
}

function openCreateForm() {
  editingTx.value = null
  form.value = {
    wallet_id: wallets.value[0]?.id || '', category_id: '', amount: '',
    type: 'expense', description: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    destination_wallet_id: '',
  }
  showForm.value = true
}

function openEditForm(tx) {
  editingTx.value = tx
  form.value = {
    wallet_id: tx.wallet_id, category_id: tx.category_id || '',
    amount: parseFloat(tx.amount), type: tx.type,
    description: tx.description || '',
    transaction_date: tx.transaction_date?.slice(0, 10) || '',
    destination_wallet_id: tx.destination_wallet_id || '',
  }
  showForm.value = true
}

async function saveTx() {
  try {
    const payload = { ...form.value }
    if (payload.type !== 'transfer') payload.destination_wallet_id = null
    if (!payload.category_id) payload.category_id = null
    if (editingTx.value) await api.put(`/transactions/${editingTx.value.id}`, payload)
    else await api.post('/transactions', payload)
    showForm.value = false
    await fetchAll()
  } catch (err) { alert(err.response?.data?.message || 'Gagal menyimpan') }
}

async function deleteTx(tx) {
  if (!confirm('Hapus transaksi ini?')) return
  try { await api.delete(`/transactions/${tx.id}`); await fetchAll() }
  catch (err) { console.error(err) }
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v)
}
function formatDate(d) {
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const typeStyle = {
  income: { icon: '📥', label: 'Pemasukan', cls: 'text-emerald-400' },
  expense: { icon: '📤', label: 'Pengeluaran', cls: 'text-red-400' },
  transfer: { icon: '🔄', label: 'Transfer', cls: 'text-blue-400' },
}

onMounted(fetchAll)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">Transaksi</h2>
        <p class="text-surface-400 text-sm mt-1">Riwayat semua transaksi kamu</p>
      </div>
      <button @click="openCreateForm" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium transition-all cursor-pointer">
        <span class="text-lg">+</span> Tambah Transaksi
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else class="space-y-3">
      <div v-for="tx in transactions" :key="tx.id" class="bg-surface-800/80 border border-surface-700 rounded-xl p-4 flex items-center justify-between group hover:border-surface-600 transition-all">
        <div class="flex items-center gap-4">
          <span class="text-2xl w-10 h-10 flex items-center justify-center rounded-xl bg-surface-700/50">{{ typeStyle[tx.type]?.icon }}</span>
          <div>
            <h3 class="font-medium text-white">{{ tx.description || tx.category?.name || typeStyle[tx.type]?.label }}</h3>
            <div class="flex items-center gap-2 text-xs text-surface-400 mt-0.5">
              <span>{{ tx.wallet?.name }}</span>
              <span v-if="tx.type === 'transfer'">→ {{ tx.destination_wallet?.name }}</span>
              <span>·</span>
              <span>{{ formatDate(tx.transaction_date) }}</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span :class="typeStyle[tx.type]?.cls" class="font-bold text-lg">
            {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(parseFloat(tx.amount)) }}
          </span>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEditForm(tx)" class="p-2 rounded-lg hover:bg-white/10 text-surface-400 hover:text-white cursor-pointer">✏️</button>
            <button @click="deleteTx(tx)" class="p-2 rounded-lg hover:bg-red-500/20 text-surface-400 hover:text-red-400 cursor-pointer">🗑️</button>
          </div>
        </div>
      </div>
      <div v-if="transactions.length === 0" class="text-center text-surface-500 py-12">
        <p class="text-4xl mb-3">📝</p>
        <p>Belum ada transaksi.</p>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showForm = false">
        <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6 w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto">
          <h3 class="text-xl font-bold text-white mb-5">{{ editingTx ? 'Edit Transaksi' : 'Tambah Transaksi' }}</h3>
          <form @submit.prevent="saveTx" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Tipe</label>
              <select v-model="form.type" class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors">
                <option value="expense">📤 Pengeluaran</option>
                <option value="income">📥 Pemasukan</option>
                <option value="transfer">🔄 Transfer</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Dompet Asal</label>
              <select v-model="form.wallet_id" required class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors">
                <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
            <div v-if="form.type === 'transfer'">
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Dompet Tujuan</label>
              <select v-model="form.destination_wallet_id" required class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors">
                <option v-for="w in wallets.filter(x => x.id !== form.wallet_id)" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
            <div v-if="form.type !== 'transfer'">
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Kategori</label>
              <select v-model="form.category_id" class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors">
                <option value="">-- Tanpa Kategori --</option>
                <option v-for="c in categories.filter(x => x.type === form.type)" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Jumlah</label>
              <input v-model.number="form.amount" type="number" min="1" required class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Deskripsi</label>
              <input v-model="form.description" type="text" placeholder="Opsional" class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white placeholder-surface-500 focus:outline-none focus:border-primary-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-surface-300 mb-1.5">Tanggal</label>
              <input v-model="form.transaction_date" type="date" required class="w-full bg-surface-900 border border-surface-600 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary-500 transition-colors" />
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="showForm = false" class="flex-1 bg-surface-700 hover:bg-surface-600 text-surface-300 px-4 py-2.5 rounded-xl font-medium cursor-pointer">Batal</button>
              <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-500 text-white px-4 py-2.5 rounded-xl font-medium cursor-pointer">{{ editingTx ? 'Simpan' : 'Tambah' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
