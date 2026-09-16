<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\WhatsAppTemplate;
use App\Models\ConferenceMember;
use App\Models\BotCommunication;
use App\Services\TwilioWhatsAppService;
use Illuminate\Support\Facades\Artisan;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $phone = '';
    public string $selectedTemplate = 'payment_completed';
    public string $customContentSid = '';
    public string $messageType = 'template'; // template or freeform
    public string $variableFormat = 'numbered'; // numbered (Twilio standard) or named
    public string $freeformMessage = 'Hello! This is a test message from NLCGA Event Assistant.';

    // Template variables
    public string $attendeeName = 'Lanre Bayewu';
    public string $eventName = 'NLCGA Conference 2026';
    public string $email = 'lanre@example.com';
    public string $ticketCode = 'EVT-TEST01';
    public string $amount = '50,000';
    public string $status = 'Confirmed';
    public string $venue = 'Summit Center, Victoria Island, Lagos';
    public string $eventDate = 'October 24, 2026';

    public ?string $resultMessage = null;
    public bool $isSuccess = false;
    public ?string $rawResponse = null;

    public int $pendingTicketsCount = 0;

    public function mount(): void
    {
        $this->refreshPendingCount();
    }

    public function refreshPendingCount(): void
    {
        $this->pendingTicketsCount = ConferenceMember::where(function ($q) {
                $q->where('whatsapp_sent', false)
                  ->orWhereNull('whatsapp_sent');
            })
            ->where(function ($q) {
                $q->where('amountPaid', '>', 0)
                  ->orWhereHas('payments', function ($p) {
                      $p->where('status', 'success');
                  });
            })
            ->count();
    }

    public function sendTest(): void
    {
        $this->validate([
            'phone' => ['required', 'string', 'min:10'],
        ]);

        $this->resultMessage = null;
        $this->rawResponse = null;

        $twilio = new TwilioWhatsAppService();

        if ($this->messageType === 'freeform') {
            $sent = $twilio->sendMessage($this->phone, $this->freeformMessage);
            if ($sent) {
                $this->isSuccess = true;
                $this->resultMessage = "Free-form message sent successfully to {$this->phone}!";
            } else {
                $this->isSuccess = false;
                $err = $twilio->getLastError() ?: "Failed to send free-form message.";
                $this->resultMessage = "{$err}\n\nNote: Free-form text is only permitted within 24 hours of the user contacting you first. Use templates for initial outbound messaging.";
            }
            return;
        }

        // Template sending
        $contentSid = null;

        if ($this->selectedTemplate === 'custom') {
            $contentSid = trim($this->customContentSid);
            if (empty($contentSid)) {
                $this->isSuccess = false;
                $this->resultMessage = "Please provide a valid Twilio Content SID (starts with HX...).";
                return;
            }
        } else {
            $contentSid = WhatsAppTemplate::getContentSid($this->selectedTemplate);

            // Default fallbacks matching Twilio Console Content SIDs
            if (!$contentSid) {
                $contentSid = match ($this->selectedTemplate) {
                    'payment_completed' => 'HX1e851b848e165f540074b548bacfd772',
                    'event_registration_confirmation', 'event_notification' => 'HX68e84d59fe2f55fc1580278239f61d04',
                    'event_payment_confirmation', 'account_alert' => 'HXb9b059eff7fa715d5f93418d2d9e0d5a',
                    'event_reminder' => 'HXb8a69c466d72d6bfac44dc932a4b7fe8',
                    default => 'HX1e851b848e165f540074b548bacfd772',
                };
            }
        }

        // Automatic variable mapping matching each template's exact schema
        $variables = match ($this->selectedTemplate) {
            'payment_completed' => ($this->variableFormat === 'numbered') ? [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => $this->attendeeName,
                '4' => $this->email,
                '5' => $this->ticketCode,
                '6' => $this->status,
                '7' => url('/qr/' . $this->ticketCode),
            ] : [
                'username'        => $this->attendeeName,
                'event_name'      => $this->eventName,
                'full_name'       => $this->attendeeName,
                'email'           => $this->email,
                'registration_id' => $this->ticketCode,
                'status'          => $this->status,
                'q_code'          => url('/qr/' . $this->ticketCode),
            ],
            'event_registration_confirmation', 'event_notification' => [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => $this->ticketCode,
                '4' => $this->eventDate,
            ],
            'event_payment_confirmation', 'account_alert' => [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => '₦' . $this->amount,
                '4' => 'REF-' . strtoupper(substr(md5(time()), 0, 8)),
            ],
            'event_reminder' => [
                '1' => $this->eventName,
                '2' => $this->attendeeName,
                '3' => $this->eventDate,
                '4' => $this->venue,
                '5' => $this->ticketCode,
            ],
            default => ($this->variableFormat === 'numbered') ? [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => $this->attendeeName,
                '4' => $this->email,
                '5' => $this->ticketCode,
                '6' => $this->status,
                '7' => url('/qr/' . $this->ticketCode),
            ] : [
                'username'        => $this->attendeeName,
                'event_name'      => $this->eventName,
                'full_name'       => $this->attendeeName,
                'email'           => $this->email,
                'registration_id' => $this->ticketCode,
                'status'          => $this->status,
                'q_code'          => url('/qr/' . $this->ticketCode),
            ]
        };

        try {
            $sent = $twilio->sendTemplateMessage($this->phone, $contentSid, $variables);

            if ($sent) {
                $this->isSuccess = true;
                $this->resultMessage = "Template '{$this->selectedTemplate}' (SID: {$contentSid}) was successfully dispatched to {$this->phone}!";
                $this->rawResponse = json_encode(['template_sid' => $contentSid, 'variables' => $variables], JSON_PRETTY_PRINT);
            } else {
                $this->isSuccess = false;
                $err = $twilio->getLastError() ?: "Twilio rejected the template dispatch.";
                $this->resultMessage = "{$err}\n\nPlease check your Twilio credentials, ensure WhatsApp Sender is active, and verify that Content SID '{$contentSid}' is approved in your Twilio Console.";
            }
        } catch (\Throwable $e) {
            $this->isSuccess = false;
            $this->resultMessage = "Error: " . $e->getMessage();
        }
    }

    public ?string $inspectedTemplate = null;

    public function inspectTemplate(): void
    {
        $contentSid = ($this->selectedTemplate === 'custom')
            ? trim($this->customContentSid)
            : (WhatsAppTemplate::getContentSid($this->selectedTemplate) ?? 'HX807e357191a3eb58e8050ee90e6b74e4');

        if (empty($contentSid)) {
            $this->resultMessage = "Please enter or select a valid Content SID to inspect.";
            $this->isSuccess = false;
            return;
        }

        $twilio = new TwilioWhatsAppService();
        $details = $twilio->fetchContentDetails($contentSid);
        $approvals = $twilio->fetchApprovalStatus($contentSid);

        if (!$details) {
            $this->isSuccess = false;
            $this->resultMessage = "Could not fetch Content SID '{$contentSid}' from Twilio. Please check your Twilio Account SID and Auth Token in .env.";
            $this->inspectedTemplate = null;
            return;
        }

        $this->isSuccess = true;
        $this->resultMessage = "Fetched template structure directly from Twilio Content API:";
        $this->inspectedTemplate = json_encode([
            'content_details' => $details,
            'approval_status' => $approvals,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function processPendingTickets(): void
    {
        try {
            Artisan::call('whatsapp:send-pending-tickets', ['--limit' => 50]);
            $output = Artisan::output();
            $this->refreshPendingCount();

            $this->isSuccess = true;
            $this->resultMessage = "Pending ticket scanner executed!\n" . $output;
        } catch (\Throwable $e) {
            $this->isSuccess = false;
            $this->resultMessage = "Error executing scanner: " . $e->getMessage();
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">WhatsApp Test & Dispatch Center</flux:heading>
            <flux:subheading>Test WhatsApp template delivery, simulate bot conversations, and trigger batch ticket dispatches.</flux:subheading>
        </div>
        <flux:button variant="ghost" href="{{ route('whatsapp-templates.index') }}" wire:navigate icon="chat-bubble-oval-left">
            Manage Templates
        </flux:button>
    </div>

    @if ($resultMessage)
        <div class="mb-6 p-4 rounded-xl border {{ $isSuccess ? 'bg-green-50 border-green-300 text-green-800 dark:bg-green-950/40 dark:border-green-800 dark:text-green-300' : 'bg-red-50 border-red-300 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300' }}">
            <div class="flex items-center gap-2 font-bold mb-1">
                <span>{{ $isSuccess ? '✅ Success' : '❌ Error' }}</span>
            </div>
            <p class="text-sm whitespace-pre-line">{{ $resultMessage }}</p>
            @if($rawResponse)
                <pre class="mt-2 p-2 bg-black/10 rounded text-xs overflow-x-auto font-mono">{{ $rawResponse }}</pre>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Test Sender Card (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:heading size="lg" class="mb-1">Send Test WhatsApp Message</flux:heading>
                <flux:subheading class="mb-4">Test template delivery to your personal WhatsApp number before broadcasting.</flux:subheading>

                <form wire:submit="sendTest" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Recipient WhatsApp Number *</flux:label>
                            <flux:input wire:model="phone" placeholder="e.g. +2348012345678 or 08012345678" required />
                            <flux:error name="phone" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Message Type</flux:label>
                            <flux:select wire:model.live="messageType">
                                <flux:select.option value="template">Pre-Approved Template</flux:select.option>
                                <flux:select.option value="freeform">Free-Form Text (24h window)</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    @if($messageType === 'template')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Select Template</flux:label>
                                <flux:select wire:model.live="selectedTemplate">
                                    <flux:select.option value="payment_completed">payment_completed (QR Ticket & Registration Details)</flux:select.option>
                                    <flux:select.option value="event_registration_confirmation">event_registration_confirmation (Details Confirmation)</flux:select.option>
                                    <flux:select.option value="event_payment_confirmation">event_payment_confirmation (Payment Receipt Summary)</flux:select.option>
                                    <flux:select.option value="event_reminder">event_reminder (Day-Before Event Reminder)</flux:select.option>
                                    <flux:select.option value="custom">Custom Content SID...</flux:select.option>
                                </flux:select>
                            </flux:field>

                            <flux:field>
                                <flux:label>Template Variable Format in Twilio</flux:label>
                                <flux:select wire:model.live="variableFormat">
                                    <flux:select.option value="named">Named: {username}, {event_name}, {email}...</flux:select.option>
                                    <flux:select.option value="numbered">Numbered: {{1}}, {{2}}, {{3}}...</flux:select.option>
                                </flux:select>
                            </flux:field>
                        </div>

                        @if($selectedTemplate === 'custom')
                            <flux:field>
                                <flux:label>Custom Twilio Content SID *</flux:label>
                                <flux:input wire:model="customContentSid" placeholder="HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required />
                            </flux:field>
                        @endif

                        <!-- Dynamic Variable Inputs -->
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-3 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">Template Test Variables</flux:heading>
                                <flux:badge size="sm" color="zinc">Format: {{ ucfirst($variableFormat) }}</flux:badge>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <flux:field>
                                    <flux:label>Attendee / Full Name</flux:label>
                                    <flux:input wire:model="attendeeName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Event Name</flux:label>
                                    <flux:input wire:model="eventName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Ticket Code / Registration ID</flux:label>
                                    <flux:input wire:model="ticketCode" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Email Address</flux:label>
                                    <flux:input wire:model="email" />
                                </flux:field>
                            </div>
                        </div>
                    @else
                        <flux:field>
                            <flux:label>Message Body</flux:label>
                            <flux:textarea wire:model="freeformMessage" rows="4" />
                        </flux:field>
                    @endif

                    <div class="flex items-center gap-3 pt-2">
                        <flux:button type="submit" variant="primary" icon="paper-airplane">
                            Send Test Message
                        </flux:button>
                        @if($messageType === 'template')
                            <flux:button type="button" wire:click="inspectTemplate" variant="ghost" icon="magnifying-glass">
                                Inspect Template on Twilio
                            </flux:button>
                        @endif
                    </div>
                </form>

                @if($inspectedTemplate)
                    <div class="mt-6 p-4 rounded-xl border border-blue-200 bg-blue-50/50 dark:bg-blue-950/20 dark:border-blue-900">
                        <div class="flex items-center justify-between mb-2">
                            <flux:heading size="sm" class="text-blue-900 dark:text-blue-200">Twilio Content API Inspection Data</flux:heading>
                            <flux:badge size="sm" color="blue">Live Twilio Response</flux:badge>
                        </div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-2">Review placeholder variables, approval state, and body definition as registered in Twilio Console:</p>
                        <pre class="p-3 bg-zinc-900 text-zinc-100 rounded-lg text-xs overflow-x-auto font-mono max-h-96 leading-relaxed">{{ $inspectedTemplate }}</pre>
                    </div>
                @endif
            </flux:card>
        </div>

        <!-- Cron Scanner & Quick Action Widget (1 col) -->
        <div class="space-y-6">
            <flux:card class="border-t-4 border-indigo-500">
                <flux:heading size="lg" class="mb-1">Automated Ticket Scanner</flux:heading>
                <flux:subheading class="mb-4">Scans the database for paid attendees created by the old system or direct checkout and dispatches their tickets.</flux:subheading>

                <div class="p-4 bg-indigo-50 dark:bg-zinc-800 rounded-xl mb-4 text-center">
                    <p class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $pendingTicketsCount }}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 uppercase font-semibold tracking-wider">Pending WhatsApp Tickets</p>
                </div>

                <div class="space-y-3">
                    <flux:button wire:click="processPendingTickets" variant="primary" class="w-full" icon="arrow-path">
                        Run Ticket Scanner Now
                    </flux:button>
                    <flux:button wire:click="refreshPendingCount" variant="ghost" class="w-full">
                        Refresh Counter
                    </flux:button>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-2">Server Cron Configuration</flux:heading>
                <p class="text-xs text-zinc-500 mb-2">To bypass <code class="bg-zinc-200 dark:bg-zinc-700 px-1 py-0.5 rounded text-[11px]">proc_open</code> restrictions on shared hosting / cPanel, run the artisan command directly or use the HTTP webhook:</p>
                
                <div class="space-y-2">
                    <div>
                        <span class="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Option 1: Direct CLI Command</span>
                        <div class="p-2 mt-1 bg-zinc-100 dark:bg-zinc-800 rounded font-mono text-xs text-zinc-800 dark:text-zinc-200 select-all overflow-x-auto">
                            * * * * * cd /home/nlcgacom/api-v2.nlcga.com/server_files && php artisan whatsapp:send-pending-tickets >> /dev/null 2>&1
                        </div>
                    </div>
                    <div>
                        <span class="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Option 2: Web Cron (cURL / cron-job.org)</span>
                        <div class="p-2 mt-1 bg-zinc-100 dark:bg-zinc-800 rounded font-mono text-xs text-indigo-600 dark:text-indigo-400 select-all overflow-x-auto">
                            curl -s https://api-v2.nlcga.com/api/cron/process-tickets
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
