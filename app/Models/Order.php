<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function requestedOrder()
    {
        return $this->hasMany(RequestedOrder::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
    public function grade()
    {
        return $this->belongsTo('App\Models\Grade');
    }
    public function priority()
    {
        return $this->belongsTo('App\Models\Priority');
    }
    public function design()
    {
        return $this->belongsTo('App\Models\Design');
    }
    public function quality()
    {
        return $this->belongsTo('App\Models\Quality');
    }
    public function status()
    {
        return $this->belongsTo('App\Models\Status');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }
    public function images()
    {
        return $this->hasMany(Images::class);
    }
    public function histories()
    {
        return $this->hasMany(OrderHistory::class);
    }
    public function approvedOrder()
    {
        return $this->hasOne(ApprovedOrder::class);
    }

    public function orderHistories()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }

    public function fetchRemarkByStatus($statusId)
    {
        $remark = $this->orderHistories()
            ->where('status_id', $statusId)
            ->first();

        return $remark ? $remark->content : null;
    }

    public function fetchArrivalDate($statusId)
    {
        $data = $this->orderHistories()
            ->where('status_id', $statusId)
            ->first();

        return $data ? $data->created_at : null;
    }

    // public function fetchCancleRemark($statusId)
    // {
    //     $data = $this->orderHistories()
    //         ->where('status_id', $statusId)
    //         ->first();

    //     return $data ? $data->created_at : null;
    // }
}
