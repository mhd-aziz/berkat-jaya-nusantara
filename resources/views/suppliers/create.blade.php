<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Supplier
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    <div class="font-semibold mb-1">
                        Data belum valid:
                    </div>

                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">Kode Supplier</label>
                        <input type="text"
                            value="{{ $kodeSupplier }}"
                            class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                            readonly>

                        <p class="text-sm text-gray-500 mt-1">
                            Kode supplier dibuat otomatis oleh sistem.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">
                            Nama Perusahaan Supplier <span class="text-red-600">*</span>
                        </label>

                        <input type="text"
                            name="nama_supplier"
                            value="{{ old('nama_supplier') }}"
                            placeholder="Contoh: PT Berkat Jaya Nusantara"
                            class="w-full border-gray-300 rounded-md shadow-sm @error('nama_supplier') border-red-500 @enderror"
                            required>

                        @error('nama_supplier')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <p class="text-sm text-gray-500 mt-1">
                            Nama perusahaan wajib diisi dan tidak boleh sama dengan supplier lain.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">Nomor Telepon</label>

                        <input type="text"
                            name="nomor_telepon"
                            value="{{ old('nomor_telepon') }}"
                            placeholder="Contoh: 08123456789"
                            class="w-full border-gray-300 rounded-md shadow-sm @error('nomor_telepon') border-red-500 @enderror">

                        @error('nomor_telepon')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <p class="text-sm text-gray-500 mt-1">
                            Opsional. Jika diisi, nomor telepon tidak boleh sama dengan supplier lain.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">NPWP Perusahaan</label>

                        <input type="text"
                            name="npwp"
                            value="{{ old('npwp') }}"
                            placeholder="Contoh: 01.234.567.8-999.000"
                            class="w-full border-gray-300 rounded-md shadow-sm @error('npwp') border-red-500 @enderror">

                        @error('npwp')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <p class="text-sm text-gray-500 mt-1">
                            Opsional. Jika diisi, NPWP tidak boleh sama dengan supplier lain.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">Alamat</label>

                        <textarea name="alamat"
                            rows="3"
                            placeholder="Alamat lengkap perusahaan supplier..."
                            class="w-full border-gray-300 rounded-md shadow-sm @error('alamat') border-red-500 @enderror">{{ old('alamat') }}</textarea>

                        @error('alamat')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        <p class="text-sm text-gray-500 mt-1">
                            Opsional. Jika diisi, alamat tidak boleh sama dengan supplier lain.
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 font-medium">Catatan</label>

                        <textarea name="catatan"
                            rows="3"
                            placeholder="Catatan tambahan tentang supplier..."
                            class="w-full border-gray-300 rounded-md shadow-sm @error('catatan') border-red-500 @enderror">{{ old('catatan') }}</textarea>

                        @error('catatan')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('suppliers.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>