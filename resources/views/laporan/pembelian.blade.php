<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Pembelian
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('laporan.pembelian.exportExcel', request()->query()) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Export Excel
                </a>

                <a href="{{ route('laporan.pembelian.exportPdf', request()->query()) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="GET" action="{{ route('laporan.pembelian') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Tanggal Awal</label>
                            <input type="date"
                                name="tanggal_awal"
                                value="{{ request('tanggal_awal') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Tanggal Akhir</label>
                            <input type="date"
                                name="tanggal_akhir"
                                value="{{ request('tanggal_akhir') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Supplier</label>
                            <select name="id_supplier"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id_supplier }}"
                                    {{ request('id_supplier') == $supplier->id_supplier ? 'selected' : '' }}>
                                    {{ $supplier->nama_supplier }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Status Penerimaan</label>
                            <select name="status_penerimaan"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="lengkap" {{ request('status_penerimaan') === 'lengkap' ? 'selected' : '' }}>
                                    Lengkap
                                </option>
                                <option value="sebagian" {{ request('status_penerimaan') === 'sebagian' ? 'selected' : '' }}>
                                    Sebagian
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Cari</label>
                            <input type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Nomor/supplier..."
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <a href="{{ route('laporan.pembelian') }}"
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
                    <p class="text-sm text-gray-500">Total Transaksi</p>
                    <p class="text-2xl font-bold">{{ $totalTransaksi }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Subtotal</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalSubtotal, 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Pajak</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalPajak, 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Akhir</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalAkhir, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Barang Dipesan</p>
                    <p class="text-2xl font-bold">{{ $totalDipesan }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Barang Diterima</p>
                    <p class="text-2xl font-bold">{{ $totalDiterima }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Sisa Belum Dikirim</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $totalSisa }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">No</th>
                                <th class="border px-3 py-2 text-left">Tanggal</th>
                                <th class="border px-3 py-2 text-left">No Pembelian</th>
                                <th class="border px-3 py-2 text-left">Supplier</th>
                                <th class="border px-3 py-2 text-left">Status</th>
                                <th class="border px-3 py-2 text-right">Dipesan</th>
                                <th class="border px-3 py-2 text-right">Diterima</th>
                                <th class="border px-3 py-2 text-right">Sisa</th>
                                <th class="border px-3 py-2 text-right">Subtotal</th>
                                <th class="border px-3 py-2 text-right">Pajak</th>
                                <th class="border px-3 py-2 text-right">Total Akhir</th>
                                <th class="border px-3 py-2 text-center">Detail</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($pembelian as $item)
                            @php
                            $jumlahDipesan = 0;
                            $jumlahDiterima = 0;

                            foreach ($item->detailPembelian as $detail) {
                            $jumlahDipesan += $detail->jumlah_dipesan ?? $detail->jumlah;
                            $jumlahDiterima += $detail->jumlah;
                            }

                            $sisa = max($jumlahDipesan - $jumlahDiterima, 0);
                            @endphp

                            <tr>
                                <td class="border px-3 py-2">
                                    {{ $pembelian->firstItem() + $loop->index }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->tanggal_pembelian->format('d-m-Y') }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->nomor_pembelian }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->supplier->nama_supplier ?? '-' }}
                                </td>

                                <td class="border px-3 py-2">
                                    @if (($item->status_penerimaan ?? 'lengkap') === 'lengkap')
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                        Lengkap
                                    </span>
                                    @elseif ($item->status_penerimaan === 'sebagian')
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                        Sebagian
                                    </span>
                                    @else
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                        Belum Dikirim
                                    </span>
                                    @endif
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ $jumlahDipesan }}
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    {{ $jumlahDiterima }}
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    @if ($sisa > 0)
                                    <span class="text-yellow-700 font-semibold">{{ $sisa }}</span>
                                    @else
                                    0
                                    @endif
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    Rp {{ number_format($item->nilai_pajak, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-right font-semibold">
                                    Rp {{ number_format($item->total_akhir, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-center">
                                    <a href="{{ route('pembelian.show', ['pembelian' => $item->id_pembelian, 'back_url' => request()->fullUrl()]) }}"
                                        class="text-blue-600 hover:underline">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="border px-3 py-6 text-center text-gray-500">
                                    Data laporan pembelian belum tersedia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pembelian->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>