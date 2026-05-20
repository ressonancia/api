<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
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

    protected $appends = [
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:sP');
    }

    public function getAvatarAttribute(): string
    {
        return 'https://www.gravatar.com/avatar/'
            .hash('sha256', strtolower(trim($this->email))).'?s=40';
    }

    public static function booted(): void
    {
        static::created(function (User $user) {
            $user->ensureOwnedOrganization();
        });
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ensureOwnedOrganization(): void
    {
        if ($this->organizations()->wherePivot('role', Organization::ROLE_OWNER)->exists()) {
            return;
        }

        $organization = Organization::create([
            'name' => 'Organization '.Str::upper(Str::random(8)),
        ]);

        $this->organizations()->attach(
            $organization->id,
            ['role' => Organization::ROLE_OWNER]
        );
    }

    public function ownsOrganization(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivot('role', Organization::ROLE_OWNER)
            ->exists();
    }

    public function isOrganizationAdmin(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivotIn('role', [
                Organization::ROLE_OWNER,
                Organization::ROLE_ADMIN,
            ])
            ->exists();
    }

    public function isOrganizationMember(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }
}
