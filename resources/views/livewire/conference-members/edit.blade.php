<?php

use App\Models\ConferenceMember;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ConferenceMember $conferenceMember;
    public ?string $fullName = '';
    public ?string $email = '';
    public ?string $phoneNumber = '';
    public ?string $passType = '';
    public ?string $organisation = '';
    public ?string $jobTitle = '';
    public ?string $sector = '';
    public ?string $paymentMethod = '';
    public ?string $amountPaid = '0';
    public ?string $conference_year = '';
    public bool $is_checked_in = false;

    public function mount(ConferenceMember $conferenceMember): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->conferenceMember = $conferenceMember;
        $this->fill($conferenceMember->only([
            'fullName', 'email', 'phoneNumber', 'passType', 'organisation',
            'jobTitle', 'sector', 'paymentMethod', 'conference_year', 'is_checked_in',
        ]));
        $this->amountPaid = (string) $conferenceMember->amountPaid;
    }

    public function save(): void
    {
        $this->validate([
            'fullName' => 'required|string|max:255',
            'email'    => 'required|email',
        ]);

        $this->conferenceMember->update($this->only([
            'fullName', 'email', 'phoneNumber', 'passType', 'organisation',
            'jobTitle', 'sector', 'paymentMethod', 'amountPaid', 'conference_year', 'is_checked_in',
        ]));

        session()->flash('success', 'Conference member updated.');
        $this->redirect(route('conference-members.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('conference-members.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Conference Member</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-2xl space-y-4">
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field class="md:col-span-2">
                    <flux:label>Full Name *</flux:label>
                    <flux:input wire:model="fullName" />
                    <flux:error name="fullName" />
                </flux:field>
                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input type="email" wire:model="email" />
                    <flux:error name="email" />
                </flux:field>
                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phoneNumber" />
                </flux:field>
                <flux:field>
                    <flux:label>Pass Type</flux:label>
                    <flux:select wire:model="passType">
                        <flux:select.option value="">— Select —</flux:select.option>
                        <flux:select.option value="delegate">Delegate</flux:select.option>
                        <flux:select.option value="exhibitor">Exhibitor</flux:select.option>
                        <flux:select.option value="speaker">Speaker</flux:select.option>
                        <flux:select.option value="sponsor">Sponsor</flux:select.option>
                        <flux:select.option value="press">Press</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Conference Year</flux:label>
                    <flux:input wire:model="conference_year" />
                </flux:field>
                <flux:field>
                    <flux:label>Organisation</flux:label>
                    <flux:input wire:model="organisation" />
                </flux:field>
                <flux:field>
                    <flux:label>Job Title</flux:label>
                    <flux:input wire:model="jobTitle" />
                </flux:field>
                <flux:field>
                    <flux:label>Amount Paid</flux:label>
                    <flux:input type="number" wire:model="amountPaid" step="0.01" />
                </flux:field>
                <flux:field>
                    <flux:checkbox wire:model="is_checked_in" label="Checked In" />
                </flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('conference-members.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




