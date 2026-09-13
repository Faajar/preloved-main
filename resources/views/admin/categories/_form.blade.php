<div class="grid gap-5">
    <div>
        <label for="name" class="form-label">Nama kategori</label>
        <input id="name" name="name" type="text" value="{{ old('name', $category->name ?? '') }}" required maxlength="100" class="input-field">
        <p class="mt-2 text-sm text-stone-500">Slug dibuat otomatis saat kategori dibuat dan tidak berubah ketika nama diedit agar URL lama tetap valid.</p>
        @error('name')<p class="form-error">{{ $message }}</p>@enderror
        @error('slug')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="form-label">Deskripsi</label>
        <textarea id="description" name="description" rows="4" maxlength="1000" class="input-field">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="reason" class="form-label">Alasan perubahan</label>
        <textarea id="reason" name="reason" rows="3" maxlength="1000" required class="input-field">{{ old('reason') }}</textarea>
        <p class="mt-2 text-sm text-stone-500">Alasan disimpan pada histori audit admin.</p>
        @error('reason')<p class="form-error">{{ $message }}</p>@enderror
    </div>
</div>
