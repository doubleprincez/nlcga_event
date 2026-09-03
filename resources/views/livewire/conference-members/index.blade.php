<?php

use App\Models\ConferenceMember;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    public ?string $search = '';
    public ?string $yearFilter = '';

    public function with(): array
    {
        return [
            'members' => ConferenceMember::query()
                ->when($this->search, fn($q) => $q
                    ->where('fullName', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('unique_code', 'like', "%{$this->search}%"))
                ->when($this->yearFilter, fn($q) => $q->where('conference_year', $this->yearFilter))
                ->latest()->paginate(20),
            'years' => ConferenceMember::select('conference_year')->distinct()->pluck('conference_year'),
        ];
    }

    public function delete(int $id): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        ConferenceMember::findOrFail($id)->delete();
        session()->flash('success', 'Record deleted.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Conference Members</flux:heading>
        @if(auth()->user()->isAdmin())
        <flux:button variant="primary" href="{{ route('conference-members.create') }}" wire:navigate icon="plus">Add</flux:button>
        @endif
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, email, code..." class="max-w-sm" icon="magnifying-glass" />
        <flux:select wire:model.live="yearFilter" class="w-32">
            <flux:select.option value="">All Years</flux:select.option>
            @foreach($years as $year)
                <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Pass Type</flux:table.column>
                <flux:table.column>Year</flux:table.column>
                <flux:table.column>Checked In</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                @if(auth()->user()->isAdmin())
                <flux:table.column>Actions</flux:table.column>
                @endif
        </flux:table.columns>
        <flux:table.rows>
            @forelse($members as $member)
                <flux:table.row>
                    <flux:table.cell>{{ $member->fullName }}</flux:table.cell>
                    <flux:table.cell>{{ $member->email }}</flux:table.cell>
                    <flux:table.cell>{{ $member->passType ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $member->conference_year }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $member->is_checked_in ? 'green' : 'zinc' }}">{{ $member->is_checked_in ? 'Yes' : 'No' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $member->unique_code ?? '—' }}</flux:table.cell>
                    @if(auth()->user()->isAdmin())
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('conference-members.edit', $member) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $member->id }})" wire:confirm="Delete this record?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-400">No records found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $members->links() }}</div>
</div>



