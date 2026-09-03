<?php

use App\Models\ConferenceMember;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout("components.layouts.app")] class extends Component
{
    use WithPagination;
    public ?string $search = "";
    public ?string $yearFilter = "";

    public function with(): array
    {
        return [
            "members" => ConferenceMember::with("payments")
                ->when($this->search, fn($q) => $q->where("fullName", "like", "%{$this->search}%")->orWhere("email", "like", "%{$this->search}%"))
                ->when($this->yearFilter, fn($q) => $q->where("conference_year", $this->yearFilter))
                ->latest()->paginate(20),
            "years" => ConferenceMember::select("conference_year")->distinct()->pluck("conference_year")->filter()->toArray(),
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedYearFilter(): void { $this->resetPage(); }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Conference Payments</flux:heading>
    <div class="flex gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, email..." class="max-w-sm" icon="magnifying-glass" />
        <flux:select wire:model.live="yearFilter" class="w-48">
            <flux:select.option value="">All Conferences</flux:select.option>
            @foreach($years as $year)
                <flux:select.option value="{{ $year }}">{{ $year }} Conference</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Conference Member</flux:table.column>
            <flux:table.column>Conference</flux:table.column>
            <flux:table.column>Pass Type</flux:table.column>
            <flux:table.column>Amount Paid (Legacy)</flux:table.column>
            <flux:table.column>Online Payments</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($members as $member)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium">{{ $member->fullName }}</div>
                        <div class="text-sm text-zinc-500">{{ $member->email }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">{{ $member->conference_year ?? "N/A" }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $member->passType ?? "N/A" }}</flux:table.cell>
                    <flux:table.cell>
                        @if($member->amountPaid)
                            <div class="font-medium">?{{ number_format(floatval(str_replace(",", "", $member->amountPaid)), 2) }}</div>
                            <div class="text-xs text-zinc-500">{{ $member->paymentMethod }}</div>
                        @else
                            <span class="text-zinc-400">No manual record</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($member->payments->isEmpty())
                            <span class="text-zinc-400">No online payments</span>
                        @else
                            <div class="space-y-1">
                                @foreach($member->payments as $payment)
                                    <div class="flex items-center gap-2 text-sm">
                                        <flux:badge size="sm" color="{{ match($payment->status) { 'success' => 'green', 'failed' => 'red', 'underpaid' => 'yellow', default => 'zinc' } }}">
                                            {{ ucfirst($payment->status) }}
                                        </flux:badge>
                                        <span class="font-mono text-xs">{{ $payment->reference }}</span>
                                        <span>?{{ number_format($payment->amount_paid_kobo / 100, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-400">No conference members found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $members->links() }}</div>
</div>
