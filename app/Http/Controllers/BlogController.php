<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlogRequest;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\Category;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'uk');
        if (! in_array($lang, ['uk', 'en'], true)) {
            $lang = 'uk';
        }

        $blogs = Blog::with(['category', 'author', 'translations'])
            ->orderByDesc('dt_created')
            ->paginate(6)
            ->withQueryString();

        return view('blog.index', compact('blogs', 'lang'));
    }

    public function admin()
    {
        $blogs = Blog::with('category')
            ->orderByDesc('dt_created')
            ->paginate(10);

        return view('blog.admin', compact('blogs'));
    }

    public function create()
    {
        $categories = Category::orderBy('category_name')
            ->pluck('category_name', 'category_id');

        return view('blog.create', compact('categories'));
    }

    public function store(BlogRequest $request)
    {
        $validated = $request->validated();

        $blog = Blog::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'text' => $validated['text'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $this->syncTranslations($blog, $validated);

        return redirect()
            ->route('blog.show', $blog)
            ->with('success', 'Запис успішно створено.');
    }

    public function show(Request $request, Blog $blog)
    {
        $lang = $request->get('lang', 'uk');
        if (! in_array($lang, ['uk', 'en'], true)) {
            $lang = 'uk';
        }

        $blog->load(['category', 'author', 'translations']);

        return view('blog.show', compact('blog', 'lang'));
    }

    public function edit(Blog $blog)
    {
        $blog->load('translations');
        $categories = Category::orderBy('category_name')
            ->pluck('category_name', 'category_id');

        return view('blog.edit', compact('blog', 'categories'));
    }

    public function update(BlogRequest $request, Blog $blog)
    {
        $validated = $request->validated();

        $blog->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'text' => $validated['text'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        $this->syncTranslations($blog, $validated);

        return redirect()
            ->route('blog.show', $blog)
            ->with('success', 'Запис успішно оновлено.');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();

        return redirect()
            ->route('blog.admin')
            ->with('success', 'Запис видалено.');
    }

    private function syncTranslations(Blog $blog, array $data): void
    {
        BlogTranslation::updateOrCreate(
            [
                'blog_id' => $blog->blog_id,
                'locale' => 'uk',
            ],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'text' => $data['text'] ?? null,
            ]
        );

        $enTitle = $data['en_title'] ?? null;
        $enDescription = $data['en_description'] ?? null;
        $enText = $data['en_text'] ?? null;

        if ($enTitle !== null || $enDescription !== null || $enText !== null) {
            BlogTranslation::updateOrCreate(
                [
                    'blog_id' => $blog->blog_id,
                    'locale' => 'en',
                ],
                [
                    'title' => filled($enTitle) ? $enTitle : $data['title'],
                    'description' => $enDescription,
                    'text' => $enText,
                ]
            );
        }
    }
}
