<?php

use App\Models\WhatsAppTemplate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public WhatsAppTemplate $whatsappTemplate;
    public ?string $name = '';
    public ?string $display_name = '';
    public ?string $content_sid = '';
    public ?string $description = '';
    public ?string $category = 'UTILITY';
    public ?string $status = 'active';

    public function mount(WhatsAppTemplate $whatsappTemplate): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->whatsappTemplate = $whatsappTemplate;
        $this->fill($whatsappTemplate->only(['name', 'display_name', 'content_sid', 'description', 'category', 'status']));
    }

    public function save(): void
    {
        $this->validate([
            'name'        => ['required', 'string', Rule::unique(WhatsAppTemplate::class, 'name')->ignore($this->whatsappTemplate->id)],
            'display_name' => 'required|string|max:255',
            'content_sid' => 'required|string',
        ]);

        $this->whatsappTemplate->update($this->only(['name', 'display_name', 'content_sid', 'description', 'category', 'status']));
        session()->flash('success', 'Template updated.');
        $this->redirect(route('whatsapp-templates.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('whatsapp-templates.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit WhatsApp Template</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Name *</flux:label><flux:input wire:model="name" /><flux:error name="name" /></flux:field>
                <flux:field><flux:label>Display Name *</flux:label><flux:input wire:model="display_name" /><flux:error name="display_name" /></flux:field>
                <flux:field><flux:label>Content SID *</flux:label><flux:input wire:model="content_sid" /><flux:error name="content_sid" /></flux:field>
                <flux:field><flux:label>Category</flux:label>
                    <flux:select wire:model="category">
                        <flux:select.option value="UTILITY">Utility</flux:select.option>
                        <flux:select.option value="MARKETING">Marketing</flux:select.option>
                        <flux:select.option value="AUTHENTICATION">Authentication</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="2" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('whatsapp-templates.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




