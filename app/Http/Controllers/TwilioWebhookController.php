<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp messages from Twilio
     */
    public function handle(Request $request, WhatsAppBotService $botService): Response
    {
        try {
            $from = $request->input('From'); 
            $body = $request->input('Body'); 

            Log::info("Twilio webhook received", [
                'from' => $from,
                'body' => $body,
            ]);

            $phone = str_replace('whatsapp:', '', $from);
            $phone = str_replace('+', '', $phone); 

            if ($body && $phone) {
                $botService->handleIncomingMessage($phone, $body);
            }

            return response('', 200);

        } catch (\Exception $e) {
            Log::error("Twilio webhook error: " . $e->getMessage());
            return response('', 200); 
        }
    }
}
