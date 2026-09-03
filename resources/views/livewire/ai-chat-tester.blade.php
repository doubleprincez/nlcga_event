<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

new #[Layout("components.layouts.app")] class extends Component
{
    public array $messages = [];
    public ?string $prompt = "";

    public function mount()
    {
        $this->messages[] = [
            "role" => "assistant",
            "content" => "Hello Admin! Send me a message to see how I would respond to your attendees based on the current AI training prompt."
        ];
    }

    public function sendMessage()
    {
        if (empty(trim($this->prompt))) return;

        $userMessage = trim($this->prompt);
        $this->messages[] = ["role" => "user", "content" => $userMessage];
        $this->prompt = "";

        $reply = $this->queryOpenAI($userMessage);
        $this->messages[] = ["role" => "assistant", "content" => $reply];
    }

    protected function queryOpenAI(string $userPrompt): string
    {
        $apiKey = env("GEMINI_API_KEY") ?? env("OPENAI_API_KEY");
        if (!$apiKey) return "Error: No API key found in .env";

        $defaultPrompt = "You are an AI assistant.";
        $systemPrompt = Storage::disk("local")->exists("ai_training.json") 
            ? json_decode(Storage::disk("local")->get("ai_training.json"), true)["prompt"] ?? $defaultPrompt 
            : $defaultPrompt;

        try {
            if (env("GEMINI_API_KEY")) {
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $apiKey, [
                        "system_instruction" => [
                            "parts" => [
                                ["text" => $systemPrompt]
                            ]
                        ],
                        "contents" => [
                            [
                                "role" => "user",
                                "parts" => [
                                    ["text" => $userPrompt]
                                ]
                            ]
                        ],
                        "generationConfig" => [
                            "maxOutputTokens" => 300
                        ]
                    ]);
                
                if ($response->successful()) {
                    $text = $response->json("candidates.0.content.parts.0.text");
                    return !empty($text) ? trim($text) : "No response content.";
                }

                return "Error from Gemini API (" . $response->status() . "): " . $response->body();
            } else {
                $response = Http::withToken($apiKey)->timeout(15)->post("https://api.openai.com/v1/chat/completions", [
                    "model" => "gpt-4o-mini",
                    "messages" => [
                        ["role" => "system", "content" => $systemPrompt],
                        ["role" => "user", "content" => $userPrompt],
                    ]
                ]);
                return $response->json()["choices"][0]["message"]["content"] ?? "No response.";
            }
        } catch (\Exception $e) {
            return "Error calling AI API: " . $e->getMessage();
        }
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">AI Chat Tester</flux:heading>

    <flux:card class="max-w-3xl flex flex-col h-[600px]">
        <div class="mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
            <div>
                <flux:heading>Test WhatsApp Bot</flux:heading>
                <flux:subheading>Simulate a conversation with the AI using your trained prompt.</flux:subheading>
            </div>
            <flux:button variant="ghost" href="{{ route('ai.training') }}" wire:navigate icon="cog-6-tooth">Edit Prompt</flux:button>
        </div>
        
        <div class="flex-1 overflow-y-auto space-y-4 mb-4 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            @foreach($messages as $msg)
                <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] p-3 rounded-lg {{ $msg['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600' }}">
                        {{ $msg['content'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <form wire:submit="sendMessage" class="flex gap-2">
                <flux:input wire:model="prompt" placeholder="Type a message..." class="flex-1" />
                <flux:button type="submit" variant="primary" icon="paper-airplane">Send</flux:button>
            </form>
        </div>
    </flux:card>
</div>
