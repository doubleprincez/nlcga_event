<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Event Management') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
    <flux:sidebar sticky stashable class="bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-3">
            <flux:icon.calendar-days class="size-6 text-blue-600" />
            <flux:heading size="lg" class="font-bold text-blue-600">EventMS</flux:heading>
        </a>

        <flux:separator />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="home" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                Dashboard
            </flux:navlist.item>

            @if(auth()->user()->isAdmin())
            <flux:navlist.group heading="Members & Users" expandable :expanded="request()->routeIs('members.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('packages.*')">
                <flux:navlist.item icon="users" href="{{ route('members.index') }}" :current="request()->routeIs('members.*')">Members</flux:navlist.item>
                <flux:navlist.item icon="user-circle" href="{{ route('users.index') }}" :current="request()->routeIs('users.*')">Users</flux:navlist.item>
                <flux:navlist.item icon="shield-check" href="{{ route('roles.index') }}" :current="request()->routeIs('roles.*')">Roles</flux:navlist.item>
                <flux:navlist.item icon="cube" href="{{ route('packages.index') }}" :current="request()->routeIs('packages.*')">Packages</flux:navlist.item>
            </flux:navlist.group>
            @endif

            <flux:navlist.group heading="Events" expandable :expanded="request()->routeIs('events.*') || request()->routeIs('event-types.*') || request()->routeIs('registrations.*') || request()->routeIs('conference-members.*')">
                <flux:navlist.item icon="calendar" href="{{ route('events.index') }}" :current="request()->routeIs('events.*')">Events</flux:navlist.item>
                @if(auth()->user()->isAdmin())
                <flux:navlist.item icon="tag" href="{{ route('event-types.index') }}" :current="request()->routeIs('event-types.*')">Event Types</flux:navlist.item>
                <flux:navlist.item icon="clipboard-document-check" href="{{ route('registrations.index') }}" :current="request()->routeIs('registrations.*')">Registrations</flux:navlist.item>
                @endif
                <flux:navlist.item icon="user-group" href="{{ route('conference-members.index') }}" :current="request()->routeIs('conference-members.*')">Conference Members</flux:navlist.item>

                @if(auth()->user()->isAdmin())
                <flux:navlist.item icon="qr-code" href="{{ route('staff.scanner') }}" :current="request()->routeIs('staff.scanner')">QR Scanner</flux:navlist.item>
                    @else
                    <flux:navlist.item icon="qr-code" href="{{ route('scanner') }}" :current="request()->routeIs('scanner')">QR Scanner</flux:navlist.item>
                @endif

            </flux:navlist.group>

            @if(auth()->user()->isAdmin())
            <flux:navlist.group heading="Payments" expandable :expanded="request()->routeIs('payments.*') || request()->routeIs('membership-payments.*')">
                <flux:navlist.item icon="credit-card" href="{{ route('payments.index') }}" :current="request()->routeIs('payments.*')">Payments</flux:navlist.item>
                <flux:navlist.item icon="banknotes" href="{{ route('membership-payments.index') }}" :current="request()->routeIs('membership-payments.*')">Membership Payments</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group heading="Community" expandable :expanded="request()->routeIs('communities.*') || request()->routeIs('community-categories.*')">
                <flux:navlist.item icon="chat-bubble-left-right" href="{{ route('communities.index') }}" :current="request()->routeIs('communities.*')">Communities</flux:navlist.item>
                <flux:navlist.item icon="folder" href="{{ route('community-categories.index') }}" :current="request()->routeIs('community-categories.*')">Categories</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group heading="Content" expandable :expanded="request()->routeIs('news.*') || request()->routeIs('news-categories.*') || request()->routeIs('resources.*')">
                <flux:navlist.item icon="newspaper" href="{{ route('news.index') }}" :current="request()->routeIs('news.*')">News</flux:navlist.item>
                <flux:navlist.item icon="bookmark" href="{{ route('news-categories.index') }}" :current="request()->routeIs('news-categories.*')">News Categories</flux:navlist.item>
                <flux:navlist.item icon="document-text" href="{{ route('resources.index') }}" :current="request()->routeIs('resources.*')">Resources</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group heading="Communication" expandable :expanded="request()->routeIs('whatsapp-templates.*') || request()->routeIs('whatsapp-test') || request()->routeIs('email-templates.*') || request()->routeIs('bot-communications.*') || request()->routeIs('ai.*')">
                <flux:navlist.item icon="chat-bubble-oval-left" href="{{ route('whatsapp-templates.index') }}" :current="request()->routeIs('whatsapp-templates.*')">WhatsApp Templates</flux:navlist.item>
                <flux:navlist.item icon="paper-airplane" href="{{ route('whatsapp-test') }}" :current="request()->routeIs('whatsapp-test')">WhatsApp Tester</flux:navlist.item>
                <flux:navlist.item icon="envelope" href="{{ route('email-templates.index') }}" :current="request()->routeIs('email-templates.*')">Email Templates</flux:navlist.item>
                <flux:navlist.item icon="cpu-chip" href="{{ route('bot-communications.index') }}" :current="request()->routeIs('bot-communications.*')">Bot Communications</flux:navlist.item>
                <flux:navlist.item icon="adjustments-horizontal" href="{{ route('ai.training') }}" :current="request()->routeIs('ai.training')">AI Training</flux:navlist.item>
                <flux:navlist.item icon="chat-bubble-left-ellipsis" href="{{ route('ai.chat-tester') }}" :current="request()->routeIs('ai.chat-tester')">AI Chat Tester</flux:navlist.item>
            </flux:navlist.group>
            @endif

        </flux:navlist>

        <flux:spacer />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="user-circle" href="{{ route('profile.edit') }}" :current="request()->routeIs('profile.edit')" wire:navigate>
                {{ auth()->user()->name ?? 'My Profile' }}
            </flux:navlist.item>
            <flux:navlist.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}"
                x-data x-on:click.prevent="document.getElementById('logout-form').submit()">
                Logout
            </flux:navlist.item>
        </flux:navlist>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </flux:sidebar>

    <flux:main container>
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:heading>{{ config('app.name') }}</flux:heading>
        </flux:header>

        <div class="py-6">
            {{ $slot }}
        </div>
    </flux:main>

    @livewireScripts
    @fluxScripts
</body>
</html>
