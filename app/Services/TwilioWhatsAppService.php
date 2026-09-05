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

    public function sendEventNotification(string $to, array $data)
    {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('event_notification')
            ?? config('services.twilio.templates.event_notification');
        
        if (!$contentSid) {
            Log::error("Event notification template not configured");
            return false;
        }

        $variables = [
            '1' => $data['attendee_name'] ?? $data['name'] ?? 'Guest',
            '2' => $data['event_name'] ?? 'Event',
            '3' => $data['ticket_code'] ?? '',
            '4' => $data['event_date'] ?? '',
            '5' => $data['venue'] ?? '',
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    public function sendAccountAlert(string $to, array $data)
    {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('account_alert')
            ?? config('services.twilio.templates.account_alert');
        
        if (!$contentSid) {
            Log::error("Account alert template not configured");
            return false;
        }

        $variables = [
            '1' => $data['user_name'] ?? 'User',
            '2' => $data['alert_type'] ?? 'Alert',
            '3' => $data['message'] ?? '',
            '4' => $data['action_required'] ?? '',
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

    public function sendRegistrationConfirmation(
        string $to,
        string $name,
        string $eventName,
        string $ticketCode,
        string $eventDate = '',
        string $venue = ''
    ) {
        return $this->sendEventNotification($to, [
            'attendee_name' => $name,
            'event_name' => $eventName,
            'ticket_code' => $ticketCode,
            'event_date' => $eventDate,
            'venue' => $venue,
        ]);
    }

    public function sendPaymentConfirmation(
        string $to,
        string $name,
        string $amount,
        string $reference
    ) {
        return $this->sendAccountAlert($to, [
            'user_name' => $name,
            'alert_type' => 'Payment Received',
            'message' => "Your payment of {$amount} has been confirmed.",
            'action_required' => "Reference: {$reference}",
        ]);
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
