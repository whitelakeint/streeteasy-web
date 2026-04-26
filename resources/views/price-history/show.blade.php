@extends('layouts.app')
@section('title', $property->property_name . ' - Price History')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@php
  // Filter history once — used by the content block and the chart script below
  $validHistory = $history->filter(fn($r) => $r->rent !== null && $r->scrape_date !== null)->values();
  $rentChartData = $validHistory->map(function ($r) {
      return [
          'date'     => $r->scrape_date instanceof \Carbon\Carbon
              ? $r->scrape_date->format('Y-m-d')
              : (string) $r->scrape_date,
          'rent'     => (int) $r->rent,
          'original' => (bool) $r->original,
      ];
  })->values()->all();
@endphp

@push('scripts')
<script>
  // --- Rent history chart ---
  const rentChartData = {!! json_encode($rentChartData) !!};

  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('rent-chart');
    if (!ctx || !rentChartData.length) return;

    const labels = rentChartData.map(d => d.date);
    const values = rentChartData.map(d => d.rent);
    const sources = rentChartData.map(d => d.original ? 'Scraped' : 'Estimated');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Rent',
          data: values,
          borderColor: '#2563EB',
          backgroundColor: 'rgba(37, 99, 235, 0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.25,
          pointRadius: 0,
          pointHoverRadius: 6,
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
            padding: 12,
            cornerRadius: 6,
            displayColors: false,
            callbacks: {
              title: (items) => {
                const d = new Date(items[0].label);
                return String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + '-' + d.getFullYear();
              },
              label: (item) => '$' + item.parsed.y.toLocaleString(),
              afterLabel: (item) => sources[item.dataIndex]
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: '#94A3B8',
              font: { size: 10 },
              maxTicksLimit: 7,
              callback: function(val) {
                const label = this.getLabelForValue(val);
                const d = new Date(label);
                return String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#F1F5F9' },
            border: { display: false },
            ticks: {
              color: '#94A3B8',
              font: { size: 10 },
              callback: (val) => '$' + val.toLocaleString()
            }
          }
        }
      }
    });
  });

  // --- Building / Unit switcher ---
  const unitsByBuilding = @json($unitsByBuilding);
  const currentBuildingId = {{ $property->url_id }};
  const currentUnitId = {{ $property->id }};

  function buildUnitOptions(buildingId) {
    const sel = document.getElementById('unit-select');
    sel.innerHTML = '';
    const units = unitsByBuilding[buildingId] || [];
    for (const u of units) {
      const opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = u.name;
      if (u.id == currentUnitId) opt.selected = true;
      sel.appendChild(opt);
    }
    if (units.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No units scraped yet';
      opt.disabled = true;
      sel.appendChild(opt);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    buildUnitOptions(currentBuildingId);

    document.getElementById('building-select').addEventListener('change', (e) => {
      const bid = e.target.value;
      const units = unitsByBuilding[bid] || [];
      if (units.length > 0) {
        // Navigate to the first unit of the newly selected building
        window.location = '/price-history/' + units[0].id;
      } else {
        buildUnitOptions(bid);
      }
    });

    document.getElementById('unit-select').addEventListener('change', (e) => {
      if (e.target.value) window.location = '/price-history/' + e.target.value;
    });
  });
