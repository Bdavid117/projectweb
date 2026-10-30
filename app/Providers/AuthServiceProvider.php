<?php

namespace App\Providers;

use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\Student;
use App\Models\TutorRequest;
use App\Models\Tutorship;
use App\Policies\CommitteeRequestPolicy;
use App\Policies\ExperiencePolicy;
use App\Policies\StudentPolicy;
use App\Policies\TutorRequestPolicy;
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
        Experience::class => ExperiencePolicy::class,
        Student::class => StudentPolicy::class,
        TutorRequest::class => TutorRequestPolicy::class,
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
