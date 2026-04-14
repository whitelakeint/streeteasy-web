@extends('layouts.app')
@section('title', 'Dashboard - StreetEasy Admin')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
<script>
  const activityData = @json($activityData);
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('activity-chart');
    if (!ctx) return;

    const labels = activityData.map(d => d.date);
    const values = activityData.map(d => d.count);

    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Properties',
          data: values,
          borderColor: '#2563EB',
          backgroundColor: 'rgba(37, 99, 235, 0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.25,
          pointRadius: 0,
          pointHoverRadius: 5,
          pointHoverBackgroundColor: '#2563EB',
          pointHoverBorderColor: '#fff',
          pointHoverBorderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0F172A',
            titleColor: '#fff',
            bodyColor: '#E2E8F0',
            padding: 10,
            cornerRadius: 6,
            displayColors: false,
            callbacks: {
              title: (items) => {
                const d = new Date(items[0].label);
                return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
              },
              label: (item) => item.parsed.y + ' properties'
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: '#94A3B8',
              font: { size: 10 },
              maxTicksLimit: 6,
              callback: function(val, i) {
                const label = this.getLabelForValue(val);
                const d = new Date(label);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#F1F5F9' },
            border: { display: false },
            ticks: { color: '#94A3B8', font: { size: 10 }, precision: 0 }
          }
        }
      }
    });
  });
</script>
@endpush

@section('content')
@php
  $propDelta = $propertiesToday - $propertiesYesterday;
  $maxActivity = max(array_map(fn($d) => $d['count'], $activityData)) ?: 1;
@endphp
<div class="p-8 max-w-[1400px]">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500 mt-1">Overview of your scraping pipeline</p>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-xs text-slate-400">Last updated: {{ now()->diffForHumans() }}</span>
      <form method="POST" action="{{ route('scrape.run') }}">
        @csrf
        <button class="bg-primary text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary/90 transition inline-flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">play_arrow</span>
          Run Scrape Now
        </button>
      </form>
    </div>
  </div>

  {{-- KPI cards --}}
  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Active URLs</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">{{ $activeUrls }}</div>
      <div class="text-xs text-slate-400 mt-1">{{ $scrapedToday }} scraped today</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Properties Today</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">{{ number_format($propertiesToday) }}</div>
      <div class="text-xs mt-1 {{ $propDelta >= 0 ? 'text-green-600' : 'text-red-600' }}">
        {{ ($propDelta >= 0 ? '+' : '') . $propDelta }} vs yesterday
      </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Total Tracked</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">{{ number_format($totalTracked) }}</div>
      <div class="text-xs text-slate-400 mt-1">all-time rows</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Last Scrape</div>
      <div class="text-xl font-semibold text-slate-900 mt-2">
        {{ $lastScrape?->last_scraped_at?->diffForHumans() ?? 'Never' }}
      </div>
      <div class="text-xs mt-1 flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full {{ $lastScrape?->last_status === 'completed' ? 'bg-green-500' : 'bg-slate-400' }}"></span>
        <span class="text-slate-500">Status: {{ ucfirst($lastScrape?->last_status ?? 'none') }}</span>
      </div>
    </div>
  </div>

  {{-- Chart + recent --}}
  <div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-1">Properties Captured Per Day</h3>
      <p class="text-xs text-slate-500 mb-4">Total rows inserted into the properties table each day (last 30 days)</p>
      <div class="h-48">
        <canvas id="activity-chart"></canvas>
      </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-1">Recent Scrapes</h3>
      <p class="text-xs text-slate-500 mb-4">Last 5 completed runs</p>
      <div class="space-y-3">
        @forelse($recentScrapes as $r)
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-3">
              <span class="w-1.5 h-1.5 rounded-full {{ $r->last_status === 'completed' ? 'bg-green-500' : ($r->last_status === 'failed' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
              <span class="font-medium text-slate-800">{{ $r->name }}</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs px-2 py-0.5 rounded-full {{ $r->last_status === 'completed' ? 'bg-green-50 text-green-700' : ($r->last_status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                {{ ucfirst($r->last_status) }}
              </span>
              <span class="text-xs text-slate-400">{{ $r->last_scraped_at?->diffForHumans() }}</span>
            </div>
          </div>
        @empty
          <p class="text-sm text-slate-400">No scrapes yet.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
