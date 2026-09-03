<?php

use App\Models\User;
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
            'users' => User::query()
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->latest()->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
        session()->flash('success', 'User deleted.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Users</flux:heading>
        <flux:button variant="primary" href="{{ route('users.create') }}" wire:navigate icon="plus">Add User</flux:button>
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." class="max-w-sm mb-4" icon="magnifying-glass" />
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($users as $user)
                <flux:table.row>
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if($user->is_admin)
                            <flux:badge color="red">Admin</flux:badge>
                        @elseif($user->is_staff)
                            <flux:badge color="blue">Staff</flux:badge>
                        @else
                            <flux:badge color="zinc">Member</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($user->isApprovedMember())
                            <flux:badge color="green">Approved</flux:badge>
                        @else
                            <flux:badge color="yellow">Pending</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('users.edit', $user) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $user->id }})"
                                wire:confirm="Delete this user?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-400">No users found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $users->links() }}</div>
</div>



