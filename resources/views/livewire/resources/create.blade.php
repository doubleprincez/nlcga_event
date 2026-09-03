<?php

use App\Models\Resource;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $title = '';
    public ?string $description = '';
    public ?string $file_url = '';
    public ?string $preview_link = '';
    public ?string $post_type = 'resource';
    public ?string $status = 'active';
    public ?string $conference_year = '';

    public function mount(): void {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.'); $this->conference_year = date('Y'); }

    public function save(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        Resource::create($this->only(['title', 'description', 'file_url', 'preview_link', 'post_type', 'status', 'conference_year']));
        session()->flash('success', 'Resource created.');
        $this->redirect(route('resources.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('resources.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Add Resource</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-2xl space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Title *</flux:label><flux:input wire:model="title" /><flux:error name="title" /></flux:field>
                <flux:field><flux:label>Post Type</flux:label>
                    <flux:select wire:model="post_type">
                        <flux:select.option value="resource">Resource</flux:select.option>
                        <flux:select.option value="document">Document</flux:select.option>
                        <flux:select.option value="video">Video</flux:select.option>
                        <flux:select.option value="presentation">Presentation</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Conference Year</flux:label><flux:input wire:model="conference_year" /></flux:field>
                <flux:field><flux:label>File URL</flux:label><flux:input wire:model="file_url" placeholder="https://..." /></flux:field>
                <flux:field><flux:label>Preview Link</flux:label><flux:input wire:model="preview_link" placeholder="https://..." /></flux:field>
                <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="4" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Save</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('resources.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




