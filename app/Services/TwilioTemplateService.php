<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing WhatsApp templates via Twilio Content API
 * Create, approve, and manage WhatsApp message templates
 */
class TwilioTemplateService
{
    protected $client;
    protected $accountSid;

    public function __construct()
    {
        $this->accountSid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        if ($this->accountSid && $token) {
            $this->client = new Client($this->accountSid, $token);
        }
    }

    /**
     * Create a new WhatsApp template
     * 
     * @param string $friendlyName Internal name for the template
     * @param string $body Template body with {{1}}, {{2}} placeholders
     * @param array $variables Default variable names (e.g., ['1' => 'name', '2' => 'event'])
     * @param string $language Language code (e.g., 'en', 'es')
     * @return array|false ['sid' => 'HXxxx...', 'status' => 'created'] or false
     */
    public function createTemplate(
        string $friendlyName,
        string $body,
        array $variables = [],
        string $language = 'en'
    ) {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return false;
        }

        try {
            // Create template using Content API
            $content = $this->client->content->v1->contents->create([
                'friendly_name' => $friendlyName,
                'language' => $language,
                'variables' => $variables,
                'types' => [
                    'twilio/text' => [
                        'body' => $body
                    ]
                ]
            ]);

            Log::info("WhatsApp template created", [
                'sid' => $content->sid,
                'name' => $friendlyName
            ]);

            return [
                'sid' => $content->sid,
                'friendly_name' => $content->friendlyName,
                'language' => $content->language,
                'status' => 'created',
                'date_created' => $content->dateCreated->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            Log::error("Failed to create template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a WhatsApp template with quick reply buttons
     * 
     * @param string $friendlyName Internal name
     * @param string $body Template body
     * @param array $buttons Array of buttons [['title' => 'Button 1', 'id' => 'btn1'], ...]
     * @param array $variables Default variables
     * @param string $language Language code
     * @return array|false Template info or false
     */
    public function createQuickReplyTemplate(
        string $friendlyName,
        string $body,
        array $buttons = [],
        array $variables = [],
        string $language = 'en'
    ) {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return false;
        }

        try {
            $content = $this->client->content->v1->contents->create([
                'friendly_name' => $friendlyName,
                'language' => $language,
                'variables' => $variables,
                'types' => [
                    'twilio/text' => [
                        'body' => $body
                    ],
                    'twilio/quick-reply' => [
                        'body' => $body,
                        'actions' => $buttons
                    ]
                ]
            ]);

            Log::info("WhatsApp quick reply template created", [
                'sid' => $content->sid,
                'name' => $friendlyName
            ]);

            return [
                'sid' => $content->sid,
                'friendly_name' => $content->friendlyName,
                'language' => $content->language,
                'status' => 'created',
                'date_created' => $content->dateCreated->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            Log::error("Failed to create quick reply template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Submit a template for WhatsApp approval
     * Required before you can use the template to send messages
     * 
     * @param string $contentSid Template SID (HXxxx...)
     * @param string $templateName Unique name (lowercase, alphanumeric, underscores only)
     * @param string $category UTILITY, MARKETING, or AUTHENTICATION
     * @return array|false Approval status or false
     */
    public function submitForApproval(
        string $contentSid,
        string $templateName,
        string $category = 'UTILITY'
    ) {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return false;
        }

        // Validate template name (WhatsApp requirement)
        if (!preg_match('/^[a-z0-9_]+$/', $templateName)) {
            Log::error("Invalid template name. Use only lowercase letters, numbers, and underscores.");
            return false;
        }

        // Validate category
        $validCategories = ['UTILITY', 'MARKETING', 'AUTHENTICATION'];
        if (!in_array(strtoupper($category), $validCategories)) {
            Log::error("Invalid category. Must be UTILITY, MARKETING, or AUTHENTICATION.");
            return false;
        }

        try {
            $approval = $this->client->content->v1
                ->contents($contentSid)
                ->approvalCreate('whatsapp', [
                    'name' => strtolower($templateName),
                    'category' => strtoupper($category)
                ]);

            Log::info("Template submitted for WhatsApp approval", [
                'content_sid' => $contentSid,
                'name' => $templateName,
                'status' => $approval->status ?? 'submitted'
            ]);

            return [
                'content_sid' => $contentSid,
                'name' => $templateName,
                'category' => $category,
                'status' => $approval->status ?? 'pending',
                'submitted_at' => now()->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            Log::error("Failed to submit template for approval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check template approval status
     * 
     * @param string $contentSid Template SID
     * @return array|false Status info or false
     */
    public function getApprovalStatus(string $contentSid)
    {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return false;
        }

        try {
            $approval = $this->client->content->v1
                ->contents($contentSid)
                ->approvalFetch()
                ->fetch();

            $whatsappStatus = $approval->whatsapp ?? null;

            if ($whatsappStatus) {
                return [
                    'content_sid' => $contentSid,
                    'name' => $whatsappStatus['name'] ?? null,
                    'status' => $whatsappStatus['status'] ?? 'unknown',
                    'category' => $whatsappStatus['category'] ?? null,
                    'rejection_reason' => $whatsappStatus['rejection_reason'] ?? null,
                ];
            }

            return [
                'content_sid' => $contentSid,
                'status' => 'not_submitted',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to fetch approval status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * List all templates
     * 
     * @param int $limit Number of templates to retrieve
     * @return array List of templates
     */
    public function listTemplates(int $limit = 100)
    {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return [];
        }

        try {
            $contents = $this->client->content->v1->contents->read(['limit' => $limit]);

            $templates = [];
            foreach ($contents as $content) {
                $templates[] = [
                    'sid' => $content->sid,
                    'friendly_name' => $content->friendlyName,
                    'language' => $content->language,
                    'date_created' => $content->dateCreated->format('Y-m-d H:i:s'),
                ];
            }

            return $templates;
        } catch (\Exception $e) {
            Log::error("Failed to list templates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * List templates with their approval status
     * 
     * @param int $limit Number of templates to retrieve
     * @return array List of templates with approval status
     */
    public function listTemplatesWithApproval(int $limit = 100)
    {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return [];
        }

        try {
            $contents = $this->client->content->v1->contentAndApprovals->read(['limit' => $limit]);

            $templates = [];
            foreach ($contents as $content) {
                $whatsappApproval = $content->approval['whatsapp'] ?? null;

                $templates[] = [
                    'sid' => $content->sid,
                    'friendly_name' => $content->friendlyName,
                    'language' => $content->language,
                    'date_created' => $content->dateCreated->format('Y-m-d H:i:s'),
                    'whatsapp_status' => $whatsappApproval['status'] ?? 'not_submitted',
                    'whatsapp_name' => $whatsappApproval['name'] ?? null,
                    'category' => $whatsappApproval['category'] ?? null,
                ];
            }

            return $templates;
        } catch (\Exception $e) {
            Log::error("Failed to list templates with approval: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a template
     * 
     * @param string $contentSid Template SID
     * @param bool $deleteFromWhatsApp Also delete from WhatsApp (if approved there)
     * @return bool Success status
     */
    public function deleteTemplate(string $contentSid, bool $deleteFromWhatsApp = false)
    {
        if (!$this->client) {
            Log::error("Twilio client not initialized");
            return false;
        }

        try {
            $this->client->content->v1
                ->contents($contentSid)
                ->delete(['deleteInWaba' => $deleteFromWhatsApp]);

            Log::info("Template deleted", [
                'content_sid' => $contentSid,
                'deleted_from_whatsapp' => $deleteFromWhatsApp
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Create a complete registration confirmation template
     * This creates the template and submits it for approval in one go
     * 
     * @return array|false Template info with approval status
     */
    public function createRegistrationTemplate()
    {
        // Step 1: Create the template
        $template = $this->createTemplate(
            'event_registration_confirmation',
            "Hello {{1}}! 🎉\n\nYour registration for {{2}} is confirmed!\n\n📋 Details:\n• Name: {{1}}\n• Event: {{2}}\n• Ticket Code: {{3}}\n• Date: {{4}}\n\nPlease keep this code safe. You'll need it at the entrance.\n\nSee you there!",
            [
                '1' => 'attendee_name',
                '2' => 'event_name',
                '3' => 'ticket_code',
                '4' => 'event_date'
            ],
            'en'
        );

        if (!$template) {
            return false;
        }

        // Step 2: Submit for WhatsApp approval
        $approval = $this->submitForApproval(
            $template['sid'],
            'event_registration_confirmation',
            'UTILITY'
        );

        if (!$approval) {
            return $template; // Return template even if approval submission failed
        }

        return array_merge($template, [
            'approval_status' => $approval['status'],
            'approval_name' => $approval['name']
        ]);
    }

    /**
     * Helper: Create a payment confirmation template
     * 
     * @return array|false Template info with approval status
     */
    public function createPaymentTemplate()
    {
        $template = $this->createTemplate(
            'event_payment_confirmation',
            "Hi {{1}}! ✅\n\nYour payment for {{2}} has been received!\n\n💳 Payment Details:\n• Amount: {{3}}\n• Reference: {{4}}\n• Status: Confirmed\n\nYour ticket will be sent shortly.\n\nThank you!",
            [
                '1' => 'customer_name',
                '2' => 'event_name',
                '3' => 'amount',
                '4' => 'reference'
            ],
            'en'
        );

        if (!$template) {
            return false;
        }

        $approval = $this->submitForApproval(
            $template['sid'],
            'event_payment_confirmation',
            'UTILITY'
        );

        if (!$approval) {
            return $template;
        }

        return array_merge($template, [
            'approval_status' => $approval['status'],
            'approval_name' => $approval['name']
        ]);
    }

    /**
     * Helper: Create an event reminder template
     * 
     * @return array|false Template info with approval status
     */
    public function createReminderTemplate()
    {
        $template = $this->createTemplate(
            'event_reminder',
            "⏰ Reminder: {{1}} is tomorrow!\n\nHi {{2}},\n\nJust a friendly reminder that {{1}} is happening tomorrow!\n\n📍 Details:\n• Event: {{1}}\n• Date: {{3}}\n• Venue: {{4}}\n• Your Ticket: {{5}}\n\nDon't forget to bring your ticket code!\n\nSee you tomorrow! 🎉",
            [
                '1' => 'event_name',
                '2' => 'attendee_name',
                '3' => 'event_date',
                '4' => 'venue',
                '5' => 'ticket_code'
            ],
            'en'
        );

        if (!$template) {
            return false;
        }

        $approval = $this->submitForApproval(
            $template['sid'],
            'event_reminder',
            'UTILITY'
        );

        if (!$approval) {
            return $template;
        }

        return array_merge($template, [
            'approval_status' => $approval['status'],
            'approval_name' => $approval['name']
        ]);
    }
}
