<script setup>
import { ref } from 'vue'
import WalletPanel from './components/WalletPanel.vue'
import CategoryPanel from './components/CategoryPanel.vue'
import TransactionPanel from './components/TransactionPanel.vue'
import ReportPanel from './components/ReportPanel.vue'

const activeTab = ref('wallets')

const tabs = [
  { key: 'wallets', label: 'Dompet', icon: '💰' },
  { key: 'transactions', label: 'Transaksi', icon: '📝' },
  { key: 'categories', label: 'Kategori', icon: '🏷️' },
  { key: 'reports', label: 'Laporan', icon: '📈' },
]
</script>

<template>
  <div class="min-h-screen bg-surface-950 relative overflow-hidden text-surface-100 font-sans">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-primary-600/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute top-1/2 -right-40 w-[400px] h-[400px] bg-emerald-600/10 rounded-full blur-[100px] pointer-events-none"></div>
    
    <div class="relative z-10">
      <!-- Premium Glassmorphism Navbar -->
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

            <!-- 2. Centered Navigation -->
            <div class="flex-1 flex justify-center overflow-x-auto no-scrollbar px-2">
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
                  <span class="hidden sm:inline">{{ tab.label }}</span>
                </button>
              </div>
            </div>

            <!-- 3. Profile / Extras (Right) -->
            <div class="flex-shrink-0 flex items-center gap-3 hidden md:flex">
               <button class="p-2.5 text-surface-400 hover:text-white transition-colors cursor-pointer rounded-full hover:bg-surface-800">
                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
               </button>
               <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-surface-700 to-surface-600 border border-surface-500 flex items-center justify-center text-sm font-bold shadow-sm cursor-pointer hover:ring-2 hover:ring-primary-400 transition-all">
                  👦
               </div>
            </div>

          </div>
        </div>
      </nav>

      <!-- Content -->
      <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 animate-fade-in">
        <WalletPanel v-if="activeTab === 'wallets'" />
        <TransactionPanel v-else-if="activeTab === 'transactions'" />
        <CategoryPanel v-else-if="activeTab === 'categories'" />
        <ReportPanel v-else-if="activeTab === 'reports'" />
      </main>
    </div>
  </div>
</template>

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
