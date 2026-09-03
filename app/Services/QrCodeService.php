<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Get or dynamically generate the PNG string for a given payload/code
     */
    public static function getOrGenerate(string $code, string $ticketUrl): ?string
    {
        $fileName = "qrcodes/{$code}.png";

        // 1. Check if already exists in storage
        if (Storage::disk('public')->exists($fileName)) {
            return Storage::disk('public')->get($fileName);
        }

        // Ensure qrcodes directory exists
        if (!Storage::disk('public')->exists('qrcodes')) {
            Storage::disk('public')->makeDirectory('qrcodes');
        }

        $imageBytes = null;

        // 2. Try Endroid QR Code
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            try {
                $qrCode = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($ticketUrl)
                    ->size(300)
                    ->margin(10)
                    ->build();

                $imageBytes = $qrCode->getString();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Endroid QR generation failed: " . $e->getMessage());
            }
        }

        // 3. Try SimpleSoftwareIO QrCode
        if (!$imageBytes && class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                $imageBytes = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->margin(2)
                    ->generate($ticketUrl);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("SimpleQrCode generation failed: " . $e->getMessage());
            }
        }

        // 4. Fallback to reliable public QR generator API
        if (!$imageBytes) {
            try {
                $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&format=png&data=" . urlencode($ticketUrl);
                $res = Http::withoutVerifying()->timeout(10)->get($apiUrl);
                if ($res->successful() && !empty($res->body())) {
                    $imageBytes = $res->body();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Remote QR generation fallback failed: " . $e->getMessage());
            }
        }

        // Save generated image for future instant retrieval
        if ($imageBytes) {
            Storage::disk('public')->put($fileName, $imageBytes);
        }

        return $imageBytes;
    }
}
