@extends('layouts.guest')
@section('title', 'Sign In - StreetEasy Admin')
@section('content')
<main class="w-full max-w-[420px]">
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-10 flex flex-col">
    <div class="mb-8 flex justify-start">
      <div class="w-10 h-10 bg-primary flex items-center justify-center rounded-lg">
        <span class="text-white font-black text-lg tracking-tighter">SE</span>
      </div>
    </div>
    <div class="mb-8">
      <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Sign in to continue</h1>
      <p class="text-slate-500 text-sm mt-1">Admin access to the StreetEasy Scraper dashboard</p>
    </div>

    @if ($errors->any())
      <div class="mb-6 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
      @csrf

      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-slate-900" for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
               placeholder="you@example.com"
               class="w-full px-3 py-2.5 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-slate-900" for="password">Password</label>
        <div class="relative">
          <input type="password" name="password" id="password" required
                 class="w-full px-3 py-2.5 pr-10 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <button type="button" onclick="togglePw()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
            <span id="pw-icon" class="material-symbols-outlined text-[20px]">visibility</span>
          </button>
        </div>
      </div>

      <button type="submit"
              class="mt-2 w-full bg-primary text-white text-sm font-medium py-2.5 rounded-lg hover:bg-primary/90 transition">
        Sign in
      </button>
    </form>

    <p class="mt-8 text-xs text-slate-400 leading-relaxed">
      Access is provisioned by the administrator. Contact your admin if you need an account.
    </p>
  </div>
</main>
<script>
  function togglePw(){
    const i = document.getElementById('password');
    const icon = document.getElementById('pw-icon');
    if (i.type === 'password') { i.type = 'text'; icon.textContent = 'visibility_off'; }
    else { i.type = 'password'; icon.textContent = 'visibility'; }
  }
</script>
@endsection
