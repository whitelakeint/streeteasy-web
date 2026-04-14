@extends('layouts.app')
@section('title', 'URLs - StreetEasy Admin')
@section('content')
<div class="p-8 max-w-[1400px]">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Scrape URLs</h1>
      <p class="text-sm text-slate-500 mt-1">Manage the list of buildings being tracked daily</p>
    </div>
    <button onclick="document.getElementById('add-drawer').classList.remove('hidden')"
            class="bg-primary text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary/90 transition inline-flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px]">add</span>
      Add URL
    </button>
  </div>

  {{-- Filters --}}
  <form method="GET" class="bg-white border border-slate-200 rounded-xl p-4 mb-4 flex items-center gap-3">
    <div class="relative flex-1 max-w-[360px]">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name or URL..."
             class="w-full pl-10 pr-3 py-2 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
    </div>
    <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
      <input type="checkbox" name="active" value="1" {{ request('active') ? 'checked' : '' }}
             class="rounded border-slate-300 text-primary focus:ring-primary/30">
      Active only
    </label>
    <button class="text-sm text-slate-600 hover:text-slate-900">Apply</button>
    @if(request('q') || request('active'))
      <a href="{{ route('urls.index') }}" class="text-sm text-slate-400 hover:text-slate-700">Reset</a>
    @endif
  </form>

  {{-- Table --}}
  <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
          <th class="px-4 py-3 font-medium">ID</th>
          <th class="px-4 py-3 font-medium">Name</th>
          <th class="px-4 py-3 font-medium">URL</th>
          <th class="px-4 py-3 font-medium">Active</th>
          <th class="px-4 py-3 font-medium">Last Status</th>
          <th class="px-4 py-3 font-medium">Last Scraped</th>
          <th class="px-4 py-3 font-medium"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($urls as $u)
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-slate-400 tabular-nums">{{ $u->id }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
            <td class="px-4 py-3 text-slate-500 truncate max-w-[260px]" title="{{ $u->url }}">{{ $u->url }}</td>
            <td class="px-4 py-3">
              <form method="POST" action="{{ route('urls.toggle', $u) }}">
                @csrf
                <button type="submit" class="relative inline-flex h-5 w-9 items-center rounded-full transition {{ $u->is_active ? 'bg-primary' : 'bg-slate-300' }}">
                  <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $u->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                </button>
              </form>
            </td>
            <td class="px-4 py-3">
              @php
                $s = $u->last_status ?: 'never';
                $styles = [
                  'completed'  => 'bg-green-50 text-green-700',
                  'in_progress'=> 'bg-blue-50 text-blue-700',
                  'failed'     => 'bg-red-50 text-red-700',
                  'never'      => 'bg-slate-100 text-slate-500',
                ][$s] ?? 'bg-slate-100 text-slate-500';
              @endphp
              <span class="text-xs px-2 py-0.5 rounded-full {{ $styles }}">{{ ucfirst(str_replace('_',' ',$s)) }}</span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">
              {{ $u->last_scraped_at?->diffForHumans() ?? '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <form method="POST" action="{{ route('urls.destroy', $u) }}"
                    onsubmit="return confirm('Delete {{ $u->name }}? All its history will be removed.')">
                @csrf @method('DELETE')
                <button class="text-slate-400 hover:text-red-600" title="Delete">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">No URLs yet. Click "Add URL" to get started.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
    <span>Showing {{ $urls->firstItem() ?? 0 }}-{{ $urls->lastItem() ?? 0 }} of {{ $urls->total() }}</span>
    <div>{{ $urls->links() }}</div>
  </div>
</div>

{{-- Add drawer --}}
<div id="add-drawer" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-slate-900/30" onclick="document.getElementById('add-drawer').classList.add('hidden')"></div>
  <div class="absolute right-0 top-0 h-full w-[420px] bg-white shadow-xl p-8 overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-lg font-semibold text-slate-900">Add a new URL</h2>
      <button onclick="document.getElementById('add-drawer').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <form method="POST" action="{{ route('urls.store') }}" class="flex flex-col gap-5">
      @csrf
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-slate-900">Name</label>
        <input type="text" name="name" required placeholder="e.g. The Brook"
               class="w-full px-3 py-2.5 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-slate-900">URL</label>
        <input type="url" name="url" required placeholder="https://streeteasy.com/building/..."
               class="w-full px-3 py-2.5 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>
      <div class="flex items-center justify-end gap-3 mt-4">
        <button type="button" onclick="document.getElementById('add-drawer').classList.add('hidden')"
                class="text-sm text-slate-600 hover:text-slate-900 px-4 py-2">Cancel</button>
        <button type="submit" class="bg-primary text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-primary/90">Add URL</button>
      </div>
    </form>
  </div>
</div>
@endsection
