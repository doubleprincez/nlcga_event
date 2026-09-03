<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public User $user;
    public ?string $name = '';
    public ?string $email = '';
    public ?string $password = '';
    public ?string $role = 'member';

    public function mount(User $user): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?? 'member';
    }

    public function save(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$this->user->id}",
            'password' => 'nullable|min:8',
            'role'     => 'required|in:admin,staff,member',
        ]);

        $data = [
            'name'     => $this->name,
            'email'    => $this->email,
            'role'     => $this->role,
            'is_admin' => $this->role === 'admin',
            'is_staff' => in_array($this->role, ['admin', 'staff']),
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        $this->user->update($data);
        session()->flash('success', 'User updated.');
        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit User</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Name *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>
                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input type="email" wire:model="email" />
                    <flux:error name="email" />
                </flux:field>
                <flux:field>
                    <flux:label>New Password <flux:subheading class="inline">(leave blank to keep current)</flux:subheading></flux:label>
                    <flux:input type="password" wire:model="password" />
                    <flux:error name="password" />
                </flux:field>
                <flux:field>
                    <flux:label>Role</flux:label>
                    <flux:select wire:model="role">
                        <flux:select.option value="member">Member</flux:select.option>
                        <flux:select.option value="staff">Staff</flux:select.option>
                        <flux:select.option value="admin">Admin</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update User</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('users.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




