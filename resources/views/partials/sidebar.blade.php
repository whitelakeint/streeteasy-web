@php
  $nav = [
    ['route' => 'dashboard',       'icon' => 'dashboard',         'label' => 'Dashboard'],
    ['route' => 'urls.index',      'icon' => 'link',              'label' => 'URLs'],
    ['route' => 'properties.index','icon' => 'apartment',         'label' => 'Properties'],
    ['route' => 'scrape.index',    'icon' => 'play_circle',       'label' => 'Scrape Control'],
  ];
@endphp
<aside class="w-[240px] bg-white border-r border-slate-200 flex-shrink-0 flex flex-col">
  <div class="p-6 border-b border-slate-200">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-primary flex items-center justify-center rounded-lg">
        <span class="text-white font-black text-sm tracking-tighter">SE</span>
      </div>
      <div>
        <div class="text-sm font-semibold text-slate-900">Scraper Admin</div>
        <div class="text-xs text-slate-500">StreetEasy</div>
      </div>
    </div>
  </div>
  <nav class="flex-1 p-3 space-y-1">
    @foreach($nav as $item)
      @php $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*', $item['route']).''); @endphp
      <a href="{{ route($item['route']) }}"
         class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                {{ $active ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
        <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
        <span>{{ $item['label'] }}</span>
      </a>
    @endforeach
  </nav>
  <div class="p-3 border-t border-slate-200">
    <div class="flex items-center gap-3 px-2 py-2">
      <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 text-xs font-semibold">
        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-slate-900 truncate">{{ auth()->user()->name ?? 'Admin' }}</div>
        <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-slate-400 hover:text-slate-700" title="Sign out">
          <span class="material-symbols-outlined text-[18px]">logout</span>
        </button>
      </form>
    </div>
  </div>
</aside>
