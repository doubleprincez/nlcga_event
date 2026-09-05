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
    public string $freeformMessage = 'Hello! This is a test message from NLCGA Event Assistant.';

    // Template variables
    public string $attendeeName = 'John Doe';
    public string $eventName = 'NLCGA Conference 2026';
    public string $email = 'john.doe@example.com';
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
                $this->resultMessage = "Failed to send free-form message. Note: Free-form text is only permitted within 24 hours of the user contacting you first. Use templates for initial outbound messaging.";
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

            // Default fallbacks if not yet in database
            if (!$contentSid) {
                $contentSid = match ($this->selectedTemplate) {
                    'payment_completed' => 'HXbfeddfb7e929b15cb88accb39d23cec6',
                    'event_notification' => 'HX807e357191a3eb58e8050ee90e6b74e4',
                    default => 'HXbfeddfb7e929b15cb88accb39d23cec6',
                };
            }
        }

        // Variable mapping
        $variables = match ($this->selectedTemplate) {
            'payment_completed' => [
                '1'               => $this->attendeeName,
                '2'               => $this->eventName,
                '3'               => $this->attendeeName,
                '4'               => $this->email,
                '5'               => $this->ticketCode,
                '6'               => $this->status,
                'username'        => $this->attendeeName,
                'full_name'       => $this->attendeeName,
                'event_name'      => $this->eventName,
                'email'           => $this->email,
                'registration_id' => $this->ticketCode,
                'status'          => $this->status,
                'q_code'          => url('/qr/' . $this->ticketCode),
            ],
            'event_notification' => [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => $this->ticketCode,
                '4' => $this->eventDate,
            ],
            'account_alert' => [
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
            default => [
                '1' => $this->attendeeName,
                '2' => $this->eventName,
                '3' => $this->ticketCode,
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
        <div class="mb-6 p-4 rounded-xl border {{ $isSuccess ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' }}">
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
                        <flux:field>
                            <flux:label>Select Template</flux:label>
                            <flux:select wire:model.live="selectedTemplate">
                                <flux:select.option value="payment_completed">payment_completed (QR Ticket & Payment Confirmation)</flux:select.option>
                                <flux:select.option value="event_notification">event_notification (Registration Confirmation)</flux:select.option>
                                <flux:select.option value="account_alert">account_alert (Payment Receipt Alert)</flux:select.option>
                                <flux:select.option value="event_reminder">event_reminder (Event Broadcast Reminder)</flux:select.option>
                                <flux:select.option value="custom">Custom Content SID...</flux:select.option>
                            </flux:select>
                        </flux:field>

                        @if($selectedTemplate === 'custom')
                            <flux:field>
                                <flux:label>Custom Twilio Content SID *</flux:label>
                                <flux:input wire:model="customContentSid" placeholder="HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required />
                            </flux:field>
                        @endif

                        <!-- Dynamic Variable Inputs -->
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-3 border border-zinc-200 dark:border-zinc-700">
                            <flux:heading size="sm">Template Test Variables</flux:heading>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <flux:field>
                                    <flux:label>Attendee Name</flux:label>
                                    <flux:input wire:model="attendeeName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Event Name</flux:label>
                                    <flux:input wire:model="eventName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Ticket Code</flux:label>
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
                    </div>
                </form>
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
                <p class="text-xs text-zinc-500 mb-2">To run this automatically every minute on your cPanel / shared hosting, add this cron job:</p>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded font-mono text-xs text-zinc-800 dark:text-zinc-200 select-all overflow-x-auto">
                    * * * * * cd /home/nlcgacom/api-v2.nlcga.com/server_files && php artisan schedule:run >> /dev/null 2>&1
                </div>
            </flux:card>
        </div>
    </div>
</div>
