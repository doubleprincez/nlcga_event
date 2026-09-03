<?php

use App\Models\MembershipPayment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    public ?string $search = '';
    public ?string $statusFilter = '';

    public function with(): array
    {
        return [
            'payments' => MembershipPayment::with(['member', 'package'])
                ->when($this->search, fn($q) => $q->whereHas('member', fn($m) => $m->where('company_name', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->latest()->paginate(20),
        ];
    }

    public function delete(int $id): void { MembershipPayment::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Membership Payments</flux:heading>
        <flux:button variant="primary" href="{{ route('membership-payments.create') }}" wire:navigate icon="plus">Add Payment</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search member..." class="max-w-sm" icon="magnifying-glass" />
        <flux:select wire:model.live="statusFilter" class="w-36">
            <flux:select.option value="">All Status</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="success">Success</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
<flux:table.column>Member</flux:table.column>
                <flux:table.column>Package</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Reference</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Paid At</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($payments as $p)
                <flux:table.row>
                    <flux:table.cell>{{ $p->member?->company_name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $p->package?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>₦{{ number_format($p->amount, 2) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $p->reference ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ match($p->status) { 'success' => 'green', 'failed' => 'red', default => 'yellow' } }}">
                            {{ ucfirst($p->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $p->paid_at?->format('M d, Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('membership-payments.edit', $p) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $p->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="7" class="text-center text-zinc-400">No membership payments found.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $payments->links() }}</div>
</div>



