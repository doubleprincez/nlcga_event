<?php

use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    public ?string $search = '';

    public function with(): array
    {
        return [
            'registrations' => Registration::query()
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('unique_code', 'like', "%{$this->search}%"))
                ->latest()->paginate(20),
        ];
    }

    public function delete(int $id): void
    {
        Registration::findOrFail($id)->delete();
        session()->flash('success', 'Registration deleted.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Registrations</flux:heading>
        <flux:button variant="primary" href="{{ route('registrations.create') }}" wire:navigate icon="plus">Register</flux:button>
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, email, code..." class="max-w-sm mb-4" icon="magnifying-glass" />
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Checked In</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($registrations as $reg)
                <flux:table.row>
                    <flux:table.cell>{{ $reg->name }}</flux:table.cell>
                    <flux:table.cell>{{ $reg->email }}</flux:table.cell>
                    <flux:table.cell>{{ $reg->phone }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $reg->unique_code }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $reg->is_checked_in ? 'green' : 'zinc' }}">
                            {{ $reg->is_checked_in ? 'Yes' : 'No' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('registrations.edit', $reg) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $reg->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-400">No registrations found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $registrations->links() }}</div>
</div>



