<?php

use App\Models\BotCommunication;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    public ?string $search = '';
    public ?string $directionFilter = '';

    public function with(): array
    {
        return [
            'messages' => BotCommunication::query()
                ->when($this->search, fn($q) => $q->where('phone', 'like', "%{$this->search}%")->orWhere('message', 'like', "%{$this->search}%"))
                ->when($this->directionFilter, fn($q) => $q->where('direction', $this->directionFilter))
                ->latest()->paginate(25),
        ];
    }

    public function delete(int $id): void { BotCommunication::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Bot Communications</flux:heading>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search phone or message..." class="max-w-sm" icon="magnifying-glass" />
        <flux:select wire:model.live="directionFilter" class="w-36">
            <flux:select.option value="">All</flux:select.option>
            <flux:select.option value="inbound">Inbound</flux:select.option>
            <flux:select.option value="outbound">Outbound</flux:select.option>
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
<flux:table.column>Phone</flux:table.column>
                <flux:table.column>Direction</flux:table.column>
                <flux:table.column>Message</flux:table.column>
                <flux:table.column>Time</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($messages as $msg)
                <flux:table.row>
                    <flux:table.cell>{{ $msg->phone }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $msg->direction === 'inbound' ? 'blue' : 'green' }}">
                            {{ ucfirst($msg->direction) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-sm truncate">{{ $msg->message }}</flux:table.cell>
                    <flux:table.cell>{{ $msg->created_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" wire:click="delete({{ $msg->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5" class="text-center text-zinc-400">No messages found.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $messages->links() }}</div>
</div>



