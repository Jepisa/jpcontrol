<?php

namespace App\Filament\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Laravel\Forge\Forge;

class Logs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.logs';

    protected static ?string $title = 'Logs Deployments';

    // Cambiar slug
    protected static ?string $slug = 'logs-deployments';

    public $defaultAction = 'onboarding';

    public $logs = '';

    public $selectedProject;

    public $projects = [];

    public $selectedServer;

    public $servers = [];

    public $selectedSite;

    public $sites = [];

    public $selectedDeployment;

    public $deployments = [];

    public bool $errorAuthForge = false;

    public string $errorAuthForgeMessage = 'Error de autenticación con Forge';

    public function mount()
    {
        try {
            $this->loadServers();
        } catch (Exception $e) {
            $this->errorAuthForge = true;
            // $this->errorAuthForgeMessage = $e->getMessage();
        }
    }

    protected function getForgeInstance(): Forge
    {
        return new Forge(config('services.forge.token'));
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

        // solo dejar el que tenga name == 'creatienda-qa'
        // $this->servers = collect($this->servers)->filter(function ($server) {
        //     return $server['name'] == 'creatienda-qa';
        // })->toArray();
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

        // reset deployments and logs
        $this->selectedSite = null;
        $this->deployments = [];
        $this->selectedDeployment = null;
        $this->logs = '';
    }

    public function loadDeployments()
    {
        if ($this->selectedServer && $this->selectedSite) {
            $forge = $this->getForgeInstance();
            $site = $forge->site($this->selectedServer, $this->selectedSite);
            $this->deployments = collect($site->getDeploymentHistory()['deployments'])->map(function ($deployment) {
                return [
                    'id' => $deployment['id'],
                    'name' => $deployment['commit_message'],
                    'started_at' => $deployment['started_at'],
                ];
            })->toArray();
        }

        // reset
        $this->logs = '';
        $this->selectedDeployment = null;
    }

    public function loadLogs()
    {
        if ($this->selectedServer && $this->selectedSite && $this->selectedDeployment) {
            $forge = $this->getForgeInstance();
            $site = $forge->site($this->selectedServer, $this->selectedSite);
            try {
                $output = $site->getDeploymentHistoryOutput($this->selectedDeployment)['output'] ?? 'No Se puedo cargar los logs';
                $this->logs = $this->removeAnsiSequences($output);
            } catch (\Laravel\Forge\Exceptions\NotFoundException $e) {
                $this->logs = 'No Se puedo cargar los logs: '.$e->getMessage();
            }
        }
    }

    public function removeAnsiSequences($text)
    {
        if ($text === null) {
            return '';
        }
        // Regex para eliminar secuencias ANSI
        $text = preg_replace('/\x1B\[[0-9;]*m/', '', $text);

        return $text;
    }

    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->modalHeading('Welcome Che')
            ->visible(fn (): bool => false);
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         StatsOverviewWidget::class
    //     ];
    // }

    public static function canAccess(): bool
    {
        return true; // auth()->user()->can('view logs');
    }

    // public function deleteAction(): Action
    // {
    //     return Action::make('delete')
    //         ->requiresConfirmation()
    //         ->action(fn (array $arguments) => dd($arguments['something']));
    // }

    // public function testAction(): Action
    // {
    //     return Action::make('test')
    //         ->requiresConfirmation()
    //         ->action(function (array $arguments) {
    //             dd('Test action called', $arguments);
    //         });
    // }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('edit')
    //             ->url('logs/{record}/edit'),
    //         Action::make('delete')
    //             ->requiresConfirmation()
    //             ->action(fn () => dd('Delete action called')),
    //     ];
    // }
}
