<?php

use App\Models\News;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $title = '';
    public ?string $short_description = '';
    public ?string $description = '';
    public ?string $display_status = 'draft';
    public ?string $post_type = 'news';

    public function save(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        News::create([
            'title' => $this->title, 'short_description' => $this->short_description,
            'description' => $this->description, 'display_status' => $this->display_status,
            'post_type' => $this->post_type, 'slug' => Str::slug($this->title) . '-' . time(),
        ]);
        session()->flash('success', 'News created.');
        $this->redirect(route('news.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('news.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Add News</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-2xl space-y-4">
        <flux:card>
            <div class="space-y-4">
                <flux:field><flux:label>Title *</flux:label><flux:input wire:model="title" /><flux:error name="title" /></flux:field>
                <flux:field><flux:label>Post Type</flux:label>
                    <flux:select wire:model="post_type">
                        <flux:select.option value="news">News</flux:select.option>
                        <flux:select.option value="blog">Blog</flux:select.option>
                        <flux:select.option value="announcement">Announcement</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Status</flux:label>
                    <flux:select wire:model="display_status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field><flux:label>Short Description</flux:label><flux:textarea wire:model="short_description" rows="2" /></flux:field>
                <flux:field><flux:label>Full Content</flux:label><flux:textarea wire:model="description" rows="8" /></flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Publish</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('news.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>





