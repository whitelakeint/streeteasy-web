<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeLog extends Model
{
    protected $table = 'scrape_logs';

    public $timestamps = false; // only `created_at` exists, set automatically

    protected $fillable = [
        'url_id',
        'property_name',
        'level',
        'event',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    public function scrapeUrl()
    {
        return $this->belongsTo(ScrapeUrl::class, 'url_id');
    }
}
