<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    @php
    $oldIdBarang = old('id_barang');
    $oldJumlah = old('jumlah');
    $oldHargaBeli = old('harga_beli');

    $rows = [];

    if (is_array($oldIdBarang)) {
    foreach ($oldIdBarang as $index => $idBarang) {
    $rows[] = [
    'id_barang' => $idBarang,
    'jumlah' => $oldJumlah[$index] ?? 1,
    'harga_beli' => $oldHargaBeli[$index] ?? 0,
    ];
    }
    } else {
    foreach ($pembelian->detailPembelian as $detail) {
    $rows[] = [
    'id_barang' => $detail->id_barang,
    'jumlah' => $detail->jumlah,
    'harga_beli' => $detail->harga_beli,
    ];
    }
    }
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Invoice Pembelian Lama
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md">
                <strong>Catatan:</strong>
                Invoice pembelian lama adalah data historis. Perubahan data ini tidak akan menambah atau mengurangi stok barang.
            </div>

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

                <form action="{{ route('invoice-historis.pembelian.update', $pembelian->id_pembelian) }}" method="POST" id="formPembelianHistoris">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block mb-1 font-medium">Nomor Sistem</label>
                            <input type="text"
                                value="{{ $pembelian->nomor_pembelian }}"
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                                readonly>

                            <p class="text-sm text-gray-500 mt-1">
                                Nomor sistem historis tidak diubah.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">
                                Nomor Dokumen Asli <span class="text-red-600">*</span>
                            </label>
                            <input type="text"
                                name="nomor_dokumen_asli"
                                value="{{ old('nomor_dokumen_asli', $pembelian->nomor_dokumen_asli) }}"
                                placeholder="Contoh: NOTA-001 / INV lama"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>

                            <p class="text-sm text-gray-500 mt-1">
                                Isi sesuai nomor nota/invoice lama dari supplier.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Tanggal Pembelian Lama</label>
                            <input type="date"
                                name="tanggal_pembelian"
                                value="{{ old('tanggal_pembelian', $pembelian->tanggal_pembelian ? $pembelian->tanggal_pembelian->format('Y-m-d') : '') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Supplier</label>
                            <select name="id_supplier"
                                id="supplierSelect"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Cari supplier..."
                                required>
                                <option value="">-- Cari / Pilih Supplier --</option>

                                @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id_supplier }}"
                                    {{ old('id_supplier', $pembelian->id_supplier) == $supplier->id_supplier ? 'selected' : '' }}>
                                    {{ $supplier->kode_supplier }} - {{ $supplier->nama_supplier }}

                                    @if ($supplier->nomor_telepon)
                                    | {{ $supplier->nomor_telepon }}
                                    @endif

                                    @if ($supplier->npwp)
                                    | NPWP: {{ $supplier->npwp }}
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="font-semibold text-lg mb-2">Daftar Barang pada Invoice Lama</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200" id="tableBarang">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-3 py-2 text-left">Barang</th>
                                        <th class="border px-3 py-2 text-right">Jumlah</th>
                                        <th class="border px-3 py-2 text-right">Harga Beli</th>
                                        <th class="border px-3 py-2 text-right">Subtotal</th>
                                        <th class="border px-3 py-2 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($rows as $row)
                                    <tr>
                                        <td class="border px-3 py-2 min-w-[420px]">
                                            <select name="id_barang[]"
                                                class="w-full barang-select"
                                                placeholder="Cari kode atau nama barang..."
                                                required>
                                                <option value="">-- Cari / Pilih Barang --</option>

                                                @foreach ($barang as $item)
                                                <option value="{{ $item->id_barang }}"
                                                    data-harga="{{ $item->harga_beli_terakhir ?? 0 }}"
                                                    data-satuan="{{ $item->satuan }}"
                                                    {{ (string) $row['id_barang'] === (string) $item->id_barang ? 'selected' : '' }}>
                                                    {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                    | Stok saat ini: {{ $item->stok_saat_ini }} {{ strtoupper($item->satuan) }}
                                                    | Harga beli terakhir: Rp {{ number_format($item->harga_beli_terakhir ?? 0, 0, ',', '.') }}
                                                </option>
                                                @endforeach
                                            </select>

                                            <p class="text-sm text-gray-500 mt-1 satuan-info">
                                                Satuan: -
                                            </p>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="jumlah[]"
                                                value="{{ $row['jumlah'] }}"
                                                min="1"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                                required>

                                            <p class="text-xs text-gray-500 mt-1 satuan-jumlah-info text-right">
                                                -
                                            </p>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="harga_beli[]"
                                                value="{{ $row['harga_beli'] }}"
                                                min="0"
                                                step="0.01"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                                required>

                                            <p class="text-xs text-gray-500 mt-1 text-right">
                                                / satuan
                                            </p>
                                        </td>

                                        <td class="border px-3 py-2 text-right min-w-[160px]">
                                            <span class="subtotal-text">Rp 0</span>
                                        </td>

                                        <td class="border px-3 py-2 text-center">
                                            <button type="button"
                                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 btn-hapus">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <template id="templateBarangRow">
                            <tr>
                                <td class="border px-3 py-2 min-w-[420px]">
                                    <select name="id_barang[]"
                                        class="w-full barang-select"
                                        placeholder="Cari kode atau nama barang..."
                                        required>
                                        <option value="">-- Cari / Pilih Barang --</option>

                                        @foreach ($barang as $item)
                                        <option value="{{ $item->id_barang }}"
                                            data-harga="{{ $item->harga_beli_terakhir ?? 0 }}"
                                            data-satuan="{{ $item->satuan }}">
                                            {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                            | Stok saat ini: {{ $item->stok_saat_ini }} {{ strtoupper($item->satuan) }}
                                            | Harga beli terakhir: Rp {{ number_format($item->harga_beli_terakhir ?? 0, 0, ',', '.') }}
                                        </option>
                                        @endforeach
                                    </select>

                                    <p class="text-sm text-gray-500 mt-1 satuan-info">
                                        Satuan: -
                                    </p>
                                </td>

                                <td class="border px-3 py-2">
                                    <input type="number"
                                        name="jumlah[]"
                                        value="1"
                                        min="1"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                        required>

                                    <p class="text-xs text-gray-500 mt-1 satuan-jumlah-info text-right">
                                        -
                                    </p>
                                </td>

                                <td class="border px-3 py-2">
                                    <input type="number"
                                        name="harga_beli[]"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                        required>

                                    <p class="text-xs text-gray-500 mt-1 text-right">
                                        / satuan
                                    </p>
                                </td>

                                <td class="border px-3 py-2 text-right min-w-[160px]">
                                    <span class="subtotal-text">Rp 0</span>
                                </td>

                                <td class="border px-3 py-2 text-center">
                                    <button type="button"
                                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 btn-hapus">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <button type="button"
                            id="btnTambahBarang"
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            + Tambah Baris Barang
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block mb-1 font-medium">Catatan</label>
                            <textarea name="catatan"
                                rows="4"
                                class="w-full border-gray-300 rounded-md shadow-sm">{{ old('catatan', $pembelian->catatan) }}</textarea>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md border">
                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Persentase Pajak (%)</label>
                                <input type="number"
                                    name="persentase_pajak"
                                    id="persentasePajak"
                                    value="{{ old('persentase_pajak', $pembelian->persentase_pajak) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-right">
                            </div>

                            <div class="mb-4">
                                <label class="block mb-2 font-medium">Perhitungan Pajak</label>

                                <div class="space-y-2">
                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                            name="pajak_ditambahkan"
                                            value="1"
                                            class="mt-1"
                                            {{ old('pajak_ditambahkan', ($pembelian->pajak_ditambahkan ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak ditambahkan ke total</strong>
                                        </span>
                                    </label>

                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                            name="pajak_ditambahkan"
                                            value="0"
                                            class="mt-1"
                                            {{ old('pajak_ditambahkan', ($pembelian->pajak_ditambahkan ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak hanya ditampilkan</strong>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-between mb-2">
                                <span>Subtotal</span>
                                <strong id="totalSubtotal">Rp 0</strong>
                            </div>

                            <div class="flex justify-between mb-2">
                                <span>Nilai Pajak</span>
                                <strong id="totalPajak">Rp 0</strong>
                            </div>

                            <div class="flex justify-between border-t pt-2 text-lg">
                                <span>Total Akhir</span>
                                <strong id="totalAkhir">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <a href="{{ route('invoice-historis.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
                            onclick="return confirm('Update invoice pembelian lama? Data ini tetap tidak akan memengaruhi stok.')">
                            Update Invoice Lama
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }

        function initSupplierSelect() {
            const supplierSelect = document.getElementById('supplierSelect');

            if (!supplierSelect || supplierSelect.tomselect) {
                return;
            }

            new TomSelect(supplierSelect, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                searchField: ['text'],
                placeholder: 'Cari supplier...'
            });
        }

        function initBarangSelect(selectElement) {
            if (!selectElement || selectElement.tomselect) {
                return;
            }

            new TomSelect(selectElement, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                searchField: ['text'],
                placeholder: 'Cari kode atau nama barang...',
                onChange: function() {
                    const row = selectElement.closest('tr');
                    updateBarangInfo(row);
                    hitungTotal();
                }
            });
        }

        function initAllBarangSelect() {
            document.querySelectorAll('.barang-select').forEach(function(select) {
                initBarangSelect(select);
            });
        }

        function getSelectedOption(selectElement) {
            if (!selectElement || !selectElement.value) {
                return null;
            }

            return selectElement.querySelector('option[value="' + selectElement.value + '"]');
        }

        function updateBarangInfo(row) {
            if (!row) {
                return;
            }

            const select = row.querySelector('.barang-select');
            const selectedOption = getSelectedOption(select);

            const satuanInfo = row.querySelector('.satuan-info');
            const satuanJumlahInfo = row.querySelector('.satuan-jumlah-info');

            if (!selectedOption) {
                satuanInfo.innerText = 'Satuan: -';
                satuanJumlahInfo.innerText = '-';
                return;
            }

            const satuan = selectedOption.getAttribute('data-satuan') || '';

            satuanInfo.innerText = satuan ? 'Satuan: ' + satuan.toUpperCase() : 'Satuan: -';
            satuanJumlahInfo.innerText = satuan ? satuan.toUpperCase() : '-';
        }

        function hitungTotal() {
            let totalSubtotal = 0;

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                const jumlah = parseFloat(row.querySelector('.jumlah-input').value) || 0;
                const harga = parseFloat(row.querySelector('.harga-input').value) || 0;
                const subtotal = jumlah * harga;

                row.querySelector('.subtotal-text').innerText = formatRupiah(subtotal);
                totalSubtotal += subtotal;
            });

            const persentasePajak = parseFloat(document.getElementById('persentasePajak').value) || 0;
            const nilaiPajak = totalSubtotal * (persentasePajak / 100);

            const pajakDitambahkanInput = document.querySelector('input[name="pajak_ditambahkan"]:checked');
            const pajakDitambahkan = pajakDitambahkanInput ? pajakDitambahkanInput.value === '1' : true;

            const totalAkhir = pajakDitambahkan ? totalSubtotal + nilaiPajak : totalSubtotal;

            document.getElementById('totalSubtotal').innerText = formatRupiah(totalSubtotal);
            document.getElementById('totalPajak').innerText = formatRupiah(nilaiPajak);
            document.getElementById('totalAkhir').innerText = formatRupiah(totalAkhir);
        }

        document.addEventListener('input', function(e) {
            if (
                e.target.classList.contains('jumlah-input') ||
                e.target.classList.contains('harga-input') ||
                e.target.id === 'persentasePajak' ||
                e.target.name === 'pajak_ditambahkan'
            ) {
                hitungTotal();
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.name === 'pajak_ditambahkan') {
                hitungTotal();
            }
        });

        document.getElementById('btnTambahBarang').addEventListener('click', function() {
            const tbody = document.querySelector('#tableBarang tbody');
            const template = document.getElementById('templateBarangRow');
            const newRow = template.content.cloneNode(true);

            tbody.appendChild(newRow);

            const rows = tbody.querySelectorAll('tr');
            const lastRow = rows[rows.length - 1];

            initBarangSelect(lastRow.querySelector('.barang-select'));
            updateBarangInfo(lastRow);
            hitungTotal();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-hapus')) {
                const tbody = document.querySelector('#tableBarang tbody');

                if (tbody.querySelectorAll('tr').length <= 1) {
                    alert('Minimal harus ada satu barang.');
                    return;
                }

                const row = e.target.closest('tr');
                const select = row.querySelector('.barang-select');

                if (select && select.tomselect) {
                    select.tomselect.destroy();
                }

                row.remove();
                hitungTotal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initSupplierSelect();
            initAllBarangSelect();

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                updateBarangInfo(row);
            });

            hitungTotal();
        });
    </script>
</x-app-layout>