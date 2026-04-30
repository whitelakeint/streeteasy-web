<?php

namespace App\Console\Commands;

use App\Mail\DailyScrapeReport;
use App\Models\Property;
use App\Models\ScrapeLog;
use App\Models\ScrapeUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendScrapeReportCommand extends Command
{
    protected $signature = 'scrape:report {--date= : Date to report on (default: today)}';
    protected $description = 'Send the daily scrape summary report via email';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $dateStr = $date->format('m-d-Y');

        $recipient = config('services.scraper.report_email');
        if (!$recipient) {
            $this->error('No report email configured. Set SCRAPER_REPORT_EMAIL in .env');
            return self::FAILURE;
        }

        $totalUrls   = ScrapeUrl::where('is_active', 1)->count();
        $completed   = ScrapeUrl::where('last_status', 'completed')
            ->whereDate('last_scraped_at', $date)->count();
        $failed      = ScrapeUrl::where('last_status', 'failed')
            ->whereDate('last_scraped_at', $date)->count();
        $notScraped  = $totalUrls - $completed - $failed;

        $propertiesScraped = Property::whereDate('scrape_date', $date)
            ->where('original', 1)->count();

        $buildingsScraped = Property::whereDate('scrape_date', $date)
            ->where('original', 1)
            ->distinct('url_id')->count('url_id');

        $errors = ScrapeLog::where('level', 'error')
            ->whereDate('created_at', $date)
            ->limit(10)
            ->get(['event', 'message', 'created_at']);

        $warnings = ScrapeLog::where('level', 'warn')
            ->whereDate('created_at', $date)->count();

        $urlDetails = ScrapeUrl::where('is_active', 1)
            ->orderBy('name')
            ->get(['name', 'last_status', 'last_scraped_at'])
            ->map(fn ($u) => [
                'name'       => $u->name,
                'status'     => $u->last_status ?: 'never',
                'scraped_at' => $u->last_scraped_at?->format('m-d-Y H:i:s'),
            ])->all();

        $report = [
            'date'               => $dateStr,
            'total_urls'         => $totalUrls,
            'completed'          => $completed,
            'failed'             => $failed,
            'not_scraped'        => $notScraped,
            'properties_scraped' => $propertiesScraped,
            'buildings_scraped'  => $buildingsScraped,
            'errors'             => $errors->toArray(),
            'warning_count'      => $warnings,
            'url_details'        => $urlDetails,
        ];

        Mail::to($recipient)->send(new DailyScrapeReport($report));

        $this->info("Report sent to {$recipient} for {$dateStr}");
        return self::SUCCESS;
    }
}
