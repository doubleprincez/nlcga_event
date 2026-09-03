<?php

use App\Models\EmailTemplate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $name = '';
    public ?string $subject = '';
    public ?string $body = '';
    public ?string $type = '';
    public ?bool $is_active = true;

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255']);
        EmailTemplate::create($this->only(['name', 'subject', 'body', 'type', 'is_active']));
        session()->flash('success', 'Template created.');
        $this->redirect(route('email-templates.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('email-templates.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Add Email Template</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-2xl space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Name *</flux:label><flux:input wire:model="name" /><flux:error name="name" /></flux:field>
                <flux:field><flux:label>Subject</flux:label><flux:input wire:model="subject" /></flux:field>
                <flux:field><flux:label>Type</flux:label><flux:input wire:model="type" placeholder="e.g. welcome, notification" /></flux:field>
                <flux:field><flux:checkbox wire:model="is_active" label="Active" /></flux:field>
                <flux:field><flux:label>Body (HTML)</flux:label><flux:textarea wire:model="body" rows="10" class="font-mono text-sm" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('email-templates.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>





