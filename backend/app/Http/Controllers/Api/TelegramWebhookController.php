<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private TelegramBotService $bot) {}

    /**
     * Handle an incoming Telegram webhook POST request.
     */
    public function handle(Request $request): Response
    {
        // Security: verify the request is from the allowed chat ID
        $allowedChatId = config('services.telegram.allowed_chat_id');

        $update  = $request->all();
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if ($message && $allowedChatId) {
            $incomingChatId = (string) ($message['chat']['id'] ?? '');
            if ($incomingChatId !== (string) $allowedChatId) {
                Log::warning('TelegramWebhook: unauthorized chat_id', ['chat_id' => $incomingChatId]);
                // Return 200 to prevent Telegram from retrying
                return response('Unauthorized', 200);
            }
        }

        try {
            $this->bot->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error('TelegramWebhook: unhandled exception', [
                'error'  => $e->getMessage(),
                'update' => $update,
            ]);
        }

        // Always return 200 — Telegram will retry on non-200
        return response('OK', 200);
    }
}
