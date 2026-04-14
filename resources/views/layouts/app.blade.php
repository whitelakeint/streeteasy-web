<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>@yield('title', 'StreetEasy Scraper Admin')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: { DEFAULT: "#2563EB", foreground: "#FFFFFF" },
          slate: {
            50: "#F8FAFC", 100: "#F1F5F9", 200: "#E2E8F0", 300: "#CBD5E1",
            400: "#94A3B8", 500: "#64748B", 600: "#475569", 700: "#334155",
            800: "#1E293B", 900: "#0F172A"
          }
        },
        borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
        fontFamily: {
          headline: ["Inter", "sans-serif"],
          body: ["Inter", "sans-serif"],
          label: ["Inter", "sans-serif"],
          mono: ["JetBrains Mono", "monospace"]
        }
      }
    }
  }
</script>
<style>
  body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
@stack('head')
</head>
<body class="bg-slate-50 h-full">
<div class="flex h-full">
  @include('partials.sidebar')
  <main class="flex-1 overflow-y-auto">
    @if(session('status'))
      <div class="m-6 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('status') }}</div>
    @endif
    @yield('content')
  </main>
</div>
@stack('scripts')
</body>
</html>
