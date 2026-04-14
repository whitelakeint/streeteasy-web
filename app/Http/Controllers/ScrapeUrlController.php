<?php

namespace App\Http\Controllers;

use App\Models\ScrapeUrl;
use Illuminate\Http\Request;

class ScrapeUrlController extends Controller
{
    public function index(Request $request)
    {
        $q = ScrapeUrl::query();

        if ($term = $request->query('q')) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")->orWhere('url', 'like', "%{$term}%");
            });
        }

        if ($request->query('active') === '1') {
            $q->where('is_active', 1);
        }

        $urls = $q->orderBy('id')->paginate(25)->withQueryString();

        return view('urls.index', compact('urls'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url'  => 'required|url|max:2048',
        ]);

        $row = ScrapeUrl::updateOrCreate(
            ['url' => $data['url']],
            ['name' => $data['name'], 'is_active' => 1]
        );

        return back()->with('status', "Added: {$row->name}");
    }

    public function toggle(ScrapeUrl $url)
    {
        $url->is_active = !$url->is_active;
        $url->save();
        return back()->with('status', "URL {$url->name} " . ($url->is_active ? 'activated' : 'deactivated'));
    }

    public function destroy(ScrapeUrl $url)
    {
        $name = $url->name;
        $url->delete();
        return back()->with('status', "Deleted: {$name}");
    }
}
