<?php

namespace App\Models;

use App\Models\CalendarNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
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
        'role',
        'password',
        'profile_photo_path',
        'position_id',
        'branch_id',
        'department_id',
        'location_id',
        'suspended'
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
        'suspended' => 'boolean',
        'google_token_expires_at' => 'datetime',
        'role' => 'string',
    ];

    /**
     * Get the user that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function position()
    {
        return $this->belongsTo('App\Models\Position');
    }
    public function comments()
    {
        return $this->belongsToMany(Comment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function noteTitles(): HasMany
    {
        return $this->hasMany(NoteTitle::class, 'created_by');
    }

    public function dailyNotes(): HasMany
    {
        return $this->hasMany(DailyNote::class, 'created_by');
    }

    public function noteMessages(): HasMany
    {
        return $this->hasMany(NoteMessage::class);
    }

    public function noteMessageReads(): HasMany
    {
        return $this->hasMany(NoteMessageRead::class);
    }

    public function readNoteMessages(): BelongsToMany
    {
        return $this->belongsToMany(NoteMessage::class, 'note_message_reads', 'user_id', 'note_message_id')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function completedDailyNotes(): HasMany
    {
        return $this->hasMany(DailyNote::class, 'completed_by');
    }

    public function dailyNoteAcknowledgements(): HasMany
    {
        return $this->hasMany(DailyNoteAcknowledgement::class);
    }

    public function acknowledgedDailyNotes(): BelongsToMany
    {
        return $this->belongsToMany(DailyNote::class, 'daily_note_acknowledgements', 'user_id', 'note_id')
            ->withPivot(['acknowledged_at'])
            ->withTimestamps();
    }

    public function psiProducts()
    {
        return $this->hasMany(PsiProduct::class);
    }

    public function psiPrices()
    {
        return $this->hasMany(PsiPrice::class);
    }

    public function psiOrders()
    {
        return $this->hasMany(PsiOrder::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function realSales()
    {
        return $this->hasMany(RealSale::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function dailyReportRecords()
    {
        return $this->hasMany(DailyReportRecord::class);
    }

    public function assignedTodos()
    {
        return $this->hasMany(TodoList::class, 'assigned_user_id');
    }

    public function createdTodos()
    {
        return $this->hasMany(TodoList::class, 'created_by_user_id');
    }

    public function taskComments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function googleCalendarAccount(): HasOne
    {
        return $this->hasOne(GoogleCalendarAccount::class);
    }

    public function calendarNotifications()
    {
        return $this->hasMany(CalendarNotification::class);
    }

    public function calendarEventGoogleCopies()
    {
        return $this->hasMany(\App\Models\Calendar\CalendarEventGoogleCopy::class);
    }

    public function whiteboardContents()
    {
        return $this->hasMany(WhiteboardContent::class, 'created_by');
    }

    public function whiteboardDecisions()
    {
        return $this->hasMany(WhiteboardDecision::class, 'created_by');
    }

    public function whiteboardReadReports()
    {
        return $this->hasMany(WhiteboardReport::class, 'read_by_user_id');
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return '/storage/' . ltrim($this->profile_photo_path, '/');
        }

        return asset('images/admin-icon.png');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
