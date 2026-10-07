<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OptionType;
use App\Enums\SelectionType;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\OptionGroup;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OptionGroupController extends Controller
{
    public function index(): View
    {
        $groups = OptionGroup::query()
            ->withCount(['options', 'products'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.options.index', [
            'groups' => $groups,
            'types' => OptionType::cases(),
            'selectionTypes' => SelectionType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);

        $group = OptionGroup::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.options.show', $group)
            ->with('success', 'Option group created. Add choices below.');
    }

    public function show(OptionGroup $option): View
    {
        $option->load(['options' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);

        return view('admin.options.show', [
            'group' => $option,
            'types' => OptionType::cases(),
            'selectionTypes' => SelectionType::cases(),
            'ingredients' => Ingredient::query()->orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }

    public function update(Request $request, OptionGroup $option): RedirectResponse
    {
        $data = $this->validateGroup($request, $option);

        $option->update([
            ...$data,
            'slug' => $this->uniqueSlug($data['name'], $option->id),
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Option group updated.');
    }

    public function destroy(OptionGroup $option): RedirectResponse
    {
        $option->delete();

        return redirect()
            ->route('admin.options.index')
            ->with('success', 'Option group removed.');
    }

    public function storeOption(Request $request, OptionGroup $option): RedirectResponse
    {
        $data = $this->validateOption($request);

        $maxSort = (int) $option->options()->max('sort_order');

        ProductOption::query()->create([
            'option_group_id' => $option->id,
            'name' => $data['name'],
            'price_adjustment' => $data['price_adjustment'] ?? 0,
            'ingredient_id' => $data['ingredient_id'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
        ]);

        return back()->with('success', 'Choice added.');
    }

    public function updateOption(Request $request, OptionGroup $option, ProductOption $choice): RedirectResponse
    {
        abort_unless($choice->option_group_id === $option->id, 404);

        $data = $this->validateOption($request);

        $choice->update([
            'name' => $data['name'],
            'price_adjustment' => $data['price_adjustment'] ?? 0,
            'ingredient_id' => $data['ingredient_id'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? $choice->sort_order,
        ]);

        return back()->with('success', 'Choice updated.');
    }

    public function destroyOption(OptionGroup $option, ProductOption $choice): RedirectResponse
    {
        abort_unless($choice->option_group_id === $option->id, 404);

        $choice->delete();

        return back()->with('success', 'Choice removed.');
    }

    private function validateGroup(Request $request, ?OptionGroup $group = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(OptionType::class)],
            'selection_type' => ['required', Rule::enum(SelectionType::class)],
            'min_selections' => ['required', 'integer', 'min:0', 'max:50'],
            'max_selections' => ['required', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ((int) $data['max_selections'] < (int) $data['min_selections']) {
            $data['max_selections'] = $data['min_selections'];
        }

        $data['sort_order'] = $data['sort_order'] ?? ($group?->sort_order ?? 0);

        return $data;
    }

    private function validateOption(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_adjustment' => ['nullable', 'numeric'],
            'ingredient_id' => ['nullable', 'exists:ingredients,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'option-group';
        $slug = $base;
        $i = 1;

        while (
            OptionGroup::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
