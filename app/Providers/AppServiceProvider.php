<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Application en français : dates, mois et jours toujours en français, quel que soit APP_LOCALE du .env
        app()->setLocale('fr');
        \Carbon\Carbon::setLocale('fr');
        \Carbon\CarbonImmutable::setLocale('fr');

        // Pagination aux couleurs de l'application (horizontale, responsive)
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.soumafe');

        // Documents du parc : alias courts et stables pour les opérations documentées (colonne documentable_type)
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(
            collect(\App\Models\Document::TYPES)->map(fn($t) => $t[0])->all()
        );
    }
}
