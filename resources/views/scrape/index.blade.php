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
      <div class="text-xs text-slate-500 mt-2">{{ config('services.scraper.control_url') }}</div>
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

  {{-- Scrape Logs (last 10 days, max 100 rows) — auto-refreshes every 3s --}}
  @php $currentLevel = request('level'); @endphp
  <div class="bg-white border border-slate-200 rounded-xl p-6"
       data-logs-url="{{ route('scrape.logs') }}{{ $currentLevel ? '?level='.$currentLevel : '' }}">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Scrape Logs</h3>
        <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
          <span>Activity from the last 10 days · up to 100 most recent</span>
          <span class="inline-flex items-center gap-1">
            <span id="auto-refresh-dot" class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
            <span id="auto-refresh-label" class="text-green-600">live</span>
          </span>
        </p>
      </div>
      <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs" id="level-tabs">
          <a href="{{ route('scrape.index') }}"
             class="px-3 py-1.5 font-medium rounded-md transition
                    {{ !$currentLevel ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
            All
          </a>
          @foreach(['info' => 'Info', 'warn' => 'Warnings', 'error' => 'Errors'] as $lvl => $label)
            <a href="{{ route('scrape.index') }}?level={{ $lvl }}"
               data-level="{{ $lvl }}"
               class="px-3 py-1.5 font-medium rounded-md transition inline-flex items-center gap-1.5
                      {{ $currentLevel === $lvl ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
              {{ $label }}
              <span data-count="{{ $lvl }}"
                    class="text-[10px] px-1.5 py-0.5 rounded-full
                           {{ $lvl === 'error' ? 'bg-red-50 text-red-700' : ($lvl === 'warn' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                {{ $logCounts[$lvl] }}
              </span>
            </a>
          @endforeach
        </div>
        <button type="button" id="toggle-autorefresh"
                class="text-xs text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-md bg-white transition">
          Pause
        </button>
      </div>
    </div>

    <div id="logs-container" class="bg-slate-950 rounded-lg p-4 font-mono text-xs text-slate-200 max-h-[500px] overflow-y-auto">
      @forelse($logs as $log)
        @php
          $lvlColor = match($log->level) {
            'error' => 'text-red-400',
            'warn'  => 'text-amber-400',
            default => 'text-blue-400',
          };
        @endphp
        <div class="flex gap-3 py-1 border-b border-slate-800/50 last:border-0">
          <span class="text-slate-500 flex-shrink-0 w-28">{{ $log->created_at->format('m-d-Y H:i:s') }}</span>
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

@push('scripts')
<script>
(function () {
  const container    = document.querySelector('[data-logs-url]');
  if (!container) return;
  const url          = container.dataset.logsUrl;
  const logsEl       = document.getElementById('logs-container');
  const toggleBtn    = document.getElementById('toggle-autorefresh');
  const statusDot    = document.getElementById('auto-refresh-dot');
  const statusLabel  = document.getElementById('auto-refresh-label');
  const REFRESH_MS   = 3000;
  let timer = null;
  let paused = false;
  let lastTopId = null;

  const levelColor = {
    error: 'text-red-400',
    warn:  'text-amber-400',
    info:  'text-blue-400',
  };

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
      '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[c]));
  }

  function renderLogs(logs) {
    if (!logs.length) {
      logsEl.innerHTML = '<div class="text-center text-slate-500 py-10">No log entries yet.</div>';
      return;
    }
    const html = logs.map(log => {
      const color = levelColor[log.level] || levelColor.info;
      const building = log.building_name
        ? `<span class="text-emerald-400 w-32 flex-shrink-0 truncate">${escapeHtml(log.building_name)}</span>`
        : `<span class="text-slate-600 w-32 flex-shrink-0">—</span>`;
      return `
        <div class="flex gap-3 py-1 border-b border-slate-800/50 last:border-0">
          <span class="text-slate-500 flex-shrink-0 w-28">${escapeHtml(log.created_at)}</span>
          <span class="${color} uppercase w-12 flex-shrink-0">${escapeHtml(log.level)}</span>
          <span class="text-slate-400 w-24 flex-shrink-0 truncate">${escapeHtml(log.event)}</span>
          ${building}
          <span class="text-slate-300 flex-1">${escapeHtml(log.message)}</span>
        </div>`;
    }).join('');
    logsEl.innerHTML = html;
  }

  function updateCounts(counts) {
    for (const lvl of ['info', 'warn', 'error']) {
      const badge = document.querySelector(`[data-count="${lvl}"]`);
      if (badge) badge.textContent = counts[lvl] ?? 0;
    }
  }

  async function fetchLogs() {
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
      if (!res.ok) throw new Error(res.statusText);
      const data = await res.json();
      const topId = data.logs[0]?.id ?? null;

      // Preserve scroll position if user is looking at older logs
      const atTop = logsEl.scrollTop < 30;

      if (topId !== lastTopId) {
        renderLogs(data.logs);
        lastTopId = topId;
        if (atTop) logsEl.scrollTop = 0;
      }
      updateCounts(data.counts);
      setIndicator(true);
    } catch (e) {
      setIndicator(false);
    }
  }

  function setIndicator(ok) {
    if (paused) {
      statusDot.className = 'w-1.5 h-1.5 rounded-full bg-slate-400';
      statusLabel.textContent = 'paused';
      statusLabel.className = 'text-slate-500';
    } else if (ok) {
      statusDot.className = 'w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse';
      statusLabel.textContent = 'live';
      statusLabel.className = 'text-green-600';
    } else {
      statusDot.className = 'w-1.5 h-1.5 rounded-full bg-red-500';
      statusLabel.textContent = 'offline';
      statusLabel.className = 'text-red-600';
    }
  }

  function start() {
    if (timer) return;
    timer = setInterval(fetchLogs, REFRESH_MS);
    fetchLogs();
  }
  function stop() {
    if (timer) { clearInterval(timer); timer = null; }
  }

  toggleBtn.addEventListener('click', () => {
    paused = !paused;
    toggleBtn.textContent = paused ? 'Resume' : 'Pause';
    if (paused) { stop(); setIndicator(true); }
    else { start(); }
  });

  // Pause auto-refresh when the tab is hidden to save resources
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stop();
    else if (!paused) start();
  });

  start();
})();
</script>
@endpush
@endsection
