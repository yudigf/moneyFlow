<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
                            {url? : Public HTTPS URL for the webhook (e.g. https://xxxx.ngrok.io/api/telegram/webhook)}
                            {--delete : Delete the currently registered webhook}
                            {--info : Show current webhook info}';

    protected $description = 'Register, delete, or inspect the Telegram bot webhook URL';

    public function __construct(private TelegramBotService $bot)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('info')) {
            $info = $this->bot->getWebhookInfo();
            $this->info('Current webhook info:');
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        if ($this->option('delete')) {
            $result = $this->bot->deleteWebhook();
            if ($result['ok'] ?? false) {
                $this->info('✅ Webhook deleted successfully.');
            } else {
                $this->error('❌ Failed to delete webhook: ' . ($result['description'] ?? 'Unknown error'));
            }
            return self::SUCCESS;
        }

        $url = $this->argument('url');

        if (! $url) {
            $this->error('Please provide the webhook URL.');
            $this->line('Usage: php artisan telegram:set-webhook https://your-domain.com/api/telegram/webhook');
            $this->line('');
            $this->line('For local dev with ngrok:');
            $this->line('  ngrok http 8000');
            $this->line('  php artisan telegram:set-webhook https://xxxx.ngrok.io/api/telegram/webhook');
            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->error('❌ Webhook URL must use HTTPS.');
            return self::FAILURE;
        }

        $this->info("Registering webhook: {$url}");
        $result = $this->bot->setWebhook($url);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook registered successfully!');
            $this->line('');
            $this->line('Next steps:');
            $this->line('  1. Open Telegram and find your bot');
            $this->line('  2. Send /start to begin');
            $this->line('  3. Try: beli makan 25rb');
        } else {
            $this->error('❌ Failed to register webhook: ' . ($result['description'] ?? 'Unknown error'));
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
