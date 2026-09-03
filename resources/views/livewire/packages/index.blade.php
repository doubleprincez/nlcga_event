<?php

use App\Models\Package;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array
    {
        return ['packages' => Package::latest()->get()];
    }

    public function delete(int $id): void
    {
        Package::findOrFail($id)->delete();
        session()->flash('success', 'Package deleted.');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Packages</flux:heading>
        <flux:button variant="primary" href="{{ route('packages.create') }}" wire:navigate icon="plus">Add Package</flux:button>
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Duration</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($packages as $pkg)
                <flux:table.row>
                    <flux:table.cell>{{ $pkg->name }}</flux:table.cell>
                    <flux:table.cell>₦{{ number_format($pkg->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ $pkg->duration ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('packages.edit', $pkg) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $pkg->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-400">No packages yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>


