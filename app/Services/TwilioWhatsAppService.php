<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $this->from = env('TWILIO_WHATSAPP_FROM'); // Fixed: Use correct env variable name

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    /**
     * Send a free-form WhatsApp message (within 24-hour window only)
     * Use this when:
     * - User messaged you first
     * - You're replying within 24 hours of their last message
     * - For customer support/bot replies
     * 
     * @param string $to Phone number (with country code)
     * @param string $message The message text
     * @param string|null $mediaUrl Optional media URL
     * @return bool Success status
     */
    public function sendMessage($to, $message, $mediaUrl = null)
    {
        if (!$this->client) {
            Log::warning("Twilio client not initialized. Check TWILIO_SID and TWILIO_AUTH_TOKEN.");
            return false;
        }

        // Format phone number to E.164
        $to = $this->formatPhoneNumber($to);
        if (!$to) {
            return false;
        }

        $options = [
            'from' => 'whatsapp:' . $this->from,
            'body' => $message,
        ];

        if ($mediaUrl) {
            $options['mediaUrl'] = [$mediaUrl];
        }

        try {
            $this->client->messages->create(
                'whatsapp:' . $to,
                $options
            );
            Log::info("WhatsApp free-form message sent to: {$to}");
            return true;
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp Error: " . $e->getMessage());
            
            // Check if it's a 24-hour window error
            if (strpos($e->getMessage(), '63016') !== false) {
                Log::warning("Error 63016: Outside 24-hour window. Use sendTemplateMessage() instead.");
            }
            
            return false;
        }
    }

    /**
     * Send a template-based WhatsApp message (can be sent anytime)
     * Use this when:
     * - Sending first message to a user who never contacted you
     * - More than 24 hours since user's last message
     * - Sending proactive notifications (appointment reminders, confirmations)
     * 
     * Template must be pre-approved in Twilio Console!
     * 
     * @param string $to Phone number (with country code)
     * @param string $contentSid Template Content SID from Twilio Console
     * @param array $variables Template variables (e.g., ['1' => 'John', '2' => 'EVT-123'])
     * @return bool Success status
     */
    public function sendTemplateMessage($to, $contentSid, $variables = [])
    {
        if (!$this->client) {
            Log::warning("Twilio client not initialized. Check TWILIO_SID and TWILIO_AUTH_TOKEN.");
            return false;
        }

        $to = $this->formatPhoneNumber($to);
        if (!$to) {
            return false;
        }

        try {
            $this->client->messages->create(
                'whatsapp:' . $to,
                [
                    'from' => 'whatsapp:' . $this->from,
                    'contentSid' => $contentSid,
                    'contentVariables' => json_encode($variables),
                ]
            );
            Log::info("WhatsApp template message sent to: {$to} (Template: {$contentSid})");
            return true;
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp Template Error: " . $e->getMessage());
            
            // Check if template is not approved
            if (strpos($e->getMessage(), 'not approved') !== false) {
                Log::warning("Template {$contentSid} is not approved. Check Twilio Console for template status.");
            }
            
            return false;
        }
    }
    
    /**
     * Smart send: automatically chooses between template and free-form based on last contact
     * 
     * @param string $to Phone number
     * @param string $message Message text (for free-form)
     * @param string|null $contentSid Template SID (for template)
     * @param array $variables Template variables
     * @param string|null $mediaUrl Optional media URL
     * @return bool Success status
     */
    public function sendSmart($to, $message, $contentSid = null, $variables = [], $mediaUrl = null)
    {
        // Check if user has contacted us in the last 24 hours
        $lastContact = \App\Models\BotCommunication::where('phone', 'like', '%' . preg_replace('/[^0-9]/', '', $to) . '%')
            ->where('direction', 'inbound')
            ->where('created_at', '>=', now()->subHours(24))
            ->first();
        
        if ($lastContact) {
            // Within 24-hour window - use free-form
            Log::info("Using free-form message (within 24-hour window)");
            return $this->sendMessage($to, $message, $mediaUrl);
        } else {
            // Outside window or no contact - use template
            if (!$contentSid) {
                Log::error("No template SID provided and outside 24-hour window. Cannot send.");
                return false;
            }
            
            Log::info("Using template message (outside 24-hour window or first contact)");
            return $this->sendTemplateMessage($to, $contentSid, $variables);
        }
    }

    /**
     * Send Event Notification template
     * Now uses database configuration
     * 
     * @param string $to Phone number
     * @param array $data Event data ['name' => '...', 'date' => '...', 'venue' => '...', etc]
     * @return bool Success status
     */
    public function sendEventNotification(string $to, array $data)
    {
        // Get from database first, fallback to config
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('event_notification')
            ?? config('services.twilio.templates.event_notification');
        
        if (!$contentSid) {
            Log::error("Event notification template not configured");
            return false;
        }

        // Map your data to template variables
        // Adjust variable numbers based on your actual template structure
        $variables = [
            '1' => $data['attendee_name'] ?? $data['name'] ?? 'Guest',
            '2' => $data['event_name'] ?? 'Event',
            '3' => $data['ticket_code'] ?? '',
            '4' => $data['event_date'] ?? '',
            '5' => $data['venue'] ?? '',
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    /**
     * Send Account Alert template
     * Now uses database configuration
     * 
     * @param string $to Phone number
     * @param array $data Alert data ['type' => '...', 'message' => '...', etc]
     * @return bool Success status
     */
    public function sendAccountAlert(string $to, array $data)
    {
        // Get from database first, fallback to config
        $contentSid = \App\Models\WhatsAppTemplate::getContentSid('account_alert')
            ?? config('services.twilio.templates.account_alert');
        
        if (!$contentSid) {
            Log::error("Account alert template not configured");
            return false;
        }

        // Map your data to template variables
        $variables = [
            '1' => $data['user_name'] ?? 'User',
            '2' => $data['alert_type'] ?? 'Alert',
            '3' => $data['message'] ?? '',
            '4' => $data['action_required'] ?? '',
        ];

        return $this->sendTemplateMessage($to, $contentSid, $variables);
    }

    /**
     * Send template by name (from database)
     * 
     * @param string $to Phone number
     * @param string $templateName Template name from database
     * @param array $variables Template variables
     * @return bool Success status
     */
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

    /**
     * Send Registration Confirmation using Event Notification template
     * 
     * @param string $to Phone number
     * @param string $name Attendee name
     * @param string $eventName Event name
     * @param string $ticketCode Ticket code
     * @param string $eventDate Event date
     * @param string $venue Venue location
     * @return bool Success status
     */
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

    /**
     * Send Payment Confirmation using Account Alert template
     * 
     * @param string $to Phone number
     * @param string $name Customer name
     * @param string $amount Payment amount
     * @param string $reference Payment reference
     * @return bool Success status
     */
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
        $number = preg_replace('/[^0-9+]/', '', $number);

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
