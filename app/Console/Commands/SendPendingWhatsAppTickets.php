<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConferenceMember;
use App\Models\Payment;
use App\Models\WhatsAppTemplate;
use App\Services\TwilioWhatsAppService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Log;

class SendPendingWhatsAppTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send-pending-tickets {--limit=25 : Maximum number of tickets to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan database for paid attendees where whatsapp_sent is false and dispatch WhatsApp ticket templates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Scanning database for pending WhatsApp ticket dispatches (Limit: {$limit})...");

        // Find members who paid but haven't received their WhatsApp ticket
        $members = ConferenceMember::where(function ($q) {
                $q->where('whatsapp_sent', false)
                  ->orWhereNull('whatsapp_sent');
            })
            ->where(function ($q) {
                $q->where('amountPaid', '>', 0)
                  ->orWhereHas('payments', function ($p) {
                      $p->where('status', Payment::STATUS_SUCCESS);
                  });
            })
            ->whereNotNull('phoneNumber')
            ->where('phoneNumber', '!=', '')
            ->limit($limit)
            ->get();

        if ($members->isEmpty()) {
            $this->info("No pending WhatsApp tickets found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$members->count()} pending ticket(s) to process.");

        $twilio = new TwilioWhatsAppService();

        // Retrieve dynamic template SID from database or use fallback
        $contentSid = WhatsAppTemplate::getContentSid('payment_completed')
            ?? WhatsAppTemplate::getContentSid('event_notification')
            ?? env('TWILIO_DEFAULT_TEMPLATE_SID', 'HXbfeddfb7e929b15cb88accb39d23cec6');

        $successCount = 0;
        $failedCount = 0;

        foreach ($members as $member) {
            try {
                // 1. Ensure unique ticket code exists
                if (empty($member->unique_code)) {
                    $member->generateUniqueCode();
                }

                $ticketUrl = url('/ticket/view/' . $member->unique_code);

                // 2. Ensure QR Code is generated
                $qrImage = QrCodeService::getOrGenerate($member->unique_code, $ticketUrl);
                $member->qr_path = 'qrcodes/' . $member->unique_code . '.png';
                $member->save();

                // 3. Map dynamic template variables
                $variables = [
                    '1'               => $member->fullName,
                    '2'               => 'NLCGA Conference 2026',
                    '3'               => $member->fullName,
                    '4'               => $member->email ?? 'N/A',
                    '5'               => $member->unique_code,
                    '6'               => 'Confirmed',
                    'username'        => $member->fullName,
                    'full_name'       => $member->fullName,
                    'event_name'      => 'NLCGA Conference 2026',
                    'email'           => $member->email ?? 'N/A',
                    'registration_id' => $member->unique_code,
                    'status'          => 'Confirmed',
                    'q_code'          => url('/qr/' . $member->unique_code),
                ];

                $sent = $twilio->sendTemplateMessage(
                    $member->phoneNumber,
                    $contentSid,
                    $variables
                );

                if ($sent) {
                    $member->update(['whatsapp_sent' => true]);
                    $this->info("✅ Dispatched ticket to {$member->fullName} ({$member->phoneNumber}) [{$member->unique_code}]");
                    $successCount++;
                } else {
                    $this->warn("⚠️ Failed sending WhatsApp to {$member->phoneNumber} ({$member->fullName})");
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                Log::error("Error processing ticket dispatch for member ID {$member->id}: " . $e->getMessage());
                $this->error("❌ Exception for member ID {$member->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        $this->info("Scan completed. Success: {$successCount}, Failed/Skipped: {$failedCount}");
        return Command::SUCCESS;
    }
}
