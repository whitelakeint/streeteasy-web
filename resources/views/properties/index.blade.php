@extends('layouts.app')
@section('title', 'Properties - StreetEasy Admin')
@section('content')
<div class="p-8 max-w-[1400px]">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Properties</h1>
      <p class="text-sm text-slate-500 mt-1">Scraped rental listings across all tracked buildings</p>
    </div>
    <a href="{{ route('properties.export', request()->query()) }}"
       class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
      <span class="material-symbols-outlined text-[18px]">download</span>
      Export CSV
    </a>
  </div>

  <form method="GET" class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <div class="flex items-center gap-4 flex-wrap">
      <div class="flex flex-col gap-1">
        <label class="text-xs text-slate-500 font-medium">Date</label>
        <input type="date" name="date" value="{{ $date }}"
               class="px-3 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs text-slate-500 font-medium">Building</label>
        <select name="building" class="px-3 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <option value="">All ({{ $buildings->count() }})</option>
          @foreach($buildings as $b)
            <option value="{{ $b->id }}" @selected(request('building') == $b->id)>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs text-slate-500 font-medium">Beds</label>
        <select name="beds" class="px-3 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <option value="">Any</option>
          @foreach([0,1,2,3,4,5] as $n)
            <option value="{{ $n }}" @selected(request('beds') == $n)>{{ $n === 0 ? 'Studio' : "$n bed" . ($n>1?'s':'') }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs text-slate-500 font-medium">Rent Range</label>
        <div class="flex items-center gap-2">
          <input type="number" name="min_rent" value="{{ request('min_rent') }}" placeholder="Min"
                 class="w-24 px-2 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <span class="text-slate-400">—</span>
          <input type="number" name="max_rent" value="{{ request('max_rent') }}" placeholder="Max"
                 class="w-24 px-2 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
        </div>
      </div>
      <div class="flex-1"></div>
      <div class="flex items-end gap-2">
        <button type="submit" class="bg-primary text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary/90">Apply</button>
        <a href="{{ route('properties.index') }}" class="text-sm text-slate-500 hover:text-slate-900 px-3 py-2">Reset</a>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">{{ $props->total() }} results for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</p>
  </form>

  <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
          <th class="px-4 py-3 font-medium">Unit #</th>
          <th class="px-4 py-3 font-medium">Building</th>
          <th class="px-4 py-3 font-medium">Beds</th>
          <th class="px-4 py-3 font-medium">Baths</th>
          <th class="px-4 py-3 font-medium">Area</th>
          <th class="px-4 py-3 font-medium text-right">Rent</th>
          <th class="px-4 py-3 font-medium">Listed By</th>
          <th class="px-4 py-3 font-medium">Availability</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($props as $p)
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <a href="{{ route('price-history.show', $p) }}" class="text-primary hover:underline font-medium">{{ $p->property_name }}</a>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ $p->scrapeUrl?->name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ $p->beds ?: '—' }}</td>
            <td class="px-4 py-3 text-slate-600">{{ $p->baths ?: '—' }}</td>
            <td class="px-4 py-3 text-slate-500">{{ $p->area ?: '—' }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900 tabular-nums">
              {{ $p->rent ? '$'.number_format($p->rent) : '—' }}
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">{{ $p->listed_by ?: '—' }}</td>
            <td class="px-4 py-3">
              @if($p->availability)
                @php $isAvailableNow = stripos($p->availability, 'now') !== false; @endphp
                <span class="text-xs {{ $isAvailableNow ? 'text-green-600' : 'text-amber-600' }}">{{ $p->availability }}</span>
              @else
                <span class="text-slate-400">—</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('price-history.show', $p) }}" class="text-slate-400 hover:text-primary" title="View history">
                <span class="material-symbols-outlined text-[18px]">show_chart</span>
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-slate-400">No properties match these filters.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
    <span>Showing {{ $props->firstItem() ?? 0 }}-{{ $props->lastItem() ?? 0 }} of {{ $props->total() }}</span>
    <div>{{ $props->links() }}</div>
  </div>
</div>
@endsection
