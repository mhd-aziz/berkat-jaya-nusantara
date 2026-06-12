<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    @php
    $oldIdBarang = old('id_barang');
    $oldJumlahDipesan = old('jumlah_dipesan');
    $oldJumlah = old('jumlah');
    $oldHargaBeli = old('harga_beli');

    $rows = [];

    if (is_array($oldIdBarang)) {
    foreach ($oldIdBarang as $index => $idBarang) {
    $rows[] = [
    'id_barang' => $idBarang,
    'jumlah_dipesan' => $oldJumlahDipesan[$index] ?? 1,
    'jumlah' => $oldJumlah[$index] ?? 1,
    'harga_beli' => $oldHargaBeli[$index] ?? 0,
    ];
    }
    } else {
    foreach ($pembelian->detailPembelian as $detail) {
    $rows[] = [
    'id_barang' => $detail->id_barang,
    'jumlah_dipesan' => $detail->jumlah_dipesan ?? $detail->jumlah,
    'jumlah' => $detail->jumlah,
    'harga_beli' => $detail->harga_beli,
    ];
    }
    }
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Pembelian / Barang Masuk
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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

                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md text-sm">
                    <strong>Perhatian:</strong>
                    Saat pembelian diedit, sistem akan mengembalikan stok lama terlebih dahulu, lalu menghitung ulang stok berdasarkan data pembelian terbaru.
                    Jika stok lama sudah terpakai/dijual sehingga tidak cukup untuk dikembalikan, pembelian tidak bisa diedit sampai stoknya disesuaikan.
                </div>

                <form action="{{ route('pembelian.update', $pembelian->id_pembelian) }}" method="POST" id="formPembelian">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block mb-1 font-medium">
                                Nomor Invoice / Nota Pembelian <span class="text-red-600">*</span>
                            </label>

                            <input type="text"
                                name="nomor_pembelian"
                                value="{{ old('nomor_pembelian', $pembelian->nomor_pembelian) }}"
                                placeholder="Contoh: INV-SUP-001 atau PB-20260612-0001"
                                class="w-full border-gray-300 rounded-md shadow-sm @error('nomor_pembelian') border-red-500 @enderror"
                                required>

                            @error('nomor_pembelian')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror

                            <p class="text-sm text-gray-500 mt-1">
                                Nomor diisi manual sesuai nota/invoice supplier. Tidak boleh sama.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">
                                Nomor Delivery Order Supplier
                            </label>

                            <input type="text"
                                name="nomor_delivery_order"
                                value="{{ old('nomor_delivery_order', $pembelian->nomor_delivery_order) }}"
                                placeholder="Contoh: DO-SUP-20260612-0001"
                                class="w-full border-gray-300 rounded-md shadow-sm @error('nomor_delivery_order') border-red-500 @enderror">

                            @error('nomor_delivery_order')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror

                            <p class="text-sm text-gray-500 mt-1">
                                Opsional. Isi sesuai dokumen DO dari supplier.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">
                                Nomor Surat Jalan Supplier
                            </label>

                            <input type="text"
                                name="nomor_surat_jalan"
                                value="{{ old('nomor_surat_jalan', $pembelian->nomor_surat_jalan) }}"
                                placeholder="Contoh: SJ-SUP-20260612-0001"
                                class="w-full border-gray-300 rounded-md shadow-sm @error('nomor_surat_jalan') border-red-500 @enderror">

                            @error('nomor_surat_jalan')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror

                            <p class="text-sm text-gray-500 mt-1">
                                Opsional. Isi sesuai surat jalan dari supplier.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Tanggal Pembelian</label>
                            <input type="date"
                                name="tanggal_pembelian"
                                value="{{ old('tanggal_pembelian', $pembelian->tanggal_pembelian->format('Y-m-d')) }}"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1 font-medium">Supplier</label>

                            <select name="id_supplier"
                                id="supplierSelect"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Cari kode atau nama supplier..."
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
                        <h3 class="font-semibold text-lg mb-2">Daftar Barang Dibeli</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200" id="tableBarang">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border px-3 py-2 text-left">Barang</th>
                                        <th class="border px-3 py-2 text-right">Jumlah Dipesan</th>
                                        <th class="border px-3 py-2 text-right">Jumlah Diterima</th>
                                        <th class="border px-3 py-2 text-right">Harga Beli</th>
                                        <th class="border px-3 py-2 text-right">Subtotal Diterima</th>
                                        <th class="border px-3 py-2 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($rows as $row)
                                    <tr>
                                        <td class="border px-3 py-2 min-w-[360px]">
                                            <select name="id_barang[]"
                                                class="w-full barang-select"
                                                placeholder="Cari kode atau nama barang..."
                                                required>
                                                <option value="">-- Cari / Pilih Barang --</option>

                                                @foreach ($barang as $item)
                                                <option value="{{ $item->id_barang }}"
                                                    {{ (string) $row['id_barang'] === (string) $item->id_barang ? 'selected' : '' }}>
                                                    {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                    | Stok Saat Ini: {{ $item->stok_saat_ini }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="jumlah_dipesan[]"
                                                value="{{ $row['jumlah_dipesan'] }}"
                                                min="1"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-dipesan-input"
                                                required>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="jumlah[]"
                                                value="{{ $row['jumlah'] }}"
                                                min="0"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                                required>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="harga_beli[]"
                                                value="{{ $row['harga_beli'] }}"
                                                min="0"
                                                step="0.01"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                                required>
                                        </td>

                                        <td class="border px-3 py-2 text-right">
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

                            <template id="templateBarangRow">
                                <tr>
                                    <td class="border px-3 py-2 min-w-[360px]">
                                        <select name="id_barang[]"
                                            class="w-full barang-select"
                                            placeholder="Cari kode atau nama barang..."
                                            required>
                                            <option value="">-- Cari / Pilih Barang --</option>

                                            @foreach ($barang as $item)
                                            <option value="{{ $item->id_barang }}">
                                                {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                | Stok Saat Ini: {{ $item->stok_saat_ini }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="jumlah_dipesan[]"
                                            value="1"
                                            min="1"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-dipesan-input"
                                            required>
                                    </td>

                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="jumlah[]"
                                            value="1"
                                            min="0"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                            required>
                                    </td>

                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="harga_beli[]"
                                            value="0"
                                            min="0"
                                            step="0.01"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                            required>
                                    </td>

                                    <td class="border px-3 py-2 text-right">
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
                        </div>

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

                                <p class="text-sm text-gray-500 mt-1">
                                    Pajak tetap bisa ditampilkan, walaupun tidak ditambahkan ke total akhir.
                                </p>
                            </div>

                            <div class="mb-4">
                                <label class="block mb-2 font-medium">Perhitungan Pajak</label>

                                <div class="space-y-2">
                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                            name="pajak_ditambahkan"
                                            value="1"
                                            class="mt-1"
                                            {{ old('pajak_ditambahkan', $pembelian->pajak_ditambahkan ? '1' : '0') == '1' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak ditambahkan ke total</strong>
                                            <br>
                                            <small class="text-gray-500">
                                                Untuk pembelian dari supplier yang memang dikenakan pajak.
                                            </small>
                                        </span>
                                    </label>

                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                            name="pajak_ditambahkan"
                                            value="0"
                                            class="mt-1"
                                            {{ old('pajak_ditambahkan', $pembelian->pajak_ditambahkan ? '1' : '0') == '0' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak hanya ditampilkan</strong>
                                            <br>
                                            <small class="text-gray-500">
                                                Untuk pembelian yang pajaknya hanya ingin dicatat/ditampilkan,
                                                tetapi tidak menambah total.
                                            </small>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-between mb-2">
                                <span>Subtotal Barang Diterima</span>
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
                        <a href="{{ route('pembelian.show', $pembelian->id_pembelian) }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            onclick="return confirm('Update transaksi pembelian ini? Stok barang akan disesuaikan ulang berdasarkan data terbaru.')">
                            Update Pembelian
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
                placeholder: 'Cari kode, nama, nomor telepon, atau NPWP supplier...'
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
                placeholder: 'Cari kode atau nama barang...'
            });
        }

        function initAllBarangSelect() {
            document.querySelectorAll('.barang-select').forEach(function(select) {
                initBarangSelect(select);
            });
        }

        function hitungTotal() {
            let totalSubtotal = 0;

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                const jumlahDipesanInput = row.querySelector('.jumlah-dipesan-input');
                const jumlahDiterimaInput = row.querySelector('.jumlah-input');
                const hargaInput = row.querySelector('.harga-input');

                const jumlahDipesan = parseFloat(jumlahDipesanInput.value) || 0;
                const jumlahDiterima = parseFloat(jumlahDiterimaInput.value) || 0;
                const harga = parseFloat(hargaInput.value) || 0;

                if (jumlahDiterima > jumlahDipesan) {
                    jumlahDiterimaInput.value = jumlahDipesan;
                }

                const jumlahFinal = parseFloat(jumlahDiterimaInput.value) || 0;
                const subtotal = jumlahFinal * harga;

                row.querySelector('.subtotal-text').innerText = formatRupiah(subtotal);
                totalSubtotal += subtotal;
            });

            const persentasePajak = parseFloat(document.getElementById('persentasePajak').value) || 0;
            const nilaiPajak = totalSubtotal * (persentasePajak / 100);

            const pajakDitambahkanInput = document.querySelector('input[name="pajak_ditambahkan"]:checked');
            const pajakDitambahkan = pajakDitambahkanInput ? pajakDitambahkanInput.value === '1' : true;

            const totalAkhir = pajakDitambahkan ?
                totalSubtotal + nilaiPajak :
                totalSubtotal;

            document.getElementById('totalSubtotal').innerText = formatRupiah(totalSubtotal);
            document.getElementById('totalPajak').innerText = formatRupiah(nilaiPajak);
            document.getElementById('totalAkhir').innerText = formatRupiah(totalAkhir);
        }

        document.addEventListener('input', function(e) {
            if (
                e.target.classList.contains('jumlah-dipesan-input') ||
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
            const select = lastRow.querySelector('.barang-select');

            initBarangSelect(select);
            hitungTotal();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-hapus')) {
                const tbody = document.querySelector('#tableBarang tbody');

                if (tbody.querySelectorAll('tr').length <= 1) {
                    alert('Minimal harus ada satu barang dalam transaksi pembelian.');
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

        document.getElementById('formPembelian').addEventListener('submit', function(e) {
            let valid = true;
            let pesan = '';

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                const jumlahDipesan = parseInt(row.querySelector('.jumlah-dipesan-input').value) || 0;
                const jumlahDiterima = parseInt(row.querySelector('.jumlah-input').value) || 0;

                if (jumlahDipesan < 1) {
                    valid = false;
                    pesan = 'Jumlah dipesan minimal 1.';
                }

                if (jumlahDiterima > jumlahDipesan) {
                    valid = false;
                    pesan = 'Jumlah diterima tidak boleh lebih besar dari jumlah dipesan.';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert(pesan);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initSupplierSelect();
            initAllBarangSelect();
            hitungTotal();
        });
    </script>
</x-app-layout>