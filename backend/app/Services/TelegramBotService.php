<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $token;
    private string $apiBase;

    public function __construct()
    {
        $this->token   = config('services.telegram.bot_token');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    // -----------------------------------------------------------------------
    // Public entry point — called by the webhook controller
    // -----------------------------------------------------------------------

    /**
     * Process an incoming Telegram update payload.
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = $message['chat']['id'];
        $text   = trim($message['text'] ?? '');

        if (empty($text)) {
            return;
        }

        // --- Command handlers ---
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text);
            return;
        }

        // --- Natural language transaction ---
        $this->handleTransactionMessage($chatId, $text);
    }

    // -----------------------------------------------------------------------
    // Command handlers
    // -----------------------------------------------------------------------

    private function handleCommand(int $chatId, string $text): void
    {
        $command = strtolower(explode(' ', $text)[0]);

        match (true) {
            in_array($command, ['/saldo', '/balance']) => $this->sendBalanceSummary($chatId),
            in_array($command, ['/laporan', '/report']) => $this->sendMonthlyReport($chatId),
            in_array($command, ['/help', '/bantuan'])  => $this->sendHelp($chatId),
            in_array($command, ['/start'])              => $this->sendWelcome($chatId),
            default => $this->sendMessage($chatId, "❓ Perintah tidak dikenal. Ketik /help untuk daftar perintah."),
        };
    }

    private function sendWelcome(int $chatId): void
    {
        $text = "👋 *Halo! Saya MoneyFlow Bot!*\n\n"
              . "Saya bisa bantu kamu mencatat transaksi langsung dari chat ini.\n\n"
              . "Cukup ketik seperti:\n"
              . "• `beli makan 25rb`\n"
              . "• `terima gaji 5jt`\n"
              . "• `bayar listrik 150000`\n\n"
              . "Ketik /help untuk panduan lengkap.";
        $this->sendMessage($chatId, $text);
    }

    private function sendHelp(int $chatId): void
    {
        $text = "📖 *Panduan MoneyFlow Bot*\n\n"
              . "*💸 Catat Pengeluaran:*\n"
              . "`beli [keterangan] [nominal]`\n"
              . "`bayar [keterangan] [nominal] pakai [dompet]`\n"
              . "`pengeluaran [keterangan] [nominal]`\n\n"
              . "*💰 Catat Pemasukan:*\n"
              . "`terima [keterangan] [nominal] via [dompet]`\n"
              . "`gaji [nominal]`\n\n"
              . "*🔄 Transfer Antar Dompet:*\n"
              . "`transfer [nominal] dari [dompet1] ke [dompet2]`\n\n"
              . "*📏 Format Nominal:*\n"
              . "`25rb` = Rp 25.000\n"
              . "`5jt` = Rp 5.000.000\n"
              . "`150000` = Rp 150.000\n\n"
              . "*📋 Perintah Lain:*\n"
              . "/saldo — Lihat semua saldo dompet\n"
              . "/laporan — Laporan bulan ini\n"
              . "/help — Tampilkan panduan ini";
        $this->sendMessage($chatId, $text);
    }

    private function sendBalanceSummary(int $chatId): void
    {
        $wallets = Wallet::all();

        if ($wallets->isEmpty()) {
            $this->sendMessage($chatId, "❌ Belum ada dompet yang terdaftar.");
            return;
        }

        $total = $wallets->sum(fn ($w) => (float) $w->balance);
        $lines = $wallets->map(fn ($w) => "• {$w->name}: *" . $this->formatRupiah($w->balance) . "*")->join("\n");

        $text = "💰 *Saldo Dompetmu:*\n\n{$lines}\n\n"
              . "💎 *Total: " . $this->formatRupiah($total) . "*";
        $this->sendMessage($chatId, $text);
    }

    private function sendMonthlyReport(int $chatId): void
    {
        $now        = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $transactions = Transaction::whereBetween('transaction_date', [$startOfMonth, $now])->get();

        $income  = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $net     = $income - $expense;
        $count   = $transactions->count();

        $text = "📊 *Laporan " . $now->translatedFormat('F Y') . ":*\n\n"
              . "⬇️ Pemasukan:   *" . $this->formatRupiah($income) . "*\n"
              . "⬆️ Pengeluaran: *" . $this->formatRupiah($expense) . "*\n"
              . "─────────────────\n"
              . ($net >= 0 ? "✅" : "⚠️") . " Net: *" . $this->formatRupiah($net) . "*\n\n"
              . "📝 Total {$count} transaksi bulan ini.";
        $this->sendMessage($chatId, $text);
    }

    // -----------------------------------------------------------------------
    // Natural language transaction parsing
    // -----------------------------------------------------------------------

    private function handleTransactionMessage(int $chatId, string $text): void
    {
        $parsed = $this->parseMessage($text);

        if (! $parsed) {
            $reply = "🤔 Saya tidak mengerti pesanmu.\n\n"
                   . "Coba format seperti:\n"
                   . "• `beli makan 25rb`\n"
                   . "• `terima gaji 5jt`\n"
                   . "• `bayar listrik 150k pakai bca`\n"
                   . "• `transfer 500rb dari bca ke gopay`\n\n"
                   . "Ketik /help untuk panduan lengkap.";
            $this->sendMessage($chatId, $reply);
            return;
        }

        // Resolve wallet
        $wallet = $this->resolveWallet($parsed['wallet_hint']);
        if (! $wallet) {
            $wallet = Wallet::first(); // Default fallback
        }

        if (! $wallet) {
            $this->sendMessage($chatId, "❌ Tidak ada dompet. Buat dompet dulu di aplikasi MoneyFlow.");
            return;
        }

        // Handle transfer logic
        $destWallet = null;
        if ($parsed['type'] === 'transfer') {
            $destWallet = $this->resolveWallet($parsed['dest_wallet_hint']);
            
            // If user just said "transfer 500rb ke gopay" without "dari", we assume default wallet as source
            // If destination is missing, we abort
            if (! $destWallet) {
                $this->sendMessage($chatId, "❌ Gagal mendeteksi dompet tujuan. Gunakan format: `transfer 500rb dari BCA ke GOPAY`.");
                return;
            }
            if ($wallet->id === $destWallet->id) {
                $this->sendMessage($chatId, "❌ Dompet asal dan tujuan tidak boleh sama.");
                return;
            }
        }

        // Find or auto-create matching category
        $category = null;
        if ($parsed['type'] !== 'transfer') {
            $category = $this->resolveCategory($parsed['category_hint'], $parsed['type']);
        }

        // Create transaction
        $transaction = Transaction::create([
            'wallet_id'             => $wallet->id,
            'category_id'           => $category?->id,
            'destination_wallet_id' => $destWallet?->id,
            'amount'                => $parsed['amount'],
            'type'                  => $parsed['type'],
            'description'           => $parsed['description'],
            'transaction_date'      => now(),
        ]);

        $transaction->load(['wallet', 'category', 'destinationWallet']);

        // Reply with confirmation
        if ($parsed['type'] === 'transfer') {
            $reply = "✅ *Transfer Berhasil!*\n\n"
                   . "🔄 Transfer Antar Dompet\n"
                   . "💵 Nominal: *" . $this->formatRupiah($parsed['amount']) . "*\n"
                   . "🏦 Dari: {$wallet->name}\n"
                   . "🏦 Ke: {$destWallet->name}\n\n"
                   . "_Saldo {$wallet->name}: " . $this->formatRupiah($wallet->fresh()->balance) . "_\n"
                   . "_Saldo {$destWallet->name}: " . $this->formatRupiah($destWallet->fresh()->balance) . "_";
        } else {
            $typeEmoji = $parsed['type'] === 'income' ? '⬇️ Pemasukan' : '⬆️ Pengeluaran';
            $sign      = $parsed['type'] === 'income' ? '+' : '-';
            $catName   = $category ? $category->name : 'Lainnya';

            $reply = "✅ *Transaksi Dicatat!*\n\n"
                   . "{$typeEmoji}\n"
                   . "💵 Nominal: *{$sign}" . $this->formatRupiah($parsed['amount']) . "*\n"
                   . "🏷️ Kategori: {$catName}\n"
                   . "📝 Keterangan: {$parsed['description']}\n"
                   . "🏦 Dompet: {$wallet->name}\n\n"
                   . "_Saldo sekarang: " . $this->formatRupiah($wallet->fresh()->balance) . "_";
        }

        $this->sendMessage($chatId, $reply);
    }

    /**
     * Parse a natural language message into transaction components.
     * Returns null if the message cannot be parsed.
     *
     * @return array{type: string, amount: float, description: string, category_hint: string}|null
     */
    public function parseMessage(string $text): ?array
    {
        $text = strtolower(trim($text));

        // Determine transaction type from leading keywords
        $type             = null;
        $transferKeywords = ['transfer', 'pindah', 'mutasi'];
        $incomeKeywords   = ['terima', 'masuk', 'dapat', 'pemasukan', 'income', 'gaji', 'bayaran', 'bonus', 'dividen'];
        $expenseKeywords  = ['beli', 'bayar', 'keluar', 'pengeluaran', 'expense', 'belanja', 'jajan', 'makan', 'minum', 'beli makan',
                             'bayar tagihan', 'iuran', 'cicilan', 'bensin', 'isi', 'ngisi', 'top up', 'topup', 'kirim'];

        // Check transfer first
        foreach ($transferKeywords as $kw) {
            if (str_starts_with($text, $kw)) {
                $type = 'transfer';
                break;
            }
        }

        // Check income
        if (! $type) {
            foreach ($incomeKeywords as $kw) {
                if (str_starts_with($text, $kw) || str_contains($text, $kw)) {
                    $type = 'income';
                    break;
                }
            }
        }

        // Check expense
        if (! $type) {
            foreach ($expenseKeywords as $kw) {
                if (str_starts_with($text, $kw)) {
                    $type = 'expense';
                    break;
                }
            }
        }

        // Extract amount from anywhere in the string
        $amount = $this->extractAmount($text);
        if (! $amount) {
            return null;
        }

        // Default type to expense if amount found but no type keyword
        $type ??= 'expense';

        // Extract wallet hints and remove them from text
        [$walletHint, $destWalletHint] = $this->extractWalletHints($text, $type);

        // Build description: remove amount token from text, clean up
        $description = $this->buildDescription($text);

        // Category hint: first non-amount word after the action verb
        $categoryHint = $this->extractCategoryHint($text);

        return [
            'type'             => $type,
            'amount'           => $amount,
            'description'      => ucfirst($description),
            'category_hint'    => $categoryHint,
            'wallet_hint'      => $walletHint,
            'dest_wallet_hint' => $destWalletHint,
        ];
    }

    /**
     * Extract a numeric amount from informal Indonesian formats.
     * Supports: 25rb, 25k, 25ribu, 5jt, 5juta, 1.5jt, 150000, 150_000
     */
    private function extractAmount(string $text): ?float
    {
        // Pattern: number (with optional decimal dot/comma) followed by optional multiplier suffix
        $pattern = '/(\d{1,3}(?:[.,]\d{1,3})*(?:[.,]\d+)?)\s*(jt|juta|rb|ribu|k|m|miliar|b|billion)?/i';

        if (! preg_match($pattern, $text, $matches)) {
            return null;
        }

        // Normalise number: remove thousands separator dots/commas when followed by multiplier or 3 digits
        $raw    = $matches[1];
        $suffix = strtolower($matches[2] ?? '');

        // If there is a suffix, the comma/dot is a decimal separator
        // Otherwise treat trailing 3-digit group as full integer
        $raw = str_replace(',', '.', $raw);

        // If multiple dots, strip all but last
        $parts = explode('.', $raw);
        if (count($parts) > 2) {
            $decimal = array_pop($parts);
            $raw     = implode('', $parts) . '.' . $decimal;
        }

        $number = (float) $raw;

        return match ($suffix) {
            'jt', 'juta'          => $number * 1_000_000,
            'rb', 'ribu', 'k'     => $number * 1_000,
            'm', 'miliar', 'b', 'billion' => $number * 1_000_000_000,
            default               => $number,
        };
    }

    /**
     * Build a human-readable description by removing amount tokens and normalising.
     */
    private function buildDescription(string $text): string
    {
        // Remove amount tokens
        $clean = preg_replace('/\d{1,3}(?:[.,]\d{1,3})*(?:[.,]\d+)?\s*(?:jt|juta|rb|ribu|k|m|miliar|b|billion)?/i', '', $text);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        // If transfer, remove "transfer dari ke" etc
        if (str_starts_with($clean, 'transfer') || str_starts_with($clean, 'pindah')) {
            return 'Transfer Saldo';
        }

        return $clean ?: $text;
    }

    /**
     * Extract wallet hints from text and remove those parts so they don't pollute the description.
     */
    private function extractWalletHints(string &$text, string $type): array
    {
        $walletHint = null;
        $destWalletHint = null;

        if ($type === 'transfer') {
            if (preg_match('/dari\s+([a-z0-9\-]+)/i', $text, $m1)) {
                $walletHint = $m1[1];
                $text = str_replace($m1[0], '', $text);
            }
            if (preg_match('/ke\s+([a-z0-9\-]+)/i', $text, $m2)) {
                $destWalletHint = $m2[1];
                $text = str_replace($m2[0], '', $text);
            }
        } else {
            if (preg_match('/(?:pakai|pake|via|menggunakan)\s+([a-z0-9\-]+)/i', $text, $m)) {
                $walletHint = $m[1];
                $text = str_replace($m[0], '', $text);
            }
        }

        return [$walletHint, $destWalletHint];
    }

    /**
     * Extract a short category hint (typically the noun after the verb).
     */
    private function extractCategoryHint(string $text): string
    {
        // Remove amount tokens first
        $clean = preg_replace('/\d{1,3}(?:[.,]\d{1,3})*(?:[.,]\d+)?\s*(?:jt|juta|rb|ribu|k|m|miliar|b|billion)?/i', '', $text);
        $words = array_values(array_filter(explode(' ', trim($clean))));

        // Return words 2 onward (skip the action verb) joined
        return implode(' ', array_slice($words, 1)) ?: ($words[0] ?? $text);
    }

    // -----------------------------------------------------------------------
    // Category resolution
    // -----------------------------------------------------------------------

    /**
     * Keyword map: hint word → category name (in DB).
     * Case-insensitive partial match.
     */
    private array $categoryKeywords = [
        // Expense categories
        'makan'     => 'Makanan',
        'minum'     => 'Makanan',
        'jajan'     => 'Makanan',
        'food'      => 'Makanan',
        'restoran'  => 'Makanan',
        'kopi'      => 'Makanan',
        'cafe'      => 'Makanan',

        'bensin'    => 'Transportasi',
        'bbm'       => 'Transportasi',
        'grab'      => 'Transportasi',
        'gojek'     => 'Transportasi',
        'ojek'      => 'Transportasi',
        'bus'       => 'Transportasi',
        'kereta'    => 'Transportasi',
        'parkir'    => 'Transportasi',
        'toll'      => 'Transportasi',
        'tol'       => 'Transportasi',
        'angkot'    => 'Transportasi',
        'isi'       => 'Transportasi',

        'listrik'   => 'Tagihan',
        'air'       => 'Tagihan',
        'pdam'      => 'Tagihan',
        'internet'  => 'Tagihan',
        'wifi'      => 'Tagihan',
        'pulsa'     => 'Tagihan',
        'tagihan'   => 'Tagihan',
        'cicilan'   => 'Tagihan',
        'iuran'     => 'Tagihan',
        'token'     => 'Tagihan',

        'belanja'   => 'Belanja',
        'baju'      => 'Belanja',
        'beli'      => 'Belanja',
        'pakaian'   => 'Belanja',
        'sepatu'    => 'Belanja',
        'tas'       => 'Belanja',
        'shop'      => 'Belanja',
        'shopee'    => 'Belanja',
        'tokopedia' => 'Belanja',
        'lazada'    => 'Belanja',

        'obat'      => 'Kesehatan',
        'dokter'    => 'Kesehatan',
        'apotek'    => 'Kesehatan',
        'rumah sakit' => 'Kesehatan',
        'rs'        => 'Kesehatan',
        'klinik'    => 'Kesehatan',
        'vitamin'   => 'Kesehatan',

        'hiburan'   => 'Hiburan',
        'bioskop'   => 'Hiburan',
        'nonton'    => 'Hiburan',
        'game'      => 'Hiburan',
        'netflix'   => 'Hiburan',
        'spotify'   => 'Hiburan',

        // Income categories
        'gaji'      => 'Gaji',
        'salary'    => 'Gaji',
        'slip'      => 'Gaji',

        'bonus'     => 'Bonus',
        'thr'       => 'Bonus',
        'insentif'  => 'Bonus',

        'freelance' => 'Freelance',
        'proyek'    => 'Freelance',
        'project'   => 'Freelance',
        'jasa'      => 'Freelance',

        'investasi' => 'Investasi',
        'dividen'   => 'Investasi',
        'saham'     => 'Investasi',
        'reksa'     => 'Investasi',
        'bunga'     => 'Investasi',
    ];

    private function resolveCategory(string $hint, string $type): ?Category
    {
        $hint = strtolower($hint);

        $matchedName = null;
        foreach ($this->categoryKeywords as $keyword => $categoryName) {
            if (str_contains($hint, $keyword)) {
                $matchedName = $categoryName;
                break;
            }
        }

        if (! $matchedName) {
            $matchedName = $type === 'income' ? 'Lainnya' : 'Lainnya';
        }

        // Try to find existing category
        $category = Category::where('name', $matchedName)->where('type', $type)->first();

        // Auto-create if not found
        if (! $category && $matchedName !== 'Lainnya') {
            $category = Category::where('type', $type)->first(); // fallback to any category
        }

        return $category;
    }

    /**
     * Resolve a wallet from a hint (partial match against DB wallet names).
     */
    private function resolveWallet(?string $hint): ?Wallet
    {
        if (! $hint) {
            return null;
        }

        $hint = strtolower($hint);
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            if (str_contains(strtolower($wallet->name), $hint)) {
                return $wallet;
            }
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Telegram API helpers
    // -----------------------------------------------------------------------

    /**
     * Send a text message to a chat. Uses MarkdownV2 parse mode.
     */
    public function sendMessage(int $chatId, string $text): void
    {
        try {
            Http::post("{$this->apiBase}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: failed to send message', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register the webhook URL with Telegram.
     */
    public function setWebhook(string $url): array
    {
        $response = Http::post("{$this->apiBase}/setWebhook", [
            'url'             => $url,
            'allowed_updates' => ['message', 'edited_message'],
        ]);

        return $response->json();
    }

    /**
     * Delete the registered webhook.
     */
    public function deleteWebhook(): array
    {
        $response = Http::post("{$this->apiBase}/deleteWebhook");
        return $response->json();
    }

    /**
     * Get webhook info.
     */
    public function getWebhookInfo(): array
    {
        $response = Http::get("{$this->apiBase}/getWebhookInfo");
        return $response->json();
    }

    // -----------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------

    private function formatRupiah(float|string $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}
