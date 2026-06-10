<script setup>
import { ref, onMounted, computed } from 'vue'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'
import api from '../services/api.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)
// ChartJS default configurations for dark mode
ChartJS.defaults.color = '#94a3b8'
ChartJS.defaults.font.family = "'Inter', sans-serif"

const loading = ref(true)
const reportData = ref(null)

const currentDate = ref(new Date())

const currentMonthLabel = computed(() => {
  return currentDate.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

async function fetchReport() {
  loading.value = true
  try {
    const m = currentDate.value.getMonth() + 1
    const y = currentDate.value.getFullYear()
    const res = await api.get(`/reports/summary?month=${m}&year=${y}`)
    reportData.value = res.data.data
  } catch (err) {
    console.error('Failed to fetch report:', err)
  } finally {
    loading.value = false
  }
}

function prevMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
  fetchReport()
}

function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
  fetchReport()
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}

// Prepare Data for Bar Chart (Daily Trends)
const barChartData = computed(() => {
  if (!reportData.value) return { labels: [], datasets: [] }
  const daily = reportData.value.daily_transactions || {}
  const labels = Object.keys(daily).map(date => {
    // get the date number, e.g. "2026-06-10" -> "10"
    return date.split('-')[2]
  })
  const incomeData = Object.values(daily).map(d => d.income)
  const expenseData = Object.values(daily).map(d => d.expense)

  return {
    labels,
    datasets: [
      {
        label: 'Pemasukan',
        backgroundColor: '#10b981', // emerald-500
        borderRadius: 4,
        data: incomeData,
      },
      {
        label: 'Pengeluaran',
        backgroundColor: '#ef4444', // red-500
        borderRadius: 4,
        data: expenseData,
      }
    ]
  }
})

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: {
    y: { grid: { color: '#334155' } }, // surface-700
    x: { grid: { display: false } }
  }
}

// Prepare Data for Doughnut Chart (Expenses by Category)
const doughnutChartData = computed(() => {
  if (!reportData.value) return { labels: [], datasets: [] }
  const catData = reportData.value.expense_by_category || {}
  
  // Custom vibrant colors for categories
  const colors = [
    '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', 
    '#8b5cf6', '#14b8a6', '#f43f5e'
  ]

  return {
    labels: Object.keys(catData),
    datasets: [
      {
        data: Object.values(catData),
        backgroundColor: colors.slice(0, Object.keys(catData).length),
        borderWidth: 0,
        hoverOffset: 4
      }
    ]
  }
})

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'right' } },
  cutout: '70%'
}

onMounted(fetchReport)
</script>

<template>
  <div class="pb-8">
    <!-- Header with Month Selector -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">Laporan Keuangan</h2>
        <p class="text-surface-400 text-sm mt-1">Analisis pengeluaran & pemasukanmu</p>
      </div>
      <div class="flex items-center bg-surface-800 rounded-xl p-1 border border-surface-700">
        <button @click="prevMonth" class="p-2 hover:bg-surface-700 rounded-lg transition-colors cursor-pointer text-surface-300 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <span class="px-4 font-semibold text-white min-w-[140px] text-center">{{ currentMonthLabel }}</span>
        <button @click="nextMonth" class="p-2 hover:bg-surface-700 rounded-lg transition-colors cursor-pointer text-surface-300 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else-if="reportData">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 border border-emerald-500/30 rounded-2xl p-5">
          <p class="text-emerald-300 text-xs font-medium mb-1">Pemasukan Bulan Ini</p>
          <p class="text-2xl font-bold text-emerald-400">{{ formatCurrency(reportData.totals.income) }}</p>
        </div>
        <div class="bg-gradient-to-br from-red-500/20 to-red-600/10 border border-red-500/30 rounded-2xl p-5">
          <p class="text-red-300 text-xs font-medium mb-1">Pengeluaran Bulan Ini</p>
          <p class="text-2xl font-bold text-red-400">{{ formatCurrency(reportData.totals.expense) }}</p>
        </div>
        <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-primary-600/30 to-primary-800/20 border border-primary-500/20 rounded-2xl p-5">
          <p class="text-primary-300 text-xs font-medium mb-1">Selisih Bersih (Net)</p>
          <p class="text-2xl font-bold text-white">{{ formatCurrency(reportData.totals.net) }}</p>
        </div>
      </div>

      <!-- Charts Area -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Doughnut Chart: Expenses by Category -->
        <div class="bg-surface-800/50 border border-surface-700 rounded-2xl p-5">
          <h3 class="text-lg font-semibold text-white mb-4">Pengeluaran per Kategori</h3>
          <div v-if="Object.keys(reportData.expense_by_category).length > 0" class="h-64 relative">
            <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
            <!-- Centered Text -->
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pr-28">
              <span class="text-xs text-surface-400">Total</span>
              <span class="text-sm font-bold text-white">{{ formatCurrency(reportData.totals.expense) }}</span>
            </div>
          </div>
          <div v-else class="h-64 flex items-center justify-center text-surface-500">
            <p>Belum ada pengeluaran bulan ini.</p>
          </div>
        </div>

        <!-- Bar Chart: Daily Trends -->
        <div class="bg-surface-800/50 border border-surface-700 rounded-2xl p-5 lg:col-span-1">
          <h3 class="text-lg font-semibold text-white mb-4">Tren Transaksi Harian</h3>
          <div v-if="Object.keys(reportData.daily_transactions).length > 0" class="h-64">
            <Bar :data="barChartData" :options="barChartOptions" />
          </div>
          <div v-else class="h-64 flex items-center justify-center text-surface-500">
            <p>Belum ada transaksi bulan ini.</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
