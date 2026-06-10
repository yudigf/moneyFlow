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
  <div class="min-h-screen bg-surface-950">
    <!-- Navbar -->
    <nav class="sticky top-0 z-40 bg-surface-900/80 backdrop-blur-xl border-b border-surface-800">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-3">
            <span class="text-2xl">💸</span>
            <h1 class="text-xl font-bold bg-gradient-to-r from-primary-400 to-primary-600 bg-clip-text text-transparent">
              MoneyFlow
            </h1>
          </div>
          <!-- Mobile friendly nav: scrollable if needed -->
          <div class="flex items-center bg-surface-800 rounded-xl p-1 overflow-x-auto no-scrollbar">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="activeTab === tab.key
                ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/25'
                : 'text-surface-400 hover:text-white'"
              class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 cursor-pointer whitespace-nowrap"
            >
              <span>{{ tab.icon }}</span>
              <span class="hidden sm:inline">{{ tab.label }}</span>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
      <WalletPanel v-if="activeTab === 'wallets'" />
      <TransactionPanel v-else-if="activeTab === 'transactions'" />
      <CategoryPanel v-else-if="activeTab === 'categories'" />
      <ReportPanel v-else-if="activeTab === 'reports'" />
    </main>
  </div>
</template>
