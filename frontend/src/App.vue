<script setup>
import { ref } from 'vue'
import HomePanel from './components/HomePanel.vue'
import WalletPanel from './components/WalletPanel.vue'
import CategoryPanel from './components/CategoryPanel.vue'
import TransactionPanel from './components/TransactionPanel.vue'
import ReportPanel from './components/ReportPanel.vue'

const activeTab = ref('home')

const tabs = [
  { key: 'home', label: 'Beranda', icon: '🏠' },
  { key: 'wallets', label: 'Dompet', icon: '💰' },
  { key: 'transactions', label: 'Transaksi', icon: '📝' },
  { key: 'categories', label: 'Kategori', icon: '🏷️' },
  { key: 'reports', label: 'Laporan', icon: '📈' },
]

function handleNavigate(tabKey) {
  activeTab.value = tabKey
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
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
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

            <!-- 3. Profile / Extras (Right) -->
            <div class="flex-shrink-0 flex items-center gap-3">
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
      <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 animate-fade-in w-full">
        <HomePanel v-if="activeTab === 'home'" @navigate="handleNavigate" />
        <WalletPanel v-else-if="activeTab === 'wallets'" />
        <TransactionPanel v-else-if="activeTab === 'transactions'" />
        <CategoryPanel v-else-if="activeTab === 'categories'" />
        <ReportPanel v-else-if="activeTab === 'reports'" />
      </main>
    </div>
  </div>
</template>

<style>
/* CSS Support for iOS Safe Area (iPhone X+) */
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom);
}

<style>
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
</style>
