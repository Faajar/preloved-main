@php($editing = isset($product))

<div class="grid gap-5">
    <div>
        <label for="name" class="form-label">Nama barang</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product->name ?? '') }}" required maxlength="255" class="input-field">
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="category_id" class="form-label">Kategori</label>
            <select id="category_id" name="category_id" required class="input-field">
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id) @disabled(! $category->is_active)>
                        {{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif — pilih kategori baru)' }}
                    </option>
                @endforeach
            </select>
            @if ($editing && ! $product->category->is_active)
                <p class="mt-2 text-sm font-medium text-amber-700">Kategori lama sudah dinonaktifkan. Pilih kategori aktif sebelum menyimpan perubahan.</p>
            @endif
            @error('category_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="condition" class="form-label">Kondisi</label>
            <select id="condition" name="condition" required class="input-field">
                <option value="">Pilih kondisi</option>
                @foreach (App\ProductCondition::cases() as $condition)
                    <option value="{{ $condition->value }}" @selected(old('condition', isset($product) ? $product->condition->value : '') === $condition->value)>{{ $condition->label() }}</option>
                @endforeach
            </select>
            @error('condition')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="price" class="form-label">Harga barang (rupiah)</label>
            <input id="price" name="price" type="number" min="1" step="1" value="{{ old('price', $product->price ?? '') }}" required class="input-field">
            @error('price')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="location" class="form-label">Lokasi</label>
            <input id="location" name="location" type="text" maxlength="255" value="{{ old('location', $product->location ?? '') }}" required class="input-field">
            @error('location')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="description" class="form-label">Deskripsi</label>
        <textarea id="description" name="description" rows="6" maxlength="5000" required class="input-field">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @unless ($editing)
        @if (! auth()->user()->whatsapp_number)
            <div>
                <label for="whatsapp_number" class="form-label">Nomor WhatsApp</label>
                <input id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number') }}" placeholder="081234567890" required class="input-field">
                <p class="mt-2 text-sm text-stone-500">Disimpan di profil seller dan digunakan pada tombol WhatsApp di listing publik.</p>
                @error('whatsapp_number')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @else
            <div class="rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                Listing menggunakan WhatsApp profil: <strong>+{{ auth()->user()->whatsapp_number }}</strong>
            </div>
        @endif

        <div>
            <label for="images" class="form-label">Foto barang</label>
            <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="input-field file:mr-3 file:rounded-md file:border-0 file:bg-stone-100 file:px-3 file:py-2 file:font-medium">
            <p class="mt-2 text-sm text-stone-500">1–5 foto, format JPG, PNG, atau WebP, maksimal 5 MB per foto.</p>
            @error('images')<p class="form-error">{{ $message }}</p>@enderror
            @error('images.*')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    @endunless
</div>