</script>
@endpush
@section('content')
@php
  // $validHistory is already computed at the top of the file
  $rents = $validHistory->pluck('rent')->values()->all();
  $labels = $validHistory->pluck('scrape_date')->values()->all();

  $chartW = 1000; $chartH = 280;
  $pts = [];
  if (count($rents) > 0) {
      $n = max(1, count($rents) - 1);
      $rMin = min($rents) * 0.98;
      $rMax = max($rents) * 1.02;
      $range = max(1, $rMax - $rMin);
      foreach ($rents as $i => $v) {
          $x = ($i / $n) * $chartW;
          $y = $chartH - (($v - $rMin) / $range) * $chartH;
          $pts[] = round($x, 1) . ',' . round($y, 1);
      }
  }
  $polyline = implode(' ', $pts);

  // Date labels for chart axes — convert to strings safely
  $firstLabel = null;
  $lastLabel  = null;
  if (count($labels) > 0) {
      $first = $labels[0];
      $last  = $labels[count($labels) - 1];
      $firstLabel = $first instanceof \Carbon\Carbon ? $first->format('m-d-Y') : (string) $first;
      $lastLabel  = $last  instanceof \Carbon\Carbon ? $last->format('m-d-Y')  : (string) $last;
  }

  $firstSeenStr   = $firstSeen   instanceof \Carbon\Carbon ? $firstSeen->format('m-d-Y')   : ($firstSeen   ? (string) $firstSeen : '—');
  $lastUpdatedStr = $lastUpdated instanceof \Carbon\Carbon ? $lastUpdated->format('m-d-Y') : ($lastUpdated ? (string) $lastUpdated : '—');
