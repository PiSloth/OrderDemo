<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTaxonomy extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_key',
        'code',
        'title',
        'icon',
        'color_hex',
        'default_template',
        'metadata',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'default_template' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];
}
