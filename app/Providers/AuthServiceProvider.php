<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\CommitteeRequest;
use App\Models\Tutorship;
use App\Policies\CommitteeRequestPolicy;
use App\Policies\TutorshipPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CommitteeRequest::class => CommitteeRequestPolicy::class,
        Tutorship::class => TutorshipPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
