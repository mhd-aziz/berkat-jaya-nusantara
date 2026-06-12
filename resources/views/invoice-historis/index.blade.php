<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Invoice Historis / Transaksi Lama
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Catatan invoice pembelian dan penjualan sebelum sistem digitalisasi digunakan.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoice-historis.pembelian.create') }}"
                    class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    + Invoice Pembelian Lama
                </a>

                <a href="{{ route('invoice-historis.penjualan.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    + Invoice Penjualan Lama
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                {{ session('success') }}
            </div>
            @endif

            @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
                {{ session('error') }}
            </div>
            @endif

            <div class="mb-6 p-5 bg-yellow-50 border border-yellow-200 rounded-xl">
                <h3 class="font-semibold text-yellow-900 mb-1">
                    Catatan Penting
                </h3>
                <p class="text-sm text-yellow-800">
                    Invoice historis digunakan untuk mencatat transaksi lama sebelum sistem berjalan.
                    Data ini masuk ke laporan dan piutang jika kredit, tetapi tidak memengaruhi stok barang saat ini.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900">
                                Invoice Pembelian Lama
                            </h3>
                            <p class="text-sm text-gray-500">
                                Data pembelian lama dari supplier. Tidak menambah stok barang.
                            </p>
                        </div>

                        <a href="{{ route('invoice-historis.pembelian.create') }}"
                            class="text-sm text-purple-600 hover:underline">
                            Tambah
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">No</th>
                                    <th class="border px-3 py-2 text-left">No Sistem</th>
                                    <th class="border px-3 py-2 text-left">No Dokumen Asli</th>
                                    <th class="border px-3 py-2 text-left">Tanggal</th>
                                    <th class="border px-3 py-2 text-left">Supplier</th>
                                    <th class="border px-3 py-2 text-right">Total</th>
                                    <th class="border px-3 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($pembelianHistoris as $item)
                                <tr>
                                    <td class="border px-3 py-2">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        <div class="font-semibold">
                                            {{ $item->nomor_pembelian }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Historis
                                        </div>
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $item->nomor_dokumen_asli ?? '-' }}
                                    </td>

                                    <td class="border px-3 py-2 whitespace-nowrap">
                                        {{ $item->tanggal_pembelian ? $item->tanggal_pembelian->format('d-m-Y') : '-' }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $item->supplier->nama_supplier ?? '-' }}
                                    </td>

                                    <td class="border px-3 py-2 text-right font-semibold">
                                        Rp {{ number_format($item->total_akhir, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <a href="{{ route('invoice-historis.pembelian.show', ['pembelian' => $item->id_pembelian, 'back_url' => route('invoice-historis.index')]) }}"
                                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Detail / Print
                                            </a>

                                            <a href="{{ route('invoice-historis.pembelian.exportExcel', $item->id_pembelian) }}"
                                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                                Excel
                                            </a>

                                            <a href="{{ route('invoice-historis.pembelian.edit', $item->id_pembelian) }}"
                                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="border px-3 py-4 text-center text-gray-500">
                                        Belum ada invoice pembelian lama.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900">
                                Invoice Penjualan Lama
                            </h3>
                            <p class="text-sm text-gray-500">
                                Data penjualan lama kepada customer. Tidak mengurangi stok barang.
                            </p>
                        </div>

                        <a href="{{ route('invoice-historis.penjualan.create') }}"
                            class="text-sm text-blue-600 hover:underline">
                            Tambah
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">No</th>
                                    <th class="border px-3 py-2 text-left">No Sistem</th>
                                    <th class="border px-3 py-2 text-left">No Dokumen Asli</th>
                                    <th class="border px-3 py-2 text-left">Tanggal</th>
                                    <th class="border px-3 py-2 text-left">Customer</th>
                                    <th class="border px-3 py-2 text-center">Metode</th>
                                    <th class="border px-3 py-2 text-right">Total</th>
                                    <th class="border px-3 py-2 text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($penjualanHistoris as $item)
                                <tr>
                                    <td class="border px-3 py-2">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        <div class="font-semibold">
                                            {{ $item->nomor_invoice }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Historis
                                        </div>
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $item->nomor_dokumen_asli ?? '-' }}
                                    </td>

                                    <td class="border px-3 py-2 whitespace-nowrap">
                                        {{ $item->tanggal_penjualan ? $item->tanggal_penjualan->format('d-m-Y') : '-' }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $item->customer->nama_customer ?? '-' }}
                                    </td>

                                    <td class="border px-3 py-2 text-center">
                                        {{ ucfirst($item->metode_pembayaran) }}
                                    </td>

                                    <td class="border px-3 py-2 text-right font-semibold">
                                        Rp {{ number_format($item->total_akhir, 0, ',', '.') }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <a href="{{ route('invoice-historis.penjualan.show', ['penjualan' => $item->id_penjualan, 'back_url' => route('invoice-historis.index')]) }}"
                                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Detail / Print
                                            </a>

                                            <a href="{{ route('invoice-historis.penjualan.exportExcel', $item->id_penjualan) }}"
                                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                                Excel
                                            </a>

                                            <a href="{{ route('invoice-historis.penjualan.edit', $item->id_penjualan) }}"
                                                class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="border px-3 py-4 text-center text-gray-500">
                                        Belum ada invoice penjualan lama.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>