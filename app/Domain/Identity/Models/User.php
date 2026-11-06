<?php

namespace App\Domain\Identity\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Contracts\HasLocationAccess;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocationAccess
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

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

    /** @return BelongsToMany<Location, $this> */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user')->withTimestamps();
    }

    /** @return list<int> ID lokasi yang boleh diakses user ini (dasar LocationScope, ADR-003). */
    public function locationIds(): array
    {
        return $this->locations()->pluck('locations.id')->map(fn ($id) => (int) $id)->all();
    }
}
