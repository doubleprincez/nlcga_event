<?php
namespace App\Services;

use App\Models\ConferenceMember;
use App\Models\BotCommunication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppBotService
{
    public function handleIncomingMessage(string $phone, string $messageBody): void
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $trimmedMessage = trim($messageBody);
        $upperMessage = strtoupper($trimmedMessage);

        // Log inbound message
        BotCommunication::create([
            'phone' => $cleanPhone,
            'message' => $trimmedMessage,
            'direction' => 'inbound',
        ]);

        // 1. Explicit Keyword Direct Matches
        if (in_array($upperMessage, ['VENUE', 'LOCATION', 'WHERE'])) {
            $reply = "📍 *Event Location*\n\nThe Summit Center, Plot 12 Victoria Island, Lagos.\nMap Link: https://maps.google.com/?q=Summit+Center+Lagos";
        } elseif (in_array($upperMessage, ['AGENDA', 'SCHEDULE', 'TIMETABLE', 'PROGRAM'])) {
            $reply = "📅 *Event Schedule*\n\n• 08:00 AM - Accreditation & Gate Scanning\n• 09:30 AM - Keynote Address\n• 01:00 PM - Lunch & Networking\n• 04:00 PM - Closing Ceremony";
        } elseif (in_array($upperMessage, ['TICKET', 'MY TICKET', 'PASS', 'CODE'])) {
            $reply = $this->getTicketByPhone($cleanPhone);
        } elseif (Str::startsWith($upperMessage, 'EVT-') || Str::contains($upperMessage, 'BOT_INFO_')) {
            $code = Str::after($upperMessage, 'BOT_INFO_');
            if (!Str::startsWith($code, 'EVT-')) {
                $code = $upperMessage;
            }
            $reply = $this->getTicketByCode($code);
        } else {
            // 2. Dynamic Fallback to Gemini / OpenAI
            $reply = $this->queryAiAgent($trimmedMessage, $cleanPhone);
        }

        $this->sendWhatsAppReply($cleanPhone, $reply);
    }

    protected function getTicketByPhone(string $phone): string
    {
        $member = ConferenceMember::where('phoneNumber', 'like', "%{$phone}%")->first();

        if (!$member) {
            return "⚠️ No ticket found associated with phone number *{$phone}*.\n\nPlease complete registration at https://event.nlcga.com";
        }

        $status = $member->is_checked_in
            ? "✅ Checked In at " . ($member->checked_in_at ? $member->checked_in_at->format('h:i A') : 'Unknown')
            : "⏳ Valid for Gate Entry";

        return "🎟️ *Your Event Ticket*\n\nName: *{$member->fullName}*\nTicket Code: *{$member->unique_code}*\nStatus: {$status}";
    }

    protected function getTicketByCode(string $code): string
    {
        $member = ConferenceMember::where('unique_code', strtoupper($code))->first();

        if (!$member) {
            return "❌ Ticket code *{$code}* was not found in our database.";
        }

        return "🎟️ *Ticket Information*\n\nAttendee: *{$member->fullName}*\nCode: *{$member->unique_code}*\nStatus: " . ($member->is_checked_in ? "Checked In" : "Pending Entry");
    }

    protected function queryAiAgent(string $userPrompt, string $phone): string
    {
        $geminiKey = env('GEMINI_API_KEY');
        $openAiKey = env('OPENAI_API_KEY');

        $defaultPrompt = 'You are an AI assistant for NLCGA Event 2026 at Summit Center, Lagos. Keep answers under 3 sentences. The NLCGA Conference & Exhibition brings together key stakeholders, policymakers, investors, and experts in Nigeria\'s gas sector. If asked for tickets, tell users to type TICKET.';
        $systemPrompt = Storage::disk('local')->exists('ai_training.json') 
            ? json_decode(Storage::disk('local')->get('ai_training.json'), true)['prompt'] ?? $defaultPrompt 
            : $defaultPrompt;

        // 1. Try Google Gemini
        if ($geminiKey) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$geminiKey}", [
                        'system_instruction' => [
                            'parts' => [
                                ['text' => $systemPrompt]
                            ]
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $userPrompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'maxOutputTokens' => 250
                        ]
                    ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($text)) {
                        return trim($text);
                    }
                } else {
                    Log::error("Gemini API Error: " . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error("Gemini API Exception: " . $e->getMessage());
            }
        }

        // 2. Fallback to OpenAI if configured
        if ($openAiKey) {
            try {
                $response = Http::withToken($openAiKey)
                    ->timeout(8)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'max_tokens' => 150,
                    ]);

                if ($response->successful()) {
                    return trim($response->json('choices.0.message.content'));
                }
            } catch (\Throwable $e) {
                Log::error("OpenAI Webhook Bot Error: " . $e->getMessage());
            }
        }

        return "Hello! Type *TICKET* for your pass, *VENUE* for location, or *AGENDA* for the schedule.";
    }

    protected function sendWhatsAppReply(string $phone, string $text): void
    {
        // Log outbound message
        BotCommunication::create([
            'phone' => $phone,
            'message' => $text,
            'direction' => 'outbound',
        ]);

        $service = new TwilioWhatsAppService();
        $service->sendMessage($phone, $text);
    }
}
