<?php

use App\Models\Member;
use App\Models\Package;
use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Member $member;

    public ?string $company_name = '';
    public ?string $email = '';
    public ?string $phone = '';
    public ?string $mobile = '';
    public ?string $about_company = '';
    public ?string $website = '';
    public ?string $country = '';
    public ?string $office_address = '';
    public ?string $activities = '';
    public ?string $isApproved = 'NO';
    public ?int $package_id = null;
    public ?int $role_id = null;
    public ?string $representative1_fullname = '';
    public ?string $representative1_email = '';
    public ?string $representative1_phone = '';
    public ?string $representative1_designation = '';

    public function mount(Member $member): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->member = $member;
        $this->fill($member->only([
            'company_name', 'email', 'phone', 'mobile', 'about_company',
            'website', 'country', 'office_address', 'activities', 'isApproved',
            'package_id', 'role_id', 'representative1_fullname',
            'representative1_email', 'representative1_phone', 'representative1_designation',
        ]));
    }

    public function save(): void
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
        ]);

        $this->member->update($this->only([
            'company_name', 'email', 'phone', 'mobile', 'about_company',
            'website', 'country', 'office_address', 'activities', 'isApproved',
            'package_id', 'role_id', 'representative1_fullname',
            'representative1_email', 'representative1_phone', 'representative1_designation',
        ]));

        session()->flash('success', 'Member updated.');
        $this->redirect(route('members.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'packages' => Package::orderBy('name')->get(),
            'roles'    => Role::orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('members.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Member</flux:heading>
    </div>

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4"><flux:heading>Company Information</flux:heading></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field class="md:col-span-2">
                    <flux:label>Company Name *</flux:label>
                    <flux:input wire:model="company_name" />
                    <flux:error name="company_name" />
                </flux:field>
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" />
                    <flux:error name="email" />
                </flux:field>
                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phone" />
                </flux:field>
                <flux:field>
                    <flux:label>Mobile</flux:label>
                    <flux:input wire:model="mobile" />
                </flux:field>
                <flux:field>
                    <flux:label>Website</flux:label>
                    <flux:input wire:model="website" />
                </flux:field>
                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input wire:model="country" />
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
                <flux:field>
                    <flux:label>Role</flux:label>
                    <flux:select wire:model="role_id">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach($roles as $role)
                            <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Approval Status</flux:label>
                    <flux:select wire:model="isApproved">
                        <flux:select.option value="NO">Pending</flux:select.option>
                        <flux:select.option value="YES">Approved</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field class="md:col-span-2">
                    <flux:label>Office Address</flux:label>
                    <flux:textarea wire:model="office_address" rows="2" />
                </flux:field>
                <flux:field class="md:col-span-2">
                    <flux:label>About Company</flux:label>
                    <flux:textarea wire:model="about_company" rows="3" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4"><flux:heading>Primary Representative</flux:heading></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field class="md:col-span-2">
                    <flux:label>Full Name</flux:label>
                    <flux:input wire:model="representative1_fullname" />
                </flux:field>
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="representative1_email" />
                </flux:field>
                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="representative1_phone" />
                </flux:field>
                <flux:field>
                    <flux:label>Designation</flux:label>
                    <flux:input wire:model="representative1_designation" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update Member</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('members.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




