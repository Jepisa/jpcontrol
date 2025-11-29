<x-filament-panels::page>
    @if ($errorAuthForge)
        <div class="p-4 rounded mb-4">
            <span class="dark:text-red-500">{{ $errorAuthForgeMessage }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-filament::input.wrapper class="p-2">
            <div class="flex justify-between">
                <label for="server">Server:</label>
                <x-filament::loading-indicator wire:loading wire:target="loadSites" class="h-5 w-5" />
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
                <x-filament::loading-indicator wire:loading wire:target="loadLogFiles" class="h-5 w-5" />
            </div>
            <x-filament::input.select id="site" wire:model="selectedSite" wire:change="loadLogFiles">
                <option value="">Select Site</option>
                @foreach($sites as $site)
                    <option value="{{ $site['id'] }}">{{ $site['name'] }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @if ($selectedSite && count($logFiles) > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <x-filament::input.wrapper class="p-2 md:col-span-2">
                <div class="flex justify-between">
                    <label for="logFile">Log File:</label>
                    <x-filament::loading-indicator wire:loading wire:target="loadLogs" class="h-5 w-5" />
                </div>
                <x-filament::input.select id="logFile" wire:model="selectedLogFile" wire:change="loadLogs">
                    @foreach($logFiles as $file)
                        <option value="{{ $file['value'] }}">{{ $file['label'] }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="p-2">
                <label for="lineLimit">Lines:</label>
                <x-filament::input
                    type="number"
                    id="lineLimit"
                    wire:model.blur="lineLimit"
                    :disabled="$showAllLines || $selectedLogFile === 'site-log'"
                    min="100"
                    max="10000"
                    step="100"
                />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="p-2">
                <label for="showAll" class="block mb-2">Show All:</label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <x-filament::input.checkbox
                        id="showAll"
                        wire:model.live="showAllLines"
                        :disabled="$selectedLogFile === 'site-log'"
                    />
                    <span class="text-sm {{ $selectedLogFile === 'site-log' ? 'text-gray-400' : '' }}">
                        Ver todo
                    </span>
                </label>
            </x-filament::input.wrapper>
        </div>
    @endif

    <div class="mt-4">
        <textarea
            readonly
            class="w-full h-96 p-4 font-mono text-sm rounded-lg border-0 resize-y"
            style="background-color: #1a1a2e; color: #16f2b3; min-height: 400px;"
        >{!! $logs !!}</textarea>
    </div>
</x-filament-panels::page>
