<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->when($request->q, fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();

        $kpis = [
            ['label' => 'Products', 'value' => Product::query()->count(), 'hint' => 'Total menu items'],
            ['label' => 'Active', 'value' => Product::query()->where('is_active', true)->count(), 'hint' => 'Visible on storefront', 'tone' => 'success'],
            ['label' => 'Featured', 'value' => Product::query()->where('is_featured', true)->count(), 'hint' => 'Highlighted on menu'],
            ['label' => 'Hidden', 'value' => Product::query()->where('is_active', false)->count(), 'hint' => 'Not shown to customers', 'tone' => 'warn'],
        ];

        return view('admin.products.index', compact('products', 'kpis'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'is_customizable' => true]),
            'categories' => Category::query()->orderBy('name')->get(),
            'optionGroups' => OptionGroup::query()->withCount('options')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        $data['image'] = $this->storeImage($request->file('image'));

        $product = Product::query()->create($data);
        $product->optionGroups()->sync($request->input('option_groups', []));

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('optionGroups');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'optionGroups' => OptionGroup::query()->withCount('options')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);

        if ($request->boolean('remove_image') && ! $request->hasFile('image')) {
            $this->deleteStoredImage($product->image);
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            $this->deleteStoredImage($product->image);
            $data['image'] = $this->storeImage($request->file('image'));
        } else {
            unset($data['image']);
        }

        $product->update($data);
        $product->optionGroups()->sync($request->input('option_groups', []));

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteStoredImage($product->image);
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'is_customizable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'option_groups' => ['nullable', 'array'],
            'option_groups.*' => ['integer', 'exists:option_groups,id'],
        ]);

        foreach (['is_customizable', 'is_active', 'is_featured'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;
        unset($data['remove_image']);

        return $data;
    }

    private function storeImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $path = $file->store('products', 'public');

        return 'storage/'.$path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'storage/products/')) {
            return;
        }

        Storage::disk('public')->delete(Str::after($path, 'storage/'));
    }
}
