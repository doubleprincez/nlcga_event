<?php

use App\Models\Package;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Package $package;
    public ?string $name = '';
    public ?string $description = '';
    public ?string $duration = '';
    public ?string $amount = '0';

    public function mount(Package $package): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->package = $package;
        $this->fill($package->only(['name', 'description', 'duration']));
        $this->amount = (string) $package->amount;
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255', 'amount' => 'required|numeric|min:0']);
        $this->package->update($this->only(['name', 'description', 'duration', 'amount']));
        session()->flash('success', 'Package updated.');
        $this->redirect(route('packages.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('packages.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Package</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Name *</flux:label><flux:input wire:model="name" /><flux:error name="name" /></flux:field>
                <flux:field><flux:label>Amount *</flux:label><flux:input type="number" wire:model="amount" step="0.01" /><flux:error name="amount" /></flux:field>
                <flux:field><flux:label>Duration</flux:label><flux:input wire:model="duration" /></flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="3" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('packages.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




