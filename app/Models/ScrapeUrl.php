<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeUrl extends Model
{
    protected $table = 'scrape_urls';

    protected $fillable = [
        'name',
        'url',
        'is_active',
        'last_status',
        'last_scraped_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function properties()
    {
        return $this->hasMany(Property::class, 'url_id');
    }
}
