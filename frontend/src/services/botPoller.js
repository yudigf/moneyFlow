/**
 * botPoller.js
 * Polls the transactions API every N seconds to detect new transactions
 * (e.g. ones created by the Telegram bot) and emits a reactive signal.
 */

import { ref, readonly } from 'vue'
import api from './api.js'

// ─── State ───────────────────────────────────────────────────────────────────
const latestTransactionId = ref(null)
const hasNewBotTransaction = ref(false)
const lastBotTransaction = ref(null)
const isPolling = ref(false)

let pollingInterval = null
let isFirstPoll = true

// ─── Internal helpers ─────────────────────────────────────────────────────────

async function checkForNewTransactions() {
  try {
    const res = await api.get('/transactions')
    const transactions = res.data?.data ?? []

    if (transactions.length === 0) return

    // Newest transaction is first (ordered by transaction_date desc)
    const newest = transactions[0]

    if (isFirstPoll) {
      // On the first poll, just store the baseline — no notification
      latestTransactionId.value = newest.id
      isFirstPoll = false
      return
    }

    // A new transaction appeared since last check
    if (newest.id !== latestTransactionId.value) {
      lastBotTransaction.value = newest
      hasNewBotTransaction.value = true
      latestTransactionId.value = newest.id
    }
  } catch (err) {
    // Silently ignore network errors during polling
    console.warn('[BotPoller] polling error:', err.message)
  }
}

// ─── Public API ───────────────────────────────────────────────────────────────

/**
 * Start polling every `intervalMs` milliseconds (default: 5000).
 */
export function startPolling(intervalMs = 5000) {
  if (pollingInterval) return // already running

  isPolling.value = true
  isFirstPoll = true

  // Run immediately then on interval
  checkForNewTransactions()
  pollingInterval = setInterval(checkForNewTransactions, intervalMs)
}

/**
 * Stop polling.
 */
export function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
  isPolling.value = false
}

/**
 * Acknowledge the new-transaction notification (clears the badge).
 */
export function acknowledgeNewTransaction() {
  hasNewBotTransaction.value = false
  lastBotTransaction.value = null
}

// ─── Reactive exports (read-only) ────────────────────────────────────────────
export const pollerState = {
  isPolling: readonly(isPolling),
  hasNewBotTransaction: readonly(hasNewBotTransaction),
  lastBotTransaction: readonly(lastBotTransaction),
}
