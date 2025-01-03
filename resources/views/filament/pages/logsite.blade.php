<x-filament-panels::page>
    @if ($errorAuthForge)
        <div class="p-4 rounded mb-4">
            <span class="dark:text-red-500 ">{{ $errorAuthForgeMessage }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-filament::input.wrapper class="p-2">
            <div class="flex justify-between">
                <label for="server">Server:</label>
                <x-filament::loading-indicator wire:loading wire:target="loadSites" class="text-right h-5 w-5" />
            </div>
            <x-filament::input.select id="server" wire:model="selectedServer" wire:change="loadSites">
                <option value="">Select Server</option>
                @foreach($servers as $server)
                    <option value="{{ $server['id'] }}">{{ $server['name'] }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <x-filament::input.wrapper class="p-2">
            <div class="flex justify-between">
                <label for="site">Site:</label>
                <x-filament::loading-indicator wire:loading wire:target="loadLogs" class="text-right h-5 w-5" />
            </div>
            <x-filament::input.select id="site" wire:model="selectedSite" wire:change="loadLogs">
                <option value="">Select Site</option>
                @foreach($sites as $site)
                    <option value="{{ $site['id'] }}">{{ $site['name'] }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <textarea readonly style="background-color: black; color: white; font-family: monospace; padding: 10px; border-radius: 5px; white-space: pre; width: 100%; height: 400px; border: none; overflow: auto; resize: vertical;">
        {!! $logs !!}
    </textarea>
</x-filament-panels::page>
