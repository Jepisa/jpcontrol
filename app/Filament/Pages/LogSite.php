<?php

namespace App\Filament\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Laravel\Forge\Forge;

class LogSite extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.logsite';
    protected static ?string $title = 'Site Logs';
    protected static ?string $slug = 'site-logs';

    public $logs = '';
    public $selectedServer;
    public $servers = [];
    public $selectedSite;
    public $sites = [];

    public bool $errorAuthForge = false;
    public string $errorAuthForgeMessage = 'Error de autenticación con Forge';

    public function mount()
    {
        try {
            $this->loadServers();
        } catch (Exception $e) {
            $this->errorAuthForge = true;
        }
    }

    protected function getForgeInstance()
    {
        return new Forge(env('FORGE_API_TOKEN'));
    }

    protected function loadServers()
    {
        $forge = $this->getForgeInstance();

        $this->servers = collect($forge->servers())->map(function ($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
            ];
        })->toArray();
    }

    public function loadSites()
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

        // reset logs
        $this->selectedSite = null;
        $this->logs = '';
    }

    public function loadLogs()
    {
        if ($this->selectedServer && $this->selectedSite) {
            $forge = $this->getForgeInstance();
            $site = $forge->site($this->selectedServer, $this->selectedSite);

            try {
                $output = $site->siteLog()['content'] ?? 'No Se puedo cargar los logs';
                $this->logs = $this->removeAnsiSequences($output);
            } catch (\Laravel\Forge\Exceptions\NotFoundException $e) {
                $this->logs = 'No Se puedo cargar los logs: ' . $e->getMessage();
            }
        }
    }

    function removeAnsiSequences($text) {
        if ($text === null) {
            return '';
        }
        // Regex para eliminar secuencias ANSI
        $text = preg_replace('/\x1B\[[0-9;]*m/', '', $text);
        return $text;
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
