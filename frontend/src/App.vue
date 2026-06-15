<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import HomePanel from './components/HomePanel.vue'
import WalletPanel from './components/WalletPanel.vue'
import CategoryPanel from './components/CategoryPanel.vue'
import TransactionPanel from './components/TransactionPanel.vue'
import ReportPanel from './components/ReportPanel.vue'
import { startPolling, stopPolling, pollerState, acknowledgeNewTransaction } from './services/botPoller.js'

const activeTab = ref('home')

// ─── Toast notification state ────────────────────────────────────────────────
const toast = ref(null)
let toastTimer = null

function showToast(transaction) {
  clearTimeout(toastTimer)
  toast.value = transaction
  toastTimer = setTimeout(() => {
    toast.value = null
  }, 6000)
}

function dismissToast() {
  clearTimeout(toastTimer)
  toast.value = null
  acknowledgeNewTransaction()
}

function goToTransactions() {
  activeTab.value = 'transactions'
  dismissToast()
}

// ─── Watch for new bot transactions ─────────────────────────────────────────
watch(
  () => pollerState.hasNewBotTransaction.value,
  (hasNew) => {
    if (hasNew && pollerState.lastBotTransaction.value) {
      showToast(pollerState.lastBotTransaction.value)
      acknowledgeNewTransaction()
    }
  }
)

onMounted(() => startPolling(5000))
onUnmounted(() => stopPolling())

// ─── Helpers ─────────────────────────────────────────────────────────────────
const tabs = [
  { key: 'home',         label: 'Beranda',   icon: '🏠' },
  { key: 'wallets',      label: 'Dompet',    icon: '💰' },
  { key: 'transactions', label: 'Transaksi', icon: '📝' },
  { key: 'categories',   label: 'Kategori',  icon: '🏷️' },
  { key: 'reports',      label: 'Laporan',   icon: '📈' },
]

