<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(
        StoreCategoryRequest $request
    ): RedirectResponse {
        Category::create(
            $request->validated()
        );

        return redirect()
            ->route('categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): RedirectResponse {
        $category->update(
            $request->validated()
        );

        return redirect()
            ->route('categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(
        Category $category
    ): RedirectResponse {
        Gate::authorize('delete', $category);

        try {
            $category->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    __('Cannot delete a category that has products.')
                );
        }

        return redirect()
            ->route('categories.index')
            ->with('success', __('Category deleted successfully.'));
    }
}
