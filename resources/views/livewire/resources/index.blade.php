<?php

use App\Models\Resource;
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
            'resources' => Resource::when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->latest()->paginate(15),
        ];
    }

    public function delete(int $id): void { Resource::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Resources</flux:heading>
        <flux:button variant="primary" href="{{ route('resources.create') }}" wire:navigate icon="plus">Add Resource</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search resources..." class="max-w-sm mb-4" icon="magnifying-glass" />
    <flux:table>
        <flux:table.columns>
<flux:table.column>Title</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Year</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($resources as $r)
                <flux:table.row>
                    <flux:table.cell>{{ $r->title }}</flux:table.cell>
                    <flux:table.cell>{{ $r->post_type }}</flux:table.cell>
                    <flux:table.cell>{{ $r->conference_year ?? '—' }}</flux:table.cell>
                    <flux:table.cell><flux:badge color="{{ $r->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($r->status) }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('resources.edit', $r) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $r->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5" class="text-center text-zinc-400">No resources found.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $resources->links() }}</div>
</div>