function handleNavigate(tabKey) {
  activeTab.value = tabKey
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>

<template>
  <div class="min-h-screen bg-surface-950 relative overflow-hidden text-surface-100 font-sans pb-20 sm:pb-0">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-primary-600/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute top-1/2 -right-40 w-[400px] h-[400px] bg-emerald-600/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="relative z-10">
      <!-- Premium Glassmorphism Navbar (Desktop Top Nav) -->
      <nav class="sticky top-0 z-40 bg-surface-900/60 backdrop-blur-2xl border-b border-surface-700/50 shadow-lg shadow-black/10">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-32 max-w-[1600px] mx-auto">
          <div class="flex items-center justify-between h-[72px]">

            <!-- 1. Logo (Left) -->
            <div class="flex-shrink-0 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20">
                <span class="text-xl">💸</span>
              </div>
              <h1 class="text-xl font-black tracking-tight text-white hidden sm:block">
                MoneyFlow
              </h1>
            </div>

            <!-- 2. Centered Navigation (Desktop Only) -->
            <div class="hidden sm:flex flex-1 justify-center px-2">
              <div class="flex items-center bg-surface-800/80 rounded-[20px] p-1.5 border border-surface-700/50 shadow-inner">
                <button
                  v-for="tab in tabs"
                  :key="tab.key"
                  @click="activeTab = tab.key"
                  :class="activeTab === tab.key
                    ? 'bg-gradient-to-b from-primary-500 to-primary-600 text-white shadow-md shadow-primary-500/25 ring-1 ring-white/10'
                    : 'text-surface-400 hover:text-white hover:bg-surface-700/50'"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-sm font-semibold transition-all duration-300 cursor-pointer whitespace-nowrap"
                >
                  <span class="text-lg" :class="activeTab === tab.key ? 'scale-110 drop-shadow-md' : 'opacity-70'">{{ tab.icon }}</span>
                  <span>{{ tab.label }}</span>
                </button>
              </div>
            </div>

            <!-- 3. Right side: Bot Badge + Profile -->
            <div class="flex-shrink-0 flex items-center gap-3">
              <!-- Telegram Bot Status Badge -->
              <div
                class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition-all duration-300"
                :class="pollerState.isPolling.value
                  ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400'
                  : 'bg-surface-800/50 border-surface-700 text-surface-500'"
                title="Telegram Bot Status"
              >
                <span class="relative flex h-2 w-2">
                  <span
                    v-if="pollerState.isPolling.value"
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                  ></span>
                  <span
                    class="relative inline-flex rounded-full h-2 w-2"
                    :class="pollerState.isPolling.value ? 'bg-emerald-500' : 'bg-surface-600'"
                  ></span>
                </span>
                🤖 Bot Aktif
              </div>

              <!-- Profile Avatar -->
              <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-surface-700 to-surface-600 border border-surface-500 flex items-center justify-center text-sm font-bold shadow-sm cursor-pointer hover:ring-2 hover:ring-primary-400 transition-all">
                👦
              </div>
            </div>

          </div>
        </div>
      </nav>

      <!-- Bottom Navigation (Mobile Only) -->
      <nav class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface-900/90 backdrop-blur-2xl border-t border-surface-700/50 pb-safe">
        <div class="flex items-center justify-around p-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="flex flex-col items-center justify-center w-16 h-14 rounded-xl transition-all duration-300 cursor-pointer"
            :class="activeTab === tab.key ? 'text-primary-400' : 'text-surface-400 hover:text-surface-200'"
          >
            <div class="relative flex items-center justify-center w-8 h-8 rounded-full mb-1 transition-all duration-300"
                 :class="activeTab === tab.key ? 'bg-primary-500/20 scale-110' : ''">
              <span class="text-xl drop-shadow-sm">{{ tab.icon }}</span>
            </div>
            <span class="text-[10px] font-semibold" :class="activeTab === tab.key ? 'text-primary-400' : ''">{{ tab.label }}</span>
          </button>
        </div>
      </nav>

      <!-- Content -->
      <main class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-32 max-w-[1600px] mx-auto py-8 animate-fade-in">
        <HomePanel v-if="activeTab === 'home'" @navigate="handleNavigate" />
        <WalletPanel v-else-if="activeTab === 'wallets'" />
        <TransactionPanel v-else-if="activeTab === 'transactions'" />
        <CategoryPanel v-else-if="activeTab === 'categories'" />
        <ReportPanel v-else-if="activeTab === 'reports'" />
      </main>
    </div>

    <!-- ─── Bot Transaction Toast Notification ──────────────────────────── -->
    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-24 sm:bottom-6 right-4 sm:right-6 z-[9999] max-w-sm w-full"
      >
        <div class="bg-surface-800 border border-emerald-500/40 rounded-2xl shadow-2xl shadow-black/40 p-4 flex items-start gap-4 backdrop-blur-xl">
          <!-- Icon -->
          <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
            <span class="text-xl">🤖</span>
          </div>
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-emerald-400 mb-0.5 uppercase tracking-wider">Transaksi Baru dari Bot</p>
            <p class="text-sm font-bold text-white truncate">
              {{ toast.description || toast.category?.name || 'Transaksi' }}
            </p>
            <p class="text-xs text-surface-400 mt-0.5">
              <span :class="toast.type === 'income' ? 'text-emerald-400' : 'text-red-400'" class="font-semibold">
                {{ toast.type === 'income' ? '+' : '-' }}{{ formatCurrency(toast.amount) }}
              </span>
              &bull; {{ toast.wallet?.name }}
            </p>
            <!-- Action buttons -->
            <div class="flex items-center gap-3 mt-3">
              <button
                @click="goToTransactions"
                class="text-xs font-semibold text-primary-400 hover:text-primary-300 transition-colors cursor-pointer"
              >
                Lihat Transaksi →
              </button>
              <button
                @click="dismissToast"
                class="text-xs font-medium text-surface-500 hover:text-surface-300 transition-colors cursor-pointer"
              >
                Tutup
              </button>
            </div>
          </div>
          <!-- Close button -->
          <button
            @click="dismissToast"
            class="text-surface-500 hover:text-surface-300 transition-colors flex-shrink-0 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style>
/* CSS Support for iOS Safe Area (iPhone X+) */
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom);
}

.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Toast slide-in animation */
.toast-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-leave-active {
  transition: all 0.3s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
</style>
