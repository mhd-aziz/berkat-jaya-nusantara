<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Stok Barang
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('laporan.stokBarang.exportExcel', request()->query()) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Export Excel
                </a>

                <a href="{{ route('laporan.stokBarang.exportPdf', request()->query()) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="GET" action="{{ route('laporan.stokBarang') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Status Barang</label>
                            <select name="status_barang"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="1" {{ request('status_barang') === '1' ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="0" {{ request('status_barang') === '0' ? 'selected' : '' }}>
                                    Nonaktif
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Kondisi Stok</label>
                            <select name="kondisi_stok"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="kosong" {{ request('kondisi_stok') === 'kosong' ? 'selected' : '' }}>
                                    Stok Kosong
                                </option>
                                <option value="rendah" {{ request('kondisi_stok') === 'rendah' ? 'selected' : '' }}>
                                    Stok Rendah
                                </option>
                                <option value="tersedia" {{ request('kondisi_stok') === 'tersedia' ? 'selected' : '' }}>
                                    Stok Tersedia
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Batas Stok Rendah</label>
                            <input type="number"
                                name="batas_stok_rendah"
                                value="{{ request('batas_stok_rendah', $batasStokRendah) }}"
                                min="1"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block mb-1 font-medium">Cari Barang</label>
                            <input type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Kode barang / nama barang / satuan..."
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <a href="{{ route('laporan.stokBarang') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Reset
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Jenis Barang</p>
                    <p class="text-2xl font-bold">{{ $totalBarang }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Stok</p>
                    <p class="text-2xl font-bold">{{ number_format($totalStok, 0, ',', '.') }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Barang Kosong</p>
                    <p class="text-2xl font-bold text-red-700">{{ $totalBarangKosong }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Stok Rendah</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $totalBarangStokRendah }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Estimasi Nilai Stok</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalNilaiStok, 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Estimasi Nilai Jual</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalEstimasiNilaiJual, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">No</th>
                                <th class="border px-3 py-2 text-left">Kode Barang</th>
                                <th class="border px-3 py-2 text-left">Nama Barang</th>
                                <th class="border px-3 py-2 text-center">Satuan</th>
                                <th class="border px-3 py-2 text-center">Stok</th>
                                <th class="border px-3 py-2 text-right">Harga Beli Terakhir</th>
                                <th class="border px-3 py-2 text-right">Nilai Stok</th>
                                <th class="border px-3 py-2 text-right">Harga Jual Default</th>
                                <th class="border px-3 py-2 text-right">Estimasi Nilai Jual</th>
                                <th class="border px-3 py-2 text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($barang as $item)
                            @php
                            $nilaiStok = $item->stok_saat_ini * ($item->harga_beli_terakhir ?? 0);
                            $estimasiNilaiJual = $item->stok_saat_ini * ($item->harga_jual_default ?? 0);

                            if ($item->stok_saat_ini <= 0) {
                                $statusStok='Kosong' ;
                                $statusClass='bg-red-100 text-red-700' ;
                                $rowClass='bg-red-50' ;
                                } elseif ($item->stok_saat_ini <= $batasStokRendah) {
                                    $statusStok='Stok Rendah' ;
                                    $statusClass='bg-yellow-100 text-yellow-700' ;
                                    $rowClass='bg-yellow-50' ;
                                    } else {
                                    $statusStok='Tersedia' ;
                                    $statusClass='bg-green-100 text-green-700' ;
                                    $rowClass='' ;
                                    }
                                    @endphp

                                    <tr class="{{ $rowClass }}">
                                    <td class="border px-3 py-2">
                                        {{ $barang->firstItem() + $loop->index }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $item->kode_barang }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        <div class="font-medium">{{ $item->nama_barang }}</div>
                                        @if (!$item->status_aktif)
                                        <div class="text-xs text-red-600">Barang nonaktif</div>
                                        @endif
                                    </td>

                                    <td class="border px-3 py-2 text-center">
                                        {{ $item->satuan }}
                                    </td>

                                    <td class="border px-3 py-2 text-center font-semibold">
                                        {{ number_format($item->stok_saat_ini, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        Rp {{ number_format($item->harga_beli_terakhir ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        Rp {{ number_format($nilaiStok, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        Rp {{ number_format($item->harga_jual_default ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2 text-right">
                                        Rp {{ number_format($estimasiNilaiJual, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2 text-center">
                                        <span class="px-2 py-1 text-xs rounded {{ $statusClass }}">
                                            {{ $statusStok }}
                                        </span>
                                    </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="border px-3 py-6 text-center text-gray-500">
                                            Data stok barang belum tersedia.
                                        </td>
                                    </tr>
                                    @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $barang->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>