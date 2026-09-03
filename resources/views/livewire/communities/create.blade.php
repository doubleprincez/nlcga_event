<?php

use App\Models\Community;
use App\Models\CommunityCategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $title = '';
    public ?string $description = '';
    public ?string $restriction = 'public';
    public ?string $status = 'active';
    public ?int $community__category_id = null;

    public function save(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        Community::create([
            'title' => $this->title, 'description' => $this->description,
            'restriction' => $this->restriction, 'status' => $this->status,
            'community__category_id' => $this->community__category_id,
            'slug' => Str::slug($this->title) . '-' . time(),
        ]);
        session()->flash('success', 'Community created.');
        $this->redirect(route('communities.index'), navigate: true);
    }

    public function with(): array
    {
        return ['categories' => CommunityCategory::orderBy('name')->get()];
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('communities.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Add Community</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-lg space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Title *</flux:label><flux:input wire:model="title" /><flux:error name="title" /></flux:field>
                <flux:field><flux:label>Category</flux:label>
                    <flux:select wire:model="community__category_id">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach($categories as $cat)
                            <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Restriction</flux:label>
                    <flux:select wire:model="restriction">
                        <flux:select.option value="public">Public</flux:select.option>
                        <flux:select.option value="private">Private</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="3" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('communities.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>





