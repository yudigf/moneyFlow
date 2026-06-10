<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../services/api.js'

const emit = defineEmits(['navigate'])

const wallets = ref([])
const transactions = ref([])
const loading = ref(true)

async function fetchData() {
  loading.value = true
  try {
    const [walletsRes, txRes] = await Promise.all([
      api.get('/wallets'),
      api.get('/transactions')
    ])
    wallets.value = walletsRes.data.data
    transactions.value = txRes.data.data
  } catch (error) {
    console.error('Error fetching home data:', error)
  } finally {
    loading.value = false
  }
}

const totalBalance = computed(() => {
  return wallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0)
})

const recentTransactions = computed(() => {
  // Sort descending by created_at or transaction_date
  return [...transactions.value].sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date)).slice(0, 5)
})

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}

function formatDate(dateStr) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="pb-8">
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else class="lg:grid lg:grid-cols-12 lg:gap-8 lg:items-start space-y-6 lg:space-y-0">
      
      <!-- Left Column (Balance & Wallets) -->
      <div class="lg:col-span-7 xl:col-span-8 space-y-6">
        <!-- Welcome & Total Balance -->
        <div class="bg-gradient-to-br from-primary-600 to-primary-900 border border-primary-500/30 rounded-3xl p-6 shadow-xl shadow-primary-900/50 relative overflow-hidden">
          <!-- Decoration -->
          <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full translate-y-1/3 -translate-x-1/4"></div>
          
          <div class="relative z-10">
            <p class="text-primary-100 font-medium mb-1">Total Saldo Kamu</p>
            <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tight mb-6">
              {{ formatCurrency(totalBalance) }}
            </h2>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-3 gap-3">
              <button @click="emit('navigate', 'transactions')" class="flex flex-col items-center justify-center bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-2xl py-3 transition-colors cursor-pointer">
                <span class="text-2xl mb-1">⬇️</span>
                <span class="text-xs font-semibold text-white">Pemasukan</span>
              </button>
              <button @click="emit('navigate', 'transactions')" class="flex flex-col items-center justify-center bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-2xl py-3 transition-colors cursor-pointer">
                <span class="text-2xl mb-1">⬆️</span>
                <span class="text-xs font-semibold text-white">Pengeluaran</span>
              </button>
              <button @click="emit('navigate', 'transactions')" class="flex flex-col items-center justify-center bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-2xl py-3 transition-colors cursor-pointer">
                <span class="text-2xl mb-1">🔄</span>
                <span class="text-xs font-semibold text-white">Transfer</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Active Wallets -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold text-white">Dompet Aktif</h3>
            <button @click="emit('navigate', 'wallets')" class="text-sm font-medium text-primary-400 hover:text-primary-300 cursor-pointer">Lihat Semua</button>
          </div>
          <div class="flex gap-4 overflow-x-auto no-scrollbar pb-2">
            <div v-for="wallet in wallets" :key="wallet.id" class="min-w-[160px] bg-surface-800/80 border border-surface-700 rounded-2xl p-4 shadow-sm flex-shrink-0">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xl">
                  {{ wallet.type === 'bank' ? '🏦' : (wallet.type === 'e-wallet' ? '📱' : '💵') }}
                </span>
                <span class="font-semibold text-surface-200 text-sm truncate">{{ wallet.name }}</span>
              </div>
              <p class="text-lg font-bold text-white">{{ formatCurrency(wallet.balance) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column (Recent Transactions) -->
      <div class="lg:col-span-5 xl:col-span-4 sticky top-24">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-bold text-white">Transaksi Terakhir</h3>
          <button @click="emit('navigate', 'transactions')" class="text-sm font-medium text-primary-400 hover:text-primary-300 cursor-pointer">Semua Riwayat</button>
        </div>
        
        <div v-if="recentTransactions.length > 0" class="bg-surface-800/80 border border-surface-700 rounded-2xl overflow-hidden shadow-sm">
          <div v-for="(tx, index) in recentTransactions" :key="tx.id" 
               class="flex items-center justify-between p-4"
               :class="{ 'border-b border-surface-700': index !== recentTransactions.length - 1 }">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg shadow-sm"
                   :class="{
                     'bg-emerald-500/20 text-emerald-400': tx.type === 'income',
                     'bg-red-500/20 text-red-400': tx.type === 'expense',
                     'bg-blue-500/20 text-blue-400': tx.type === 'transfer'
                   }">
                {{ tx.type === 'income' ? '⬇️' : (tx.type === 'expense' ? '⬆️' : '🔄') }}
              </div>
              <div>
                <p class="font-semibold text-surface-100 truncate max-w-[120px] sm:max-w-[150px]">{{ tx.description || tx.category?.name || (tx.type === 'transfer' ? 'Transfer Saldo' : 'Lainnya') }}</p>
                <p class="text-xs text-surface-400">{{ tx.wallet.name }} &bull; {{ formatDate(tx.transaction_date) }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold whitespace-nowrap" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-red-400'">
                {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
              </p>
            </div>
          </div>
        </div>
        <div v-else class="bg-surface-800/50 border border-surface-700 border-dashed rounded-2xl p-8 text-center">
          <span class="text-4xl mb-2 block">📝</span>
          <p class="text-surface-400">Belum ada transaksi sama sekali.</p>
        </div>
      </div>

    </div>
  </div>
</template>