@endphp
<div class="p-8 max-w-[1400px]">
  <div class="text-xs text-slate-500 mb-3">
    <a href="{{ route('properties.index') }}" class="hover:text-slate-900">Properties</a>
    <span class="mx-1">›</span>
    <span>{{ $property->scrapeUrl?->name }}</span>
    <span class="mx-1">›</span>
    <span>{{ $property->property_name }}</span>
  </div>
  <div class="flex items-start justify-between mb-8 gap-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">{{ $property->property_name }} at {{ $property->scrapeUrl?->name }}</h1>
      <p class="text-sm text-slate-500 mt-1">
        {{ $property->beds_no === 0 ? 'Studio' : ($property->beds ?: '—') }} ·
        {{ $property->baths ?: '—' }} ·
        {{ $property->area ?: 'base rent' }}
      </p>
    </div>

    {{-- Building / Unit switcher --}}
    <div class="flex items-center gap-3">
      <div class="flex flex-col gap-1">
        <label class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Building</label>
        <select id="building-select"
                class="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none min-w-[200px]">
          @foreach($allBuildings as $b)
            <option value="{{ $b->id }}" @selected($b->id == $property->url_id)>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Unit</label>
        <select id="unit-select"
                class="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none min-w-[120px]">
          {{-- Populated by JS --}}
        </select>
      </div>
      <a href="{{ route('price-history.export', [$property] + request()->query()) }}"
         class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 self-end transition"
         title="Export CSV">
        <span class="material-symbols-outlined text-[16px]">download</span>
      </a>
      @if($property->listing_url)
        <a href="{{ $property->listing_url }}" target="_blank"
           class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 self-end">
          <span class="material-symbols-outlined text-[16px]">open_in_new</span>
        </a>
      @endif
    </div>
  </div>

  {{-- Stat cards (scoped to selected date range) --}}
  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Current Rent</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">${{ number_format($current) }}</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Period Change</div>
      <div class="text-3xl font-semibold mt-2 tabular-nums {{ $change >= 0 ? 'text-green-600' : 'text-red-600' }}">
        {{ $change >= 0 ? '+' : '' }}{{ $change }}%
      </div>
      <div class="text-xs text-slate-400 mt-1">{{ $rangeLabel }}</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Period High</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">{{ $max ? '$'.number_format($max) : '—' }}</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="text-xs text-slate-500 font-medium">Period Low</div>
      <div class="text-3xl font-semibold text-slate-900 mt-2 tabular-nums">{{ $min ? '$'.number_format($min) : '—' }}</div>
    </div>
  </div>

  {{-- Chart --}}
  <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">Rent Over Time</h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ $firstSeenStr }} — {{ $lastUpdatedStr }} ({{ count($rents) }} data points)</p>
      </div>
      @php
        $baseUrl = route('price-history.show', $property);
        $isCustom = $range === 'custom';
      @endphp
      <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
          @foreach([['7','7D'], ['30','30D'], ['90','90D']] as [$val, $label])
            <a href="{{ $baseUrl }}?range={{ $val }}"
               class="px-3 py-1.5 text-xs font-medium rounded-md transition
                      {{ $range === $val ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
              {{ $label }}
            </a>
          @endforeach
          <button type="button" onclick="document.getElementById('custom-range-panel').classList.toggle('hidden')"
                  class="px-3 py-1.5 text-xs font-medium rounded-md transition
                         {{ $isCustom ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
            Custom
          </button>
        </div>
      </div>
    </div>

    <div id="custom-range-panel" class="{{ $isCustom ? '' : 'hidden' }} mb-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
      <form method="GET" action="{{ $baseUrl }}" class="flex items-end gap-3 flex-wrap">
        <input type="hidden" name="range" value="custom">
        <div class="flex flex-col gap-1">
          <label class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">From</label>
          <input type="date" name="from" value="{{ $fromDate->toDateString() }}"
                 class="px-3 py-1.5 text-xs rounded-md border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">To</label>
          <input type="date" name="to" value="{{ $toDate->toDateString() }}"
                 class="px-3 py-1.5 text-xs rounded-md border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
        <button class="bg-primary text-white text-xs font-medium px-3 py-1.5 rounded-md hover:bg-primary/90">Apply</button>
      </form>
    </div>
    <div class="h-72">
      <canvas id="rent-chart"></canvas>
    </div>
  </div>

  {{-- Data points + Unit details --}}
  <div class="grid grid-cols-2 gap-4">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">Daily Data Points</h3>
      <div class="max-h-[400px] overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="sticky top-0 bg-white border-b border-slate-100">
            <tr class="text-left text-xs text-slate-500">
              <th class="py-2 font-medium">Date</th>
              <th class="py-2 font-medium text-right">Rent</th>
              <th class="py-2 font-medium text-right">Change</th>
              <th class="py-2 font-medium">Source</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            @php $prev = null; @endphp
            @foreach($history->reverse() as $row)
              @php
                $delta = $prev && $row->rent && $prev ? (($row->rent - $prev) / $prev) * 100 : 0;
                $prev = $row->rent;
                $dateStr = $row->scrape_date instanceof \Carbon\Carbon
                    ? $row->scrape_date->format('m-d-Y')
                    : (string) $row->scrape_date;
              @endphp
              <tr>
                <td class="py-2 text-slate-700">{{ $dateStr }}</td>
                <td class="py-2 text-right tabular-nums">${{ number_format($row->rent) }}</td>
                <td class="py-2 text-right text-xs {{ $delta > 0 ? 'text-green-600' : ($delta < 0 ? 'text-red-600' : 'text-slate-400') }}">
                  {{ $delta !== 0.0 ? ($delta > 0 ? '+' : '') . number_format($delta, 1) . '%' : '—' }}
                </td>
                <td class="py-2">
                  <span class="text-xs px-2 py-0.5 rounded-full {{ $row->original ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ $row->original ? 'Scraped' : 'Estimated' }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">Unit Details</h3>
      <dl class="text-sm divide-y divide-slate-100">
        @php
          $details = [
            ['Building', $property->scrapeUrl?->name],
            ['Unit', $property->property_name],
            ['Beds', $property->beds_no === 0 ? 'Studio' : ($property->beds ?: '—')],
            ['Baths', $property->baths ?: '—'],
            ['Area', $property->area ?: '—'],
            ['Listed By', $property->listed_by ?: '—'],
            ['Specials', $property->specials ?: '—'],
            ['First Seen', $firstSeenStr],
            ['Last Updated', $lastUpdatedStr],
          ];
        @endphp
        @foreach($details as [$k,$v])
          <div class="flex items-start justify-between py-2.5 gap-6">
            <dt class="text-slate-500 text-xs uppercase tracking-wide flex-shrink-0">{{ $k }}</dt>
            <dd class="text-slate-800 text-right text-sm">{{ $v }}</dd>
          </div>
        @endforeach
        @if($property->availability)
          <div class="flex items-start justify-between py-2.5 gap-6">
            <dt class="text-slate-500 text-xs uppercase tracking-wide">Availability</dt>
            <dd><span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700">{{ $property->availability }}</span></dd>
          </div>
        @endif
      </dl>
    </div>
  </div>
</div>
@endsection
