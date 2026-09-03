<?php

use App\Models\MembershipPayment;
use App\Models\Member;
use App\Models\Package;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?int $member_id = null;
    public ?int $package_id = null;
    public ?string $amount = '0';
    public ?string $reference = '';
    public ?string $status = 'pending';
    public ?string $paid_at = '';

    public function save(): void
    {
        $this->validate([
            'member_id' => 'required|exists:members,id',
            'amount'    => 'required|numeric|min:0',
        ]);

        MembershipPayment::create([
            'member_id'  => $this->member_id,
            'package_id' => $this->package_id,
            'amount'     => $this->amount,
            'reference'  => $this->reference ?: null,
            'status'     => $this->status,
            'paid_at'    => $this->paid_at ?: null,
        ]);

        session()->flash('success', 'Membership payment recorded.');
        $this->redirect(route('membership-payments.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'members'  => Member::orderBy('company_name')->get(['id', 'company_name']),
            'packages' => Package::orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('membership-payments.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Add Membership Payment</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Member *</flux:label>
                    <flux:select wire:model="member_id">
                        <flux:select.option value="">— Select Member —</flux:select.option>
                        @foreach($members as $m)
                            <flux:select.option value="{{ $m->id }}">{{ $m->company_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="member_id" />
                </flux:field>
                <flux:field>
                    <flux:label>Package</flux:label>
                    <flux:select wire:model="package_id">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach($packages as $pkg)
                            <flux:select.option value="{{ $pkg->id }}">{{ $pkg->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Amount *</flux:label><flux:input type="number" wire:model="amount" step="0.01" /><flux:error name="amount" /></flux:field>
                <flux:field><flux:label>Reference</flux:label><flux:input wire:model="reference" /></flux:field>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="success">Success</flux:select.option>
                        <flux:select.option value="failed">Failed</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Paid At</flux:label><flux:input type="datetime-local" wire:model="paid_at" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('membership-payments.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>





