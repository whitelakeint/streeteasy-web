<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $table = 'properties';

    // Python's db_setup.py owns this schema — only 'scraped_at' exists,
    // not Laravel's default created_at/updated_at columns. Disable auto-timestamps.
    public $timestamps = false;

    protected $fillable = [
        'url_id',
        'scrape_date',
        'property_name',
        'rent',
        'beds',
        'beds_no',
        'baths',
        'baths_no',
        'area',
        'listing_url',
        'listed_by',
        'availability',
        'specials',
        'original',
    ];

    protected $casts = [
        'scrape_date' => 'date',
        'rent' => 'integer',
        'beds_no' => 'integer',
        'baths_no' => 'decimal:1',
        'original' => 'boolean',
    ];

    public function scrapeUrl()
    {
        return $this->belongsTo(ScrapeUrl::class, 'url_id');
    }
}
