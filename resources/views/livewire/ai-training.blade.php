<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new #[Layout("components.layouts.app")] class extends Component
{
    public ?string $prompt = "";

    public function mount()
    {
        $defaultPrompt = "You are an AI assistant for NLCGA Event 2026 at Summit Center, Lagos. Keep answers under 3 sentences.";
        $this->prompt = Storage::disk("local")->exists("ai_training.json") 
            ? json_decode(Storage::disk("local")->get("ai_training.json"), true)["prompt"] ?? $defaultPrompt 
            : $defaultPrompt;
    }

    public function save()
    {
        Storage::disk("local")->put("ai_training.json", json_encode(["prompt" => $this->prompt]));
        session()->flash("success", "AI System Prompt updated successfully.");
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Train AI Agent</flux:heading>
    @if(session("success"))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session("success") }}</flux:callout>@endif

    <flux:card class="max-w-3xl">
        <div class="mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading>AI System Prompt (Training Instructions)</flux:heading>
            <flux:subheading>Instruct the AI on how to behave, what event details it should know, and how it should format its answers.</flux:subheading>
        </div>
        <div>
            <form wire:submit="save" class="space-y-4">
                <flux:textarea wire:model="prompt" rows="10" placeholder="You are a helpful assistant..." />
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Save Configuration</flux:button>
                </div>
            </form>
        </div>
    </flux:card>
</div>


