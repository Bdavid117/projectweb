<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => Role::class,
        'active' => 'boolean',
    ];

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => Str::lower(trim($value)));
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function tutorAssignments(): HasMany
    {
        return $this->hasMany(TutorAssignment::class, 'tutor_id');
    }

    public function initials(): string
    {
        $name = $this->name;

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            $name = strtok($first, ' ').' '.strtok($last, ' ');
        }

        return collect(preg_split('/\s+/', trim($name)))
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /** @return array{initials: string, name: string, role: string} */
    public function viewData(): array
    {
        return [
            'initials' => $this->initials(),
            'name' => $this->name,
            'role' => $this->role->label(),
        ];
    }
}
