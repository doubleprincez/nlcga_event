<?php

use App\Models\EmailTemplate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public EmailTemplate $emailTemplate;
    public ?string $name = '';
    public ?string $subject = '';
    public ?string $body = '';
    public ?string $type = '';
    public ?bool $is_active = true;

    public function mount(EmailTemplate $emailTemplate): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->emailTemplate = $emailTemplate;
        $data = $emailTemplate->only(['name', 'subject', 'body', 'type', 'is_active']);
        $data['is_active'] = (bool) ($emailTemplate->is_active ?? true);
        $this->fill($data);
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255']);
        $this->emailTemplate->update($this->only(['name', 'subject', 'body', 'type', 'is_active']));
        session()->flash('success', 'Template updated.');
        $this->redirect(route('email-templates.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('email-templates.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Email Template</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-2xl space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Name *</flux:label><flux:input wire:model="name" /><flux:error name="name" /></flux:field>
                <flux:field><flux:label>Subject</flux:label><flux:input wire:model="subject" /></flux:field>
                <flux:field><flux:label>Type</flux:label><flux:input wire:model="type" /></flux:field>
                <flux:field><flux:checkbox wire:model="is_active" label="Active" /></flux:field>
                <flux:field><flux:label>Body (HTML)</flux:label><flux:textarea wire:model="body" rows="10" class="font-mono text-sm" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('email-templates.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




