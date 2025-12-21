<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'password',
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
}
