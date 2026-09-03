<?php

use App\Models\NewsCategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $title = '';
    public ?string $description = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public function with(): array { return ['categories' => NewsCategory::latest()->get()]; }
    public function create(): void { $this->reset(['title', 'description', 'editingId']); $this->showForm = true; }
    public function edit(int $id): void { $c = NewsCategory::findOrFail($id); $this->editingId = $id; $this->title = $c->title; $this->description = $c->description ?? ''; $this->showForm = true; }
    public function save(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        $data = ['title' => $this->title, 'description' => $this->description];
        $this->editingId ? NewsCategory::findOrFail($this->editingId)->update($data) : NewsCategory::create($data);
        $this->reset(['title', 'description', 'editingId', 'showForm']);
        session()->flash('success', 'Saved.');
    }
    public function delete(int $id): void { NewsCategory::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">News Categories</flux:heading>
        <flux:button variant="primary" wire:click="create" icon="plus">Add Category</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    @if($showForm)
        <flux:card class="max-w-lg mb-6"><div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4"><flux:heading>{{ $editingId ? 'Edit' : 'New' }} Category</flux:heading></div>
            <div><form wire:submit="save" class="space-y-3">
                <flux:field><flux:label>Title *</flux:label><flux:input wire:model="title" /><flux:error name="title" /></flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="2" /></flux:field>
                <div class="flex gap-2"><flux:button type="submit" variant="primary">Save</flux:button><flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Cancel</flux:button></div>
            </form></div>
        </flux:card>
    @endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Title</flux:table.column><flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($categories as $cat)
                <flux:table.row>
                    <flux:table.cell>{{ $cat->title }}</flux:table.cell>
                    <flux:table.cell><div class="flex gap-2"><flux:button size="sm" variant="ghost" wire:click="edit({{ $cat->id }})" icon="pencil" /><flux:button size="sm" variant="ghost" wire:click="delete({{ $cat->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" /></div></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="2" class="text-center text-zinc-400">No categories yet.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>



