<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Penjualan
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('laporan.penjualan.exportExcel', request()->query()) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Export Excel
                </a>

                <a href="{{ route('laporan.penjualan.exportPdf', request()->query()) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="GET" action="{{ route('laporan.penjualan') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
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
                            <label class="block mb-1 font-medium">Customer</label>
                            <select name="id_customer"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua Customer</option>
                                @foreach ($customers as $customer)
                                <option value="{{ $customer->id_customer }}"
                                    {{ request('id_customer') == $customer->id_customer ? 'selected' : '' }}>
                                    {{ $customer->nama_customer }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Metode Pembayaran</label>
                            <select name="metode_pembayaran"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="tunai" {{ request('metode_pembayaran') === 'tunai' ? 'selected' : '' }}>
                                    Tunai
                                </option>
                                <option value="transfer" {{ request('metode_pembayaran') === 'transfer' ? 'selected' : '' }}>
                                    Transfer
                                </option>
                                <option value="giro" {{ request('metode_pembayaran') === 'giro' ? 'selected' : '' }}>
                                    Giro
                                </option>
                                <option value="lainnya" {{ request('metode_pembayaran') === 'lainnya' ? 'selected' : '' }}>
                                    Lainnya
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Status Pembayaran</label>
                            <select name="status_pembayaran"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="lunas" {{ request('status_pembayaran') === 'lunas' ? 'selected' : '' }}>
                                    Lunas
                                </option>
                                <option value="sebagian" {{ request('status_pembayaran') === 'sebagian' ? 'selected' : '' }}>
                                    Sebagian
                                </option>
                                <option value="belum_lunas" {{ request('status_pembayaran') === 'belum_lunas' ? 'selected' : '' }}>
                                    Belum Lunas
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Cari</label>
                            <input type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Invoice/customer..."
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <a href="{{ route('laporan.penjualan') }}"
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
                    <p class="text-2xl font-bold text-green-700">
                        Rp {{ number_format($totalAkhir, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">No</th>
                                <th class="border px-3 py-2 text-left">Tanggal</th>
                                <th class="border px-3 py-2 text-left">Nomor Invoice</th>
                                <th class="border px-3 py-2 text-left">Customer</th>
                                <th class="border px-3 py-2 text-left">Metode</th>
                                <th class="border px-3 py-2 text-left">Status</th>
                                <th class="border px-3 py-2 text-right">Subtotal</th>
                                <th class="border px-3 py-2 text-right">Pajak</th>
                                <th class="border px-3 py-2 text-right">Total Akhir</th>
                                <th class="border px-3 py-2 text-center">Detail</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($penjualan as $item)
                            <tr>
                                <td class="border px-3 py-2">
                                    {{ $penjualan->firstItem() + $loop->index }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->tanggal_penjualan ? $item->tanggal_penjualan->format('d-m-Y') : '-' }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->nomor_invoice }}
                                </td>

                                <td class="border px-3 py-2">
                                    <div class="font-medium">
                                        {{ $item->customer->nama_customer ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $item->customer->nomor_telepon ?? '-' }}
                                    </div>
                                </td>

                                <td class="border px-3 py-2">
                                    {{ ucfirst($item->metode_pembayaran) }}
                                </td>

                                <td class="border px-3 py-2">
                                    @if ($item->status_pembayaran === 'lunas')
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                        Lunas
                                    </span>
                                    @elseif ($item->status_pembayaran === 'sebagian')
                                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">
                                        Sebagian
                                    </span>
                                    @else
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                        Belum Lunas
                                    </span>
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
                                    <a href="{{ route('penjualan.show', ['penjualan' => $item->id_penjualan, 'back_url' => request()->fullUrl()]) }}"
                                        class="text-blue-600 hover:underline">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="border px-3 py-6 text-center text-gray-500">
                                    Data laporan penjualan belum tersedia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $penjualan->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>