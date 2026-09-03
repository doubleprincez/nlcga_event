<?php

use App\Models\CommunityCategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $name = '';
    public ?string $description = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public function with(): array { return ['categories' => CommunityCategory::withCount('communities')->latest()->get()]; }
    public function create(): void { $this->reset(['name', 'description', 'editingId']); $this->showForm = true; }
    public function edit(int $id): void { $c = CommunityCategory::findOrFail($id); $this->editingId = $id; $this->name = $c->name; $this->description = $c->description ?? ''; $this->showForm = true; }
    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255']);
        $data = ['name' => $this->name, 'description' => $this->description];
        $this->editingId ? CommunityCategory::findOrFail($this->editingId)->update($data) : CommunityCategory::create($data);
        $this->reset(['name', 'description', 'editingId', 'showForm']);
        session()->flash('success', 'Saved.');
    }
    public function delete(int $id): void { CommunityCategory::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Community Categories</flux:heading>
        <flux:button variant="primary" wire:click="create" icon="plus">Add Category</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    @if($showForm)
        <flux:card class="max-w-lg mb-6"><div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4"><flux:heading>{{ $editingId ? 'Edit' : 'New' }} Category</flux:heading></div>
            <div><form wire:submit="save" class="space-y-3">
                <flux:field><flux:label>Name *</flux:label><flux:input wire:model="name" /><flux:error name="name" /></flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="2" /></flux:field>
                <div class="flex gap-2"><flux:button type="submit" variant="primary">Save</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Cancel</flux:button></div>
            </form></div>
        </flux:card>
    @endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column><flux:table.column>Communities</flux:table.column><flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($categories as $cat)
                <flux:table.row>
                    <flux:table.cell>{{ $cat->name }}</flux:table.cell>
                    <flux:table.cell>{{ $cat->communities_count }}</flux:table.cell>
                    <flux:table.cell><div class="flex gap-2"><flux:button size="sm" variant="ghost" wire:click="edit({{ $cat->id }})" icon="pencil" /><flux:button size="sm" variant="ghost" wire:click="delete({{ $cat->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" /></div></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="3" class="text-center text-zinc-400">No categories yet.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>



