<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppService
{
    public ?string $lastError = null;
    protected $client = null;
    protected $accountSid;
    protected $authToken;
    protected $from;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function __construct()
    {
        $this->accountSid = env('TWILIO_ACCOUNT_SID', env('TWILIO_SID'));
        $this->authToken = env('TWILIO_AUTH_TOKEN');
        $this->from = env('TWILIO_WHATSAPP_FROM');

        // If official Twilio SDK is installed, initialize it
        if ($this->accountSid && $this->authToken && class_exists(\Twilio\Rest\Client::class)) {
            try {
                $this->client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
            } catch (\Throwable $e) {
                Log::warning("Twilio SDK Client init warning: " . $e->getMessage());
            }
        }
    }

    /**
     * Send a free-form WhatsApp message (within 24-hour window only)
     * 
     * @param string $to Phone number
     * @param string $message The message text
     * @param string|null $mediaUrl Optional media URL
     * @return bool Success status
     */
    public function sendMessage($to, $message, $mediaUrl = null): bool
    {
        if (!$this->accountSid || !$this->authToken) {
            Log::warning("Twilio credentials missing. Check TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.");
            return false;
        }

        $to = $this->formatPhoneNumber($to);
        if (!$to) {
            return false;
        }

        $fromFormatted = str_starts_with($this->from, 'whatsapp:') ? $this->from : 'whatsapp:' . $this->from;
        $toFormatted = str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to;

        // Try official SDK if present
        if ($this->client) {
            try {
                $options = [
                    'from' => $fromFormatted,
                    'body' => $message,
                ];
                if ($mediaUrl) {
                    $options['mediaUrl'] = [$mediaUrl];
                }
                $this->client->messages->create($toFormatted, $options);
                Log::info("WhatsApp free-form message sent to: {$toFormatted} via SDK");
                return true;
            } catch (\Throwable $e) {
                Log::warning("Twilio SDK sendMessage failed, falling back to REST HTTP API: " . $e->getMessage());
            }
        }

        // Native HTTP Fallback (Zero external dependencies)
        try {
            $apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
            
            $payload = [
                'From' => $fromFormatted,
                'To'   => $toFormatted,
                'Body' => $message,
            ];

            if ($mediaUrl) {
                $payload['MediaUrl'] = $mediaUrl;
            }

            $response = Http::withoutVerifying()
                ->asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp free-form message sent to: {$toFormatted} via REST API (SID: " . ($response->json('sid') ?? 'N/A') . ")");
                return true;
            }

            $errorBody = $response->json();
            Log::error("Twilio REST API Error: " . ($errorBody['message'] ?? $response->body()));
            return false;
        } catch (\Throwable $e) {
            Log::error("Twilio WhatsApp Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a template-based WhatsApp message (can be sent anytime)
     * 
     * @param string $to Phone number
     * @param string $contentSid Template Content SID from Twilio Console
     * @param array $variables Template variables (e.g., ['1' => 'John', '2' => 'EVT-123'])
     * @return bool Success status
     */
    public function sendTemplateMessage($to, $contentSid, $variables = []): bool
    {
        if (!$this->accountSid || !$this->authToken) {
            Log::warning("Twilio credentials missing. Check TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.");
            return false;
        }

        $to = $this->formatPhoneNumber($to);
        if (!$to) {
            return false;
        }

        $fromFormatted = str_starts_with($this->from, 'whatsapp:') ? $this->from : 'whatsapp:' . $this->from;
        $toFormatted = str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to;
        $variablesJson = json_encode($variables);

        // Try official SDK if present
        if ($this->client) {
            try {
                $this->client->messages->create(
                    $toFormatted,
                    [
                        'from' => $fromFormatted,
                        'contentSid' => $contentSid,
                        'contentVariables' => $variablesJson,
                    ]
                );
                Log::info("WhatsApp template sent to: {$toFormatted} via SDK (Template: {$contentSid})");
                return true;
            } catch (\Throwable $e) {
                Log::warning("Twilio SDK sendTemplateMessage failed, falling back to REST HTTP API: " . $e->getMessage());
            }
        }

        // Native HTTP Fallback (Zero external dependencies)
        try {
            $apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

            $response = Http::withoutVerifying()
                ->asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->post($apiUrl, [
                    'From'             => $fromFormatted,
                    'To'               => $toFormatted,
                    'ContentSid'       => $contentSid,
                    'ContentVariables' => $variablesJson,
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp template sent to: {$toFormatted} via REST API (SID: " . ($response->json('sid') ?? 'N/A') . ")");
                return true;
            }

            $errorBody = $response->json();
            $code = $errorBody['code'] ?? 'N/A';
            $msg = $errorBody['message'] ?? $response->body();
            $this->lastError = "Twilio Error (Code {$code}): {$msg}";
            Log::error("Twilio Template REST Error (Code: {$code}): {$msg}");
            return false;
        } catch (\Throwable $e) {
            $this->lastError = "Exception: " . $e->getMessage();
            Log::error("Twilio WhatsApp Template Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Smart send: automatically chooses between template and free-form based on last contact
     */
    public function sendSmart($to, $message, $contentSid = null, $variables = [], $mediaUrl = null)
    {
        $lastContact = \App\Models\BotCommunication::where('phone', 'like', '%' . preg_replace('/[^0-9]/', '', $to) . '%')
            ->where('direction', 'inbound')
            ->where('created_at', '>=', now()->subHours(24))
            ->first();
        
        if ($lastContact) {
            Log::info("Using free-form message (within 24-hour window)");
            return $this->sendMessage($to, $message, $mediaUrl);
        } else {
            if (!$contentSid) {
                Log::error("No template SID provided and outside 24-hour window. Cannot send.");
                return false;
            }
            
            Log::info("Using template message (outside 24-hour window or first contact)");
            return $this->sendTemplateMessage($to, $contentSid, $variables);
        }
    }

    /**
     * Template 1: payment_completed
     * Hello {username}, Your registration for {event_name} is successful and your registration information is as follows:
     * Full name: {full_name}
     * Email: {email}
     * Registration ID: {registration_id}
     * Payment Status: {status}
     * QR: {q_code}
     */
    public function sendPaymentCompleted(string $to, array $data): bool
    {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('payment_completed')
            ?? config('services.twilio.templates.payment_completed', 'HX807e357191a3eb58e8050ee90e6b74e4');

        $variables = [
            'username'        => $data['username'] ?? $data['full_name'] ?? 'Attendee',
            'event_name'      => $data['event_name'] ?? 'NLCGA Conference 2026',
            'full_name'       => $data['full_name'] ?? $data['username'] ?? 'Attendee',
            'email'           => $data['email'] ?? 'N/A',
            'registration_id' => $data['registration_id'] ?? $data['ticket_code'] ?? '',
            'status'          => $data['status'] ?? 'Confirmed',
            'q_code'          => $data['q_code'] ?? url('/qr/' . ($data['registration_id'] ?? '')),
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    /**
     * Template 2: event_registration_confirmation
     * Hello {{1}}! 🎉
     * Your registration for {{2}} is confirmed!
     * 📋 Details:
     * • Attendee: {{1}}
     * • Event: {{2}}
     * • Ticket Code: {{3}}
     * • Date: {{4}}
     */
    public function sendRegistrationConfirmation(
        string $to,
        string $name,
        string $eventName,
        string $ticketCode,
        string $eventDate = ''
    ): bool {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('event_registration_confirmation')
            ?? \App\Models\WhatsAppTemplate::getContentSid('event_notification')
            ?? config('services.twilio.templates.event_registration_confirmation');

        $variables = [
            '1' => $name,
            '2' => $eventName,
            '3' => $ticketCode,
            '4' => $eventDate,
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    /**
     * Template 3: event_payment_confirmation
     * Hi {{1}}! ✅
     * Your payment for {{2}} has been successfully received!
     * 💳 Payment Summary:
     * • Amount Paid: {{3}}
     * • Transaction Reference: {{4}}
     * • Status: Confirmed
     */
    public function sendPaymentConfirmation(
        string $to,
        string $name,
        string $eventName,
        string $amount,
        string $reference
    ): bool {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('event_payment_confirmation')
            ?? \App\Models\WhatsAppTemplate::getContentSid('account_alert')
            ?? config('services.twilio.templates.event_payment_confirmation');

        $variables = [
            '1' => $name,
            '2' => $eventName,
            '3' => $amount,
            '4' => $reference,
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    /**
     * Template 4: event_reminder
     * Hi {{2}},
     * This is a friendly reminder that {{1}} takes place tomorrow!
     * 📍 Event Information:
     * • Event: {{1}}
     * • Date & Time: {{3}}
     * • Venue: {{4}}
     * • Your Ticket Code: {{5}}
     */
    public function sendEventReminder(
        string $to,
        string $eventName,
        string $name,
        string $eventDate,
        string $venue,
        string $ticketCode
    ): bool {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('event_reminder')
            ?? config('services.twilio.templates.event_reminder');

        $variables = [
            '1' => $eventName,
            '2' => $name,
            '3' => $eventDate,
            '4' => $venue,
            '5' => $ticketCode,
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    public function sendTemplateByName(string $to, string $templateName, array $variables = [])
    {
        $template = \App\Models\WhatsAppTemplate::getByName($templateName);
        
        if (!$template) {
            Log::error("Template not found: {$templateName}");
            return false;
        }

        if (!$template->isActive()) {
            Log::error("Template is inactive: {$templateName}");
            return false;
        }

        return $this->sendTemplateMessage($to, $template->content_sid, $variables);
    }

    private function formatPhoneNumber($number)
    {
        $number = preg_replace('/[^0-9+]/', '', (string) $number);

        // If Nigerian number without country code
        if (str_starts_with($number, '0') && strlen($number) === 11) {
            return '+234' . substr($number, 1);
        }

        if (!str_starts_with($number, '+')) {
            return '+' . $number;
        }

        return $number;
    }
}
