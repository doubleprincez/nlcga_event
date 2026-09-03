<?php

use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $name = '';
    public ?string $email = '';
    public ?string $phone = '';

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
        ]);

        Registration::create(['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone]);
        session()->flash('success', 'Registration created.');
        $this->redirect(route('registrations.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('registrations.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Register Attendee</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>
                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input type="email" wire:model="email" />
                    <flux:error name="email" />
                </flux:field>
                <flux:field>
                    <flux:label>Phone *</flux:label>
                    <flux:input wire:model="phone" />
                    <flux:error name="phone" />
                </flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Register</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('registrations.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>





