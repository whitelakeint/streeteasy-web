@extends('layouts.app')
@section('title', 'Scrape Control - StreetEasy Admin')
@section('content')
<div class="p-8 max-w-[1400px]">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Scrape Control</h1>
      <p class="text-sm text-slate-500 mt-1">Trigger scrapes and monitor extension health</p>
    </div>
  </div>

  {{-- Status cards --}}
  <div class="grid grid-cols-3 gap-4 mb-6">
    @php
      $ext = $status['extension'] ?? 'unknown';
      $scr = $status['scraper'] ?? 'unknown';
    @endphp
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium mb-2">Extension</div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full {{ $ext === 'connected' ? 'bg-green-500' : 'bg-slate-400' }}"></span>
        <span class="text-lg font-semibold text-slate-900 capitalize">{{ $ext }}</span>
      </div>
      <div class="text-xs text-slate-500 mt-2">Chrome Extension v1.0.0</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium mb-2">Backend Server</div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full {{ $online ? 'bg-green-500' : 'bg-red-500' }}"></span>
        <span class="text-lg font-semibold text-slate-900">{{ $online ? 'Running' : 'Offline' }}</span>
      </div>
      <div class="text-xs text-slate-500 mt-2">WebSocket ws://localhost:8765</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium mb-2">Scraper State</div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full {{ $scr === 'running' ? 'bg-amber-500' : 'bg-slate-400' }}"></span>
        <span class="text-lg font-semibold text-slate-900 capitalize">{{ $scr }}</span>
      </div>
      <div class="text-xs text-slate-500 mt-2">
        Last run: {{ $lastScrape?->last_scraped_at?->diffForHumans() ?? '—' }}
      </div>
    </div>
  </div>

  {{-- Run scrape panel --}}
  <div class="bg-white border border-slate-200 rounded-xl p-8 mb-6">
    <div class="flex items-center justify-between gap-8">
      <div class="flex-1">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Run Scrape Now</h2>
        <p class="text-sm text-slate-500">Triggers a full scrape across all {{ $activeUrls->count() }} active URLs. Average run takes ~15 minutes.</p>
      </div>
      <form method="POST" action="{{ route('scrape.run') }}">
        @csrf
        <button {{ !$online || $scr === 'running' ? 'disabled' : '' }}
                class="bg-primary text-white text-sm font-medium px-6 py-3 rounded-lg hover:bg-primary/90 transition inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
          <span class="material-symbols-outlined text-[20px]">play_arrow</span>
          Start Scrape
        </button>
      </form>
    </div>
    @if(!$online)
      <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
        Backend server is offline. Start it with: <code class="bg-amber-100 px-1.5 py-0.5 rounded">python main.py serve</code>
      </div>
    @endif
  </div>

  {{-- Scrape Logs (last 10 days, max 100 rows) --}}
  <div class="bg-white border border-slate-200 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Scrape Logs</h3>
        <p class="text-xs text-slate-500 mt-0.5">Activity from the last 10 days · showing up to 100 most recent entries</p>
      </div>
      @php $currentLevel = request('level'); @endphp
      <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs">
        <a href="{{ route('scrape.index') }}"
           class="px-3 py-1.5 font-medium rounded-md transition
                  {{ !$currentLevel ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
          All
        </a>
        @foreach(['info' => 'Info', 'warn' => 'Warnings', 'error' => 'Errors'] as $lvl => $label)
          <a href="{{ route('scrape.index') }}?level={{ $lvl }}"
             class="px-3 py-1.5 font-medium rounded-md transition inline-flex items-center gap-1.5
                    {{ $currentLevel === $lvl ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
            {{ $label }}
            <span class="text-[10px] px-1.5 py-0.5 rounded-full
                         {{ $lvl === 'error' ? 'bg-red-50 text-red-700' : ($lvl === 'warn' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
              {{ $logCounts[$lvl] }}
            </span>
          </a>
        @endforeach
      </div>
    </div>

    <div class="bg-slate-950 rounded-lg p-4 font-mono text-xs text-slate-200 max-h-[500px] overflow-y-auto">
      @forelse($logs as $log)
        @php
          $lvlColor = match($log->level) {
            'error' => 'text-red-400',
            'warn'  => 'text-amber-400',
            default => 'text-blue-400',
          };
        @endphp
        <div class="flex gap-3 py-1 border-b border-slate-800/50 last:border-0">
          <span class="text-slate-500 flex-shrink-0 w-28">{{ $log->created_at->format('M j H:i:s') }}</span>
          <span class="{{ $lvlColor }} uppercase w-12 flex-shrink-0">{{ $log->level }}</span>
          <span class="text-slate-400 w-24 flex-shrink-0 truncate">{{ $log->event }}</span>
          @if($log->scrapeUrl)
            <span class="text-emerald-400 w-32 flex-shrink-0 truncate">{{ $log->scrapeUrl->name }}</span>
          @else
            <span class="text-slate-600 w-32 flex-shrink-0">—</span>
          @endif
          <span class="text-slate-300 flex-1">{{ $log->message }}</span>
        </div>
      @empty
        <div class="text-center text-slate-500 py-10">
          No log entries yet. Run a scrape to start generating logs.
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
