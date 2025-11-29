<?php

namespace App\Providers;

use App\Services\JiraService;
use App\Services\SlackService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SlackService::class, function ($app) {
            return new SlackService(
                webhookUrl: config('services.slack.webhook_url'),
                channel: config('services.slack.tickets_channel'),
            );
        });

        $this->app->singleton(JiraService::class, function ($app) {
            return new JiraService(
                jiraUrl: config('services.jira.url'),
                email: config('services.jira.email'),
                apiToken: config('services.jira.api_token'),
                projectKey: config('services.jira.project_key'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
