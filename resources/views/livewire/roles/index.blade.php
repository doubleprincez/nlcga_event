<?php

use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $name = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public function with(): array
    {
        return ['roles' => Role::latest()->get()];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->editingId = $id;
        $this->name = $role->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:100']);
        if ($this->editingId) {
            Role::findOrFail($this->editingId)->update(['name' => $this->name]);
        } else {
            Role::create(['name' => $this->name]);
        }
        $this->resetForm();
        session()->flash('success', 'Role saved.');
    }

    public function delete(int $id): void
    {
        Role::findOrFail($id)->delete();
        session()->flash('success', 'Role deleted.');
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->editingId = null;
        $this->showForm = false;
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Roles</flux:heading>
        <flux:button variant="primary" wire:click="create" icon="plus">Add Role</flux:button>
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if($showForm)
        <flux:card class="max-w-md mb-6">
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <flux:heading>{{ $editingId ? 'Edit Role' : 'New Role' }}</flux:heading>
            </div>
            <div>
                <form wire:submit="save" class="flex gap-3">
                    <flux:input wire:model="name" placeholder="Role name" class="flex-1" />
                    <flux:button type="submit" variant="primary">Save</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Cancel</flux:button>
                </form>
                <flux:error name="name" />
            </div>
        </flux:card>
    @endif

    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($roles as $role)
                <flux:table.row>
                    <flux:table.cell>{{ $role->name }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $role->id }})" icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $role->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="2" class="text-center text-zinc-400">No roles yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>



