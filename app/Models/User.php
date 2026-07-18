<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Enums\ProfileVisibility;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property Carbon|null $username_changed_at
 * @property string $email
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property string|null $country
 * @property string|null $region
 * @property ProfileVisibility $profile_visibility
 * @property Carbon|null $terms_accepted_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'current_team_id',
    'bio',
    'avatar_path',
    'country',
    'region',
    'profile_visibility',
    'terms_accepted_at',
    'username_changed_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'username_changed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'profile_visibility' => ProfileVisibility::class,
        ];
    }

    public function canChangeUsername(): bool
    {
        return $this->username_changed_at === null
            || Carbon::parse($this->username_changed_at)->addDays(30)->lessThanOrEqualTo(now());
    }

    public function nextUsernameChangeAllowedAt(): ?Carbon
    {
        return $this->username_changed_at === null
            ? null
            : Carbon::parse($this->username_changed_at)->addDays(30);
    }
}
