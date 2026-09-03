<?php

use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Registration $registration;
    public ?string $name = '';
    public ?string $email = '';
    public ?string $phone = '';
    public bool $is_checked_in = false;

    public function mount(Registration $registration): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->registration = $registration;
        $this->fill($registration->only(['name', 'email', 'phone', 'is_checked_in']));
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $this->registration->update($this->only(['name', 'email', 'phone', 'is_checked_in']));
        session()->flash('success', 'Registration updated.');
        $this->redirect(route('registrations.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('registrations.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Registration</flux:heading>
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
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phone" />
                </flux:field>
                <flux:field>
                    <flux:checkbox wire:model="is_checked_in" label="Checked In" />
                </flux:field>
                <div class="text-sm text-zinc-500">Unique Code: <span class="font-mono">{{ $registration->unique_code }}</span></div>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('registrations.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




