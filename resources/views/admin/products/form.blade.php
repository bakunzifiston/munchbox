@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'New product')
@section('heading', $product->exists ? 'Edit product' : 'New product')

@section('content')
<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-5 rounded-3xl border border-munch/10 bg-white p-6 shadow-sm">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div>
        <label class="label-field">Name</label>
        <input name="name" value="{{ old('name', $product->name) }}" class="input-field" required>
    </div>
    <div>
        <label class="label-field">Category</label>
        <select name="category_id" class="input-field" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label-field">Description</label>
        <textarea name="description" class="input-field" rows="3">{{ old('description', $product->description) }}</textarea>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label-field">Base price (RWF)</label>
            <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" class="input-field" required min="0" step="any">
        </div>
        <div>
            <label class="label-field">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="input-field" min="0">
        </div>
    </div>

    <div>
        <label class="label-field" for="image">Product image</label>
        <p class="mb-2 text-xs text-ink/45">JPG, PNG, or WebP · max 4 MB. Shown on the menu and product page.</p>
        <div class="flex flex-wrap items-start gap-4">
            <div
                id="image-preview"
                class="h-28 w-28 shrink-0 rounded-2xl bg-cover bg-center ring-1 ring-munch/10 {{ $product->image ? '' : 'bg-[#FAF8F4]' }}"
                @if($product->image)
                    style="background-image: url('{{ asset($product->image) }}')"
                @endif
            ></div>
            <div class="min-w-0 flex-1 space-y-2">
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-ink/70 file:mr-3 file:rounded-full file:border-0 file:bg-munch file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-munch/90"
                >
                @error('image')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if($product->image)
                    <label class="flex items-center gap-2 text-sm text-ink/60">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove current image
                    </label>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-sm">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Active</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_customizable" value="1" @checked(old('is_customizable', $product->is_customizable ?? true))> Customizable</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))> Featured</label>
    </div>

    <div>
        <div class="flex items-center justify-between gap-3">
            <p class="label-field !mb-0">Option groups</p>
            <a href="{{ route('admin.options.index') }}" class="text-xs font-bold text-box hover:underline">Edit choices (Serving Style, Bread, Proteins…) →</a>
        </div>
        <p class="mt-1 text-xs text-ink/45">Tick which customization sections this product uses. Manage the actual choices under Menu Options.</p>
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @forelse($optionGroups as $group)
                <label class="flex items-center gap-2 rounded-2xl border border-munch/10 px-3 py-2 text-sm">
                    <input type="checkbox" name="option_groups[]" value="{{ $group->id }}"
                        @checked(collect(old('option_groups', $product->exists ? $product->optionGroups->pluck('id')->all() : []))->contains($group->id))>
                    {{ $group->name }}
                    <span class="ml-auto text-[10px] font-semibold text-ink/35">{{ $group->options_count ?? $group->options()->count() }}</span>
                </label>
            @empty
                <p class="sm:col-span-2 text-sm text-ink/50">
                    No option groups yet.
                    <a href="{{ route('admin.options.index') }}" class="font-bold text-box hover:underline">Create Serving Style, Bread, Proteins…</a>
                </p>
            @endforelse
        </div>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Save product</button>
        <a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>

<script>
    // Shrink large photos before upload so slow connections don't time out (408).
    const MAX_SIDE = 1600;
    const SKIP_BELOW_BYTES = 400 * 1024;
    let resizing = null;

    async function shrinkImage(file) {
        if (file.size < SKIP_BELOW_BYTES || !file.type.startsWith('image/')) return file;

        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        if (!blob || blob.size >= file.size) return file;

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg' });
    }

    document.getElementById('image')?.addEventListener('change', (event) => {
        const input = event.target;
        const file = input.files?.[0];
        const preview = document.getElementById('image-preview');
        if (!file || !preview) return;
        const url = URL.createObjectURL(file);
        preview.style.backgroundImage = `url('${url}')`;
        preview.classList.remove('bg-[#FAF8F4]');

        resizing = shrinkImage(file)
            .then((smaller) => {
                if (smaller === file) return;
                const transfer = new DataTransfer();
                transfer.items.add(smaller);
                input.files = transfer.files;
            })
            .catch(() => {})
            .finally(() => { resizing = null; });
    });

    document.getElementById('image')?.form.addEventListener('submit', async (event) => {
        if (!resizing) return;
        event.preventDefault();
        await resizing;
        event.target.submit();
    });
</script>
@endsection
