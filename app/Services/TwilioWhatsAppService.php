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

    /**
     * Inspect Content Template directly from Twilio Content API
     */
    public function fetchContentDetails(string $contentSid): ?array
    {
        if (!$this->accountSid || !$this->authToken) {
            return null;
        }

        try {
            $apiUrl = "https://content.twilio.com/v1/Content/{$contentSid}";
            $res = Http::withoutVerifying()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->get($apiUrl);

            if ($res->successful()) {
                return $res->json();
            }

            Log::warning("Failed to fetch Twilio content details: " . $res->body());
            return null;
        } catch (\Throwable $e) {
            Log::error("Twilio fetchContentDetails error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch WhatsApp approval status for Content SID
     */
    public function fetchApprovalStatus(string $contentSid): ?array
    {
        if (!$this->accountSid || !$this->authToken) {
            return null;
        }

        try {
            $apiUrl = "https://content.twilio.com/v1/Content/{$contentSid}/ApprovalRequests";
            $res = Http::withoutVerifying()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->get($apiUrl);

            if ($res->successful()) {
                return $res->json();
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid', env('TWILIO_ACCOUNT_SID', env('TWILIO_SID')));
        $this->authToken  = config('services.twilio.token', env('TWILIO_AUTH_TOKEN'));
        $this->apiKey     = config('services.twilio.api_key', env('TWILIO_API_KEY'));
        $this->apiSecret  = config('services.twilio.api_secret', env('TWILIO_API_SECRET'));
        $this->from       = config('services.twilio.whatsapp_from', env('TWILIO_WHATSAPP_FROM', '+14155238886'));

        // Initialize Twilio SDK Client if class exists
        if (class_exists(\Twilio\Rest\Client::class)) {
            try {
                if ($this->apiKey && $this->apiSecret && $this->accountSid) {
                    $this->client = new \Twilio\Rest\Client($this->apiKey, $this->apiSecret, $this->accountSid);
                } elseif ($this->accountSid && $this->authToken) {
                    $this->client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
                }
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
     * Dynamically conform variables to the exact schema defined on Twilio Content API
     */
    public function adaptVariablesForTemplate(string $contentSid, array $variables): array
    {
        try {
            $expectedVars = \Illuminate\Support\Facades\Cache::remember("twilio_template_vars_{$contentSid}", 3600, function () use ($contentSid) {
                $details = $this->fetchContentDetails($contentSid);
                return isset($details['variables']) && is_array($details['variables']) ? array_keys($details['variables']) : null;
            });

            if (!empty($expectedVars)) {
                $mapped = [];
                foreach ($expectedVars as $expectedKey) {
                    $strKey = (string) $expectedKey;
                    if (array_key_exists($strKey, $variables)) {
                        $mapped[$strKey] = $variables[$strKey];
                    } elseif ($strKey === '1' && isset($variables['username'])) {
                        $mapped[$strKey] = $variables['username'];
                    } elseif ($strKey === '1' && isset($variables['full_name'])) {
                        $mapped[$strKey] = $variables['full_name'];
                    } elseif ($strKey === '2' && isset($variables['event_name'])) {
                        $mapped[$strKey] = $variables['event_name'];
                    } elseif ($strKey === '3' && isset($variables['full_name'])) {
                        $mapped[$strKey] = $variables['full_name'];
                    } elseif ($strKey === '3' && isset($variables['ticket_code'])) {
                        $mapped[$strKey] = $variables['ticket_code'];
                    } elseif ($strKey === '4' && isset($variables['email'])) {
                        $mapped[$strKey] = $variables['email'];
                    } elseif ($strKey === '4' && isset($variables['date'])) {
                        $mapped[$strKey] = $variables['date'];
                    } elseif ($strKey === '5' && isset($variables['registration_id'])) {
                        $mapped[$strKey] = $variables['registration_id'];
                    } elseif ($strKey === '6') {
                        // If template has 6 variables, 6 is the media QR code filename
                        if (count($expectedVars) === 6) {
                            $mapped[$strKey] = ($variables['registration_id'] ?? $variables['ticket_code'] ?? '') ? ($variables['registration_id'] ?? $variables['ticket_code']) . '.png' : ($variables['6'] ?? 'ticket.png');
                        } else {
                            $mapped[$strKey] = $variables['status'] ?? $variables['6'] ?? 'Confirmed';
                        }
                    } elseif ($strKey === '7') {
                        $mapped[$strKey] = $variables['7'] ?? $variables['q_code_url'] ?? $variables['q_code'] ?? url('/qr/' . ($variables['registration_id'] ?? $variables['ticket_code'] ?? 'EVT'));
                    } elseif ($strKey === '8') {
                        $mapped[$strKey] = $variables['8'] ?? ($variables['registration_id'] ?? $variables['ticket_code'] ?? 'EVT') . '.png';
                    } elseif ($strKey === 'q_code' && isset($variables['registration_id'])) {
                        $mapped[$strKey] = $variables['registration_id'] . '.png';
                    } else {
                        $mapped[$strKey] = $variables[$strKey] ?? 'N/A';
                    }
                }
                return $mapped;
            }
        } catch (\Throwable $e) {
            Log::warning("Could not auto-adapt variables for {$contentSid}: " . $e->getMessage());
        }

        return $variables;
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

        // Auto-adapt variables according to Twilio template schema definition
        $adaptedVariables = $this->adaptVariablesForTemplate($contentSid, $variables);

        // Clean & sanitize variables according to Twilio Content API rules
        // (No newlines, no tabs, string-casted values, no null/empty strings)
        $cleanVariables = [];
        foreach ($adaptedVariables as $k => $v) {
            $strVal = trim(preg_replace('/[\r\n\t]+/', ' ', (string) $v));
            $cleanVariables[(string) $k] = ($strVal === '') ? 'N/A' : $strVal;
        }

        $variablesJson = json_encode($cleanVariables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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

            // Check if failure is due to unapproved / rejected WhatsApp template state
            $approvalInfo = $this->fetchApprovalStatus($contentSid);
            $whatsappStatus = $approvalInfo['whatsapp']['status'] ?? 'unknown';
            $rejectionReason = $approvalInfo['whatsapp']['rejection_reason'] ?? '';

            $extraDetail = "";
            if ($whatsappStatus !== 'approved') {
                $extraDetail = "\n\n📋 Twilio Template Approval Status: '{$whatsappStatus}'";
                if ($whatsappStatus === 'received') {
                    $extraDetail .= " (Pending Meta review. WhatsApp strictly rejects dispatching templates until Meta marks them 'approved').";
                } elseif ($whatsappStatus === 'rejected') {
                    $extraDetail .= " (Meta rejected this template. Reason: {$rejectionReason}).";
                }
            }

            $this->lastError = "Twilio Error (Code {$code}): {$msg}{$extraDetail}";
            Log::error("Twilio Template REST Error (Code: {$code}): {$msg}{$extraDetail}");
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
     * Template 1: payment_completed (8-variable format with media QR card)
     * Hello {{1}}, Your registration for {{2}} is successful and your registration information is as follows:
     * Full name: {{3}}
     * Email: {{4}}
     * Registration ID: {{5}}
     * Payment Status: {{6}}
     * QR: {{7}}
     * 
     * Thank you for registering. See you at the conference
     * Media: https://api-v2.nlcga.com/qr/{{8}}
     */
    public function sendPaymentCompleted(string $to, array $data): bool
    {
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('payment_completed')
            ?? config('services.twilio.templates.payment_completed', 'HX05f973a590f932ea4078b09c5b54c56b');

        $regId = $data['registration_id'] ?? $data['ticket_code'] ?? '';
        $qrUrl = $data['q_code_url'] ?? $data['q_code'] ?? url('/qr/' . $regId);
        $qrFilename = $data['qr_filename'] ?? ($regId ? $regId . '.png' : 'ticket.png');

        $variables = [
            '1'               => $data['username'] ?? $data['full_name'] ?? 'Attendee',
            '2'               => $data['event_name'] ?? 'NLCGA Conference 2026',
            '3'               => $data['full_name'] ?? $data['username'] ?? 'Attendee',
            '4'               => $data['email'] ?? 'N/A',
            '5'               => $regId,
            '6'               => $data['status'] ?? 'Confirmed',
            '7'               => $qrUrl,
            '8'               => $qrFilename,
            'username'        => $data['username'] ?? $data['full_name'] ?? 'Attendee',
            'event_name'      => $data['event_name'] ?? 'NLCGA Conference 2026',
            'full_name'       => $data['full_name'] ?? $data['username'] ?? 'Attendee',
            'email'           => $data['email'] ?? 'N/A',
            'registration_id' => $regId,
            'status'          => $data['status'] ?? 'Confirmed',
            'q_code'          => $qrUrl,
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
            ?? config('services.twilio.templates.event_registration_confirmation', 'HX68e84d59fe2f55fc1580278239f61d04');

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
            ?? config('services.twilio.templates.event_payment_confirmation', 'HXb9b059eff7fa715d5f93418d2d9e0d5a');

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
            ?? config('services.twilio.templates.event_reminder', 'HXb8a69c466d72d6bfac44dc932a4b7fe8');

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
