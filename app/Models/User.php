<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\Notification;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'streak_days',
        'last_studied_at',
        'firebase_uid',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_studied_at' => 'datetime',
        ];
    }

    /**
     * Định nghĩa quan hệ: User có nhiều Messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Định nghĩa quan hệ: User có nhiều Events
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return in_array($this->role, ['user', 'student', 'instructor'], true);
    }

    /**
     * Get all knowledge created by user
     */
    public function knowledges(): HasMany
    {
        return $this->hasMany(Knowledge::class);
    }

    /**
     * Get user's published knowledge
     */
    public function publishedKnowledges(): HasMany
    {
        return $this->hasMany(Knowledge::class)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get user's draft knowledge
     */
    public function draftKnowledges(): HasMany
    {
        return $this->hasMany(Knowledge::class)
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc');
    }

    /**
     * The documents that are related to this user.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'recent_activities')
            ->withTimestamps();
    }

    /**
     * The documents that this user has saved.
     */
    public function savedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'my_documents')
            ->using(MyDocument::class)
            ->withTimestamps();
    }

    /**
     * The uploads that belong to this user.
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    /**
     * One-to-one relation: user's log (quota, tokens, limits)
     */
    public function userLog(): HasOne
    {
        return $this->hasOne(UserLog::class);
    }

    /**
     * The reviews that belong to this user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The exams that belong to this user.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * The decks that belong to this user.
     */
    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class);
    }

     public function wallet(): HasOne
    {
        return $this->hasOne(\App\Models\Wallet::class)
            ->where('currency_code', 'COIN');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\WalletTransaction::class);
    }


    public function incrementStreak()
    {
        $today = now()->startOfDay();
        $lastStudied = $this->last_studied_at ? $this->last_studied_at->startOfDay() : null;

        if ($lastStudied && $lastStudied->isSameDay($today)) {
            return;
        }

        if ($lastStudied && $lastStudied->diffInDays($today) === 1) {
            $this->streak_days += 1;
        } else {
            $this->streak_days = 1;
        }

        $this->last_studied_at = now();
        $this->save();
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(\App\Models\Wallet::class);
    }

     public function coinBalance(): int
    {
        return $this->wallet?->balance ?? 0;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('plan')
            ->latest()
            ->first();
    }

    public function currentPlan(): Plan
    {
        return $this->activeSubscription()?->plan
            ?? Plan::where('slug', 'free')->first();
    }

    public function hasFeature(string $feature): bool
    {
        // $feature: 'pro', 'premium'
        $plan = $this->currentPlan();
        return match($feature) {
            'pro'     => in_array($plan->slug, ['pro', 'premium']),
            'premium' => $plan->slug === 'premium',
            default   => true,
        };
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }
    
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->whereNull('read_at')->latest();
    }

    public function scopeSearch($query, ?string $q)
    {
        return $q
            ? $query->where(fn ($sub) =>
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
              )
            : $query;
    }
 
    public function scopeOfRole($query, ?string $role)
    {
        return $role ? $query->where('role', $role) : $query;
    }
 
    public function scopeOfPlan($query, ?string $plan)
    {
        return $plan ? $query->where('plan', $plan) : $query;
    }
 
    public function scopeOfStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
