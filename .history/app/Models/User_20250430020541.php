<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Find or create a user by Google data
     */
    public static function findOrCreateGoogleUser($googleUser): User
    {
        $user = self::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if ($user) {
            // Update existing user with Google data if not already set
            if (!$user->google_id) {
                $user->google_id = $googleUser->id;
                $user->avatar = $googleUser->avatar;
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->save();
            }
            return $user;
        }

        // Create new user
        return self::create([
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'avatar' => $googleUser->avatar,
            'email_verified_at' => now(),
            'password' => null // Google users don't need password
        ]);
    }

    /**
     * Check if user has Google account linked
     */
    public function hasGoogleLinked(): bool
    {
        return !is_null($this->google_id);
    }

    /**
     * Get user's teams
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get user's personal tasks
     */
    public function personalTasks()
    {
        return $this->hasMany(PersonalTask::class, 'user_id');
    }

    /**
     * Get user's team task assignments
     */
    public function teamTaskAssignments()
    {
        return $this->hasMany(TeamTaskAssignment::class, 'user_id');
    }

    /**
     * Get user's devices
     */
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Get user's sync status
     */
    public function syncStatuses()
    {
        return $this->hasMany(SyncStatus::class);
    }

    /**
     * Get user's notification queue
     */
    public function notificationQueue()
    {
        return $this->hasMany(NotificationQueue::class);
    }

    /**
     * Get user's message reactions
     */
    public function messageReactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get user's settings
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get user's drafts
     */
    public function drafts()
    {
        return $this->hasMany(Draft::class);
    }

    /**
     * Get user's biometric authentication
     */
    public function biometricAuth()
    {
        return $this->hasMany(BiometricAuth::class);
    }
}
