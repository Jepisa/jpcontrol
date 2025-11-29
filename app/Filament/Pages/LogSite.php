<?php

namespace App\Filament\Pages;

use Exception;
use Filament\Pages\Page;
use Laravel\Forge\Forge;

class LogSite extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.logsite';

    protected static ?string $title = 'Site Logs';

    protected static ?string $slug = 'site-logs';

    public string $logs = '';

    public $selectedServer;

    public array $servers = [];

    public $selectedSite;

    public array $sites = [];

    public array $logFiles = [];

    public ?string $selectedLogFile = 'site-log';

    public int $lineLimit = 1000;

    public bool $showAllLines = false;

    public bool $errorAuthForge = false;

    public string $errorAuthForgeMessage = 'Error de autenticación con Forge';

    protected ?string $siteRootPath = null;

    public function mount(): void
    {
        try {
            $this->loadServers();
        } catch (Exception $e) {
            $this->errorAuthForge = true;
        }
    }

    protected function getForgeInstance(): Forge
    {
        return new Forge(config('services.forge.token'));
    }

    protected function loadServers(): void
    {
        $forge = $this->getForgeInstance();

        $this->servers = collect($forge->servers())->map(function ($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
            ];
        })->toArray();
    }

    public function loadSites(): void
    {
        if ($this->selectedServer) {
            $forge = $this->getForgeInstance();
            $this->sites = collect($forge->sites($this->selectedServer))->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'serverId' => $site->serverId,
                ];
            })->toArray();
        }

        $this->resetLogState();
    }

    protected function resetLogState(): void
    {
        $this->selectedSite = null;
        $this->selectedLogFile = 'site-log';
        $this->logFiles = [];
        $this->logs = '';
        $this->siteRootPath = null;
    }

    public function loadLogFiles(): void
    {
        if (! $this->selectedServer || ! $this->selectedSite) {
            return;
        }

        $forge = $this->getForgeInstance();
        $site = $forge->site($this->selectedServer, $this->selectedSite);
        $this->siteRootPath = $site->attributes['root_path'] ?? "/home/forge/{$site->name}";

        $this->logFiles = [
            ['value' => 'site-log', 'label' => 'Site Log (default)'],
        ];

        $command = 'find storage/logs -type f -name "*.log" 2>/dev/null | sort -r';
        $output = $this->executeAndWaitCommand($command);

        if ($output) {
            $files = array_filter(explode("\n", trim($output)));
            foreach ($files as $file) {
                $file = trim($file);
                if (empty($file)) {
                    continue;
                }

                $label = str_replace('storage/logs/', '', $file);

                $this->logFiles[] = [
                    'value' => $file,
                    'label' => $label,
                ];
            }
        }

        $this->selectedLogFile = 'site-log';
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        if (! $this->selectedServer || ! $this->selectedSite) {
            return;
        }

        if ($this->selectedLogFile === 'site-log') {
            $this->loadSiteLog();
        } else {
            $this->loadLogFileContent();
        }
    }

    protected function loadSiteLog(): void
    {
        $forge = $this->getForgeInstance();
        $site = $forge->site($this->selectedServer, $this->selectedSite);

        try {
            $output = $site->siteLog()['content'] ?? 'No se pudo cargar los logs';
            $this->logs = $this->removeAnsiSequences($output);
        } catch (\Laravel\Forge\Exceptions\NotFoundException $e) {
            $this->logs = 'No se pudo cargar los logs: '.$e->getMessage();
        }
    }

    protected function loadLogFileContent(): void
    {
        if (! $this->isValidLogPath($this->selectedLogFile)) {
            $this->logs = 'Ruta de archivo no válida';

            return;
        }

        $path = escapeshellarg($this->selectedLogFile);

        $command = $this->showAllLines
            ? "cat {$path}"
            : "tail -n {$this->lineLimit} {$path}";

        $output = $this->executeAndWaitCommand($command);

        if ($output !== null) {
            $this->logs = $this->removeAnsiSequences($output);
        } else {
            $this->logs = 'Error al cargar el archivo de log (timeout o error de ejecución)';
        }
    }

    protected function isValidLogPath(string $path): bool
    {
        if (! str_starts_with($path, 'storage/logs/')) {
            return false;
        }

        if (str_contains($path, '..')) {
            return false;
        }

        return true;
    }

    protected function executeAndWaitCommand(string $command, int $maxWaitSeconds = 15): ?string
    {
        $forge = $this->getForgeInstance();

        try {
            $siteCommand = $forge->executeSiteCommand(
                $this->selectedServer,
                $this->selectedSite,
                ['command' => $command]
            );

            $startTime = time();

            while (true) {
                $result = $forge->getSiteCommand(
                    $this->selectedServer,
                    $this->selectedSite,
                    $siteCommand->id
                );

                $commandResult = $result[0] ?? null;

                if ($commandResult && $commandResult->status === 'finished') {
                    return $commandResult->output ?? '';
                }

                if (time() - $startTime > $maxWaitSeconds) {
                    return null;
                }

                usleep(500000);
            }
        } catch (Exception $e) {
            return null;
        }
    }

    public function updatedShowAllLines(): void
    {
        $this->loadLogs();
    }

    public function updatedLineLimit(): void
    {
        if (! $this->showAllLines && $this->selectedLogFile !== 'site-log') {
            $this->loadLogs();
        }
    }

    public function removeAnsiSequences(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = preg_replace('/\x1B\[[0-9;]*m/', '', $text);

        return $text;
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
