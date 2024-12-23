<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReportRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }
}
