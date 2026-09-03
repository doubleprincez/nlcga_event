<?php

use App\Models\Community;
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
            'communities' => Community::with(['member', 'category'])
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->latest()->paginate(15),
        ];
    }

    public function delete(int $id): void { Community::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Communities</flux:heading>
        <flux:button variant="primary" href="{{ route('communities.create') }}" wire:navigate icon="plus">Add Community</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search..." class="max-w-sm mb-4" icon="magnifying-glass" />
    <flux:table>
        <flux:table.columns>
<flux:table.column>Title</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Restriction</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($communities as $c)
                <flux:table.row>
                    <flux:table.cell>{{ $c->title }}</flux:table.cell>
                    <flux:table.cell>{{ $c->category?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell><flux:badge color="{{ $c->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($c->status) }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ ucfirst($c->restriction) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('communities.edit', $c) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $c->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5" class="text-center text-zinc-400">No communities found.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $communities->links() }}</div>
</div>



