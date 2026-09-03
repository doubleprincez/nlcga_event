<?php

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public ?string $search = '';
    public ?string $approvedFilter = '';

    public function with(): array
    {
        return [
            'members' => Member::query()
                ->when($this->search, fn($q) => $q
                    ->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('representative1_fullname', 'like', "%{$this->search}%"))
                ->when($this->approvedFilter !== '', fn($q) => $q->where('isApproved', $this->approvedFilter))
                ->latest()
                ->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        Member::findOrFail($id)->delete();
        session()->flash('success', 'Member deleted.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Members</flux:heading>
        <flux:button variant="primary" href="{{ route('members.create') }}" wire:navigate icon="plus">
            Add Member
        </flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by company, email, name..." class="max-w-sm" icon="magnifying-glass" />
        <flux:select wire:model.live="approvedFilter" class="w-40">
            <flux:select.option value="">All Status</flux:select.option>
            <flux:select.option value="YES">Approved</flux:select.option>
            <flux:select.option value="NO">Pending</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
<flux:table.column>Company</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Package</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($members as $member)
                <flux:table.row>
                    <flux:table.cell>{{ $member->company_name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $member->email ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $member->phone ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $member->package?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($member->isApproved === 'YES')
                            <flux:badge color="green">Approved</flux:badge>
                        @else
                            <flux:badge color="yellow">Pending</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('members.edit', $member) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $member->id }})"
                                wire:confirm="Delete this member?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-400">No members found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $members->links() }}</div>
</div>



