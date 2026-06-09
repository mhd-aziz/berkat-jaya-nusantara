<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Piutang
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('laporan.piutang.exportExcel', request()->query()) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Export Excel
                </a>

                <a href="{{ route('laporan.piutang.exportPdf', request()->query()) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="GET" action="{{ route('laporan.piutang') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block mb-1 font-medium">Jatuh Tempo Awal</label>
                            <input type="date"
                                name="tanggal_awal"
                                value="{{ request('tanggal_awal') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Jatuh Tempo Akhir</label>
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
                            <label class="block mb-1 font-medium">Status Piutang</label>
                            <select name="status_piutang"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="belum_lunas" {{ request('status_piutang') === 'belum_lunas' ? 'selected' : '' }}>
                                    Belum Lunas
                                </option>
                                <option value="sebagian_dibayar" {{ request('status_piutang') === 'sebagian_dibayar' ? 'selected' : '' }}>
                                    Sebagian Dibayar
                                </option>
                                <option value="lunas" {{ request('status_piutang') === 'lunas' ? 'selected' : '' }}>
                                    Lunas
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Jatuh Tempo</label>
                            <select name="jatuh_tempo"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Semua</option>
                                <option value="lewat" {{ request('jatuh_tempo') === 'lewat' ? 'selected' : '' }}>
                                    Lewat Jatuh Tempo
                                </option>
                                <option value="belum" {{ request('jatuh_tempo') === 'belum' ? 'selected' : '' }}>
                                    Belum Lewat
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
                        <a href="{{ route('laporan.piutang') }}"
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
                    <p class="text-sm text-gray-500">Total Data</p>
                    <p class="text-2xl font-bold">{{ $totalData }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Piutang</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Total Dibayar</p>
                    <p class="text-2xl font-bold">
                        Rp {{ number_format($totalDibayar, 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Sisa Piutang</p>
                    <p class="text-2xl font-bold text-red-700">
                        Rp {{ number_format($totalSisa, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Belum Lunas</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $totalBelumLunas }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Sebagian Dibayar</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $totalSebagian }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Lunas</p>
                    <p class="text-2xl font-bold text-green-700">{{ $totalLunas }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-500">Lewat Jatuh Tempo</p>
                    <p class="text-2xl font-bold text-red-700">{{ $totalLewatJatuhTempo }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left">No</th>
                                <th class="border px-3 py-2 text-left">Invoice</th>
                                <th class="border px-3 py-2 text-left">Customer</th>
                                <th class="border px-3 py-2 text-left">Jatuh Tempo</th>
                                <th class="border px-3 py-2 text-left">Status</th>
                                <th class="border px-3 py-2 text-right">Total Piutang</th>
                                <th class="border px-3 py-2 text-right">Dibayar</th>
                                <th class="border px-3 py-2 text-right">Sisa</th>
                                <th class="border px-3 py-2 text-center">Keterangan</th>
                                <th class="border px-3 py-2 text-center">Detail</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($piutang as $item)
                            @php
                            $lewatJatuhTempo = $item->status_piutang !== 'lunas'
                            && $item->tanggal_jatuh_tempo
                            && $item->tanggal_jatuh_tempo->isPast();
                            @endphp

                            <tr class="{{ $lewatJatuhTempo ? 'bg-red-50' : '' }}">
                                <td class="border px-3 py-2">
                                    {{ $piutang->firstItem() + $loop->index }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->nomor_invoice }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->customer->nama_customer ?? '-' }}
                                </td>

                                <td class="border px-3 py-2">
                                    {{ $item->tanggal_jatuh_tempo ? $item->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
                                </td>

                                <td class="border px-3 py-2">
                                    @if ($item->status_piutang === 'lunas')
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                        Lunas
                                    </span>
                                    @elseif ($item->status_piutang === 'sebagian_dibayar')
                                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">
                                        Sebagian Dibayar
                                    </span>
                                    @else
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                        Belum Lunas
                                    </span>
                                    @endif
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    Rp {{ number_format($item->total_piutang, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-right">
                                    Rp {{ number_format($item->total_dibayar, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-right font-semibold">
                                    Rp {{ number_format($item->sisa_piutang, 0, ',', '.') }}
                                </td>

                                <td class="border px-3 py-2 text-center">
                                    @if ($item->status_piutang === 'lunas')
                                    <span class="text-green-700 font-semibold">
                                        Selesai
                                    </span>
                                    @elseif ($lewatJatuhTempo)
                                    <span class="text-red-700 font-semibold">
                                        Lewat Tempo
                                    </span>
                                    @else
                                    <span class="text-yellow-700 font-semibold">
                                        Berjalan
                                    </span>
                                    @endif
                                </td>

                                <td class="border px-3 py-2 text-center">
                                    <a href="{{ route('piutang.show', ['piutang' => $item->id_piutang, 'back_url' => request()->fullUrl()]) }}"
                                        class="text-blue-600 hover:underline">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="border px-3 py-6 text-center text-gray-500">
                                    Data laporan piutang belum tersedia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $piutang->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>