<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Input Invoice Penjualan Lama
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md">
                <strong>Catatan:</strong>
                Invoice penjualan lama akan disimpan sebagai data historis dan tidak akan mengurangi stok barang.
                Jika metode pembayaran kredit, sistem tetap membuat data piutang.
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

                <form action="{{ route('invoice-historis.penjualan.store') }}" method="POST" id="formPenjualanHistoris">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block mb-1 font-medium">Nomor Sistem</label>
                            <input type="text"
                                value="{{ $nomorInvoice }}"
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                                readonly>

                            <p class="text-sm text-gray-500 mt-1">
                                Nomor sistem dibuat otomatis.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">
                                Nomor Dokumen Asli <span class="text-red-600">*</span>
                            </label>
                            <input type="text"
                                name="nomor_dokumen_asli"
                                value="{{ old('nomor_dokumen_asli') }}"
                                placeholder="Contoh: INV lama / nota lama"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>

                            <p class="text-sm text-gray-500 mt-1">
                                Isi sesuai nomor invoice/nota lama sebelum sistem berjalan.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Tanggal Penjualan Lama</label>
                            <input type="date"
                                name="tanggal_penjualan"
                                value="{{ old('tanggal_penjualan') }}"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Customer</label>
                            <select name="id_customer"
                                id="customerSelect"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Cari customer..."
                                required>
                                <option value="">-- Cari / Pilih Customer --</option>

                                @foreach ($customers as $customer)
                                <option value="{{ $customer->id_customer }}"
                                    {{ old('id_customer') == $customer->id_customer ? 'selected' : '' }}>
                                    {{ $customer->kode_customer }} - {{ $customer->nama_customer }}

                                    @if ($customer->nomor_telepon)
                                    | {{ $customer->nomor_telepon }}
                                    @endif

                                    @if ($customer->npwp)
                                    | NPWP: {{ $customer->npwp }}
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
                                        <th class="border px-3 py-2 text-right">Harga Jual</th>
                                        <th class="border px-3 py-2 text-right">Subtotal</th>
                                        <th class="border px-3 py-2 text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="border px-3 py-2 min-w-[460px]">
                                            <select name="id_barang[]"
                                                class="w-full barang-select"
                                                placeholder="Cari kode atau nama barang..."
                                                required>
                                                <option value="">-- Cari / Pilih Barang --</option>

                                                @foreach ($barang as $item)
                                                <option value="{{ $item->id_barang }}"
                                                    data-harga="{{ $item->harga_jual_default }}"
                                                    data-satuan="{{ $item->satuan }}"
                                                    data-tipe-perhitungan="{{ $item->tipe_perhitungan_harga ?? 'normal' }}"
                                                    data-satuan-hitung="{{ $item->satuan_hitung_harga }}"
                                                    data-isi-per-satuan="{{ $item->isi_per_satuan ?? 1 }}">
                                                    {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                    | Stok saat ini: {{ $item->stok_saat_ini }} {{ strtoupper($item->satuan) }}
                                                    | Harga: Rp {{ number_format($item->harga_jual_default, 0, ',', '.') }}

                                                    @if (($item->tipe_perhitungan_harga ?? 'normal') === 'isi_kemasan')
                                                    / {{ strtoupper($item->satuan_hitung_harga) }}
                                                    | 1 {{ strtoupper($item->satuan) }} =
                                                    {{ rtrim(rtrim(number_format($item->isi_per_satuan, 3, ',', '.'), '0'), ',') }}
                                                    {{ strtoupper($item->satuan_hitung_harga) }}
                                                    @else
                                                    / {{ strtoupper($item->satuan) }}
                                                    @endif
                                                </option>
                                                @endforeach
                                            </select>

                                            <p class="text-sm text-gray-500 mt-1 perhitungan-info">
                                                Perhitungan: -
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
                                                name="harga_jual[]"
                                                value="0"
                                                min="0"
                                                step="0.01"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                                required>

                                            <p class="text-xs text-gray-500 mt-1 harga-info text-right">
                                                -
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
                                </tbody>
                            </table>
                        </div>

                        <template id="templateBarangRow">
                            <tr>
                                <td class="border px-3 py-2 min-w-[460px]">
                                    <select name="id_barang[]"
                                        class="w-full barang-select"
                                        placeholder="Cari kode atau nama barang..."
                                        required>
                                        <option value="">-- Cari / Pilih Barang --</option>

                                        @foreach ($barang as $item)
                                        <option value="{{ $item->id_barang }}"
                                            data-harga="{{ $item->harga_jual_default }}"
                                            data-satuan="{{ $item->satuan }}"
                                            data-tipe-perhitungan="{{ $item->tipe_perhitungan_harga ?? 'normal' }}"
                                            data-satuan-hitung="{{ $item->satuan_hitung_harga }}"
                                            data-isi-per-satuan="{{ $item->isi_per_satuan ?? 1 }}">
                                            {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                            | Stok saat ini: {{ $item->stok_saat_ini }} {{ strtoupper($item->satuan) }}
                                            | Harga: Rp {{ number_format($item->harga_jual_default, 0, ',', '.') }}

                                            @if (($item->tipe_perhitungan_harga ?? 'normal') === 'isi_kemasan')
                                            / {{ strtoupper($item->satuan_hitung_harga) }}
                                            | 1 {{ strtoupper($item->satuan) }} =
                                            {{ rtrim(rtrim(number_format($item->isi_per_satuan, 3, ',', '.'), '0'), ',') }}
                                            {{ strtoupper($item->satuan_hitung_harga) }}
                                            @else
                                            / {{ strtoupper($item->satuan) }}
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>

                                    <p class="text-sm text-gray-500 mt-1 perhitungan-info">
                                        Perhitungan: -
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
                                        name="harga_jual[]"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-right harga-input"
                                        required>

                                    <p class="text-xs text-gray-500 mt-1 harga-info text-right">
                                        -
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
                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Metode Pembayaran</label>
                                <select name="metode_pembayaran"
                                    id="metodePembayaran"
                                    class="w-full border-gray-300 rounded-md shadow-sm"
                                    required>
                                    <option value="tunai" {{ old('metode_pembayaran', 'tunai') === 'tunai' ? 'selected' : '' }}>
                                        Tunai
                                    </option>
                                    <option value="kredit" {{ old('metode_pembayaran') === 'kredit' ? 'selected' : '' }}>
                                        Kredit / Piutang
                                    </option>
                                </select>
                            </div>

                            <div class="mb-4" id="fieldJatuhTempo" style="display: none;">
                                <label class="block mb-1 font-medium">Tanggal Jatuh Tempo</label>
                                <input type="date"
                                    name="tanggal_jatuh_tempo"
                                    value="{{ old('tanggal_jatuh_tempo') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm">

                                <p class="text-sm text-gray-500 mt-1">
                                    Wajib diisi jika pembayaran kredit.
                                </p>
                            </div>

                            <label class="block mb-1 font-medium">Catatan</label>
                            <textarea name="catatan"
                                rows="4"
                                class="w-full border-gray-300 rounded-md shadow-sm">{{ old('catatan') }}</textarea>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md border">
                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Persentase Pajak (%)</label>
                                <input type="number"
                                    name="persentase_pajak"
                                    id="persentasePajak"
                                    value="{{ old('persentase_pajak', 0) }}"
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
                                            {{ old('pajak_ditambahkan', '1') == '1' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak ditambahkan ke total</strong>
                                        </span>
                                    </label>

                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                            name="pajak_ditambahkan"
                                            value="0"
                                            class="mt-1"
                                            {{ old('pajak_ditambahkan') == '0' ? 'checked' : '' }}>

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
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            onclick="return confirm('Simpan invoice penjualan lama? Data ini tidak akan mengurangi stok.')">
                            Simpan Invoice Lama
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

        function formatAngka(angka) {
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 3
            }).format(angka);
        }

        function initCustomerSelect() {
            const customerSelect = document.getElementById('customerSelect');

            if (!customerSelect || customerSelect.tomselect) {
                return;
            }

            new TomSelect(customerSelect, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                searchField: ['text'],
                placeholder: 'Cari customer...'
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

            const hargaInput = row.querySelector('.harga-input');
            const perhitunganInfo = row.querySelector('.perhitungan-info');
            const satuanJumlahInfo = row.querySelector('.satuan-jumlah-info');
            const hargaInfo = row.querySelector('.harga-info');

            if (!selectedOption) {
                hargaInput.value = 0;
                perhitunganInfo.innerText = 'Perhitungan: -';
                satuanJumlahInfo.innerText = '-';
                hargaInfo.innerText = '-';
                return;
            }

            const harga = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
            const satuan = selectedOption.getAttribute('data-satuan') || '';
            const tipePerhitungan = selectedOption.getAttribute('data-tipe-perhitungan') || 'normal';
            const satuanHitung = selectedOption.getAttribute('data-satuan-hitung') || satuan;
            const isiPerSatuan = parseFloat(selectedOption.getAttribute('data-isi-per-satuan')) || 1;

            if (!hargaInput.value || parseFloat(hargaInput.value) === 0) {
                hargaInput.value = harga;
            }

            satuanJumlahInfo.innerText = satuan ? satuan.toUpperCase() : '-';

            if (tipePerhitungan === 'isi_kemasan') {
                perhitunganInfo.innerText =
                    'Perhitungan: jumlah ' + satuan.toUpperCase() +
                    ' x isi per ' + satuan.toUpperCase() +
                    ' x harga per ' + satuanHitung.toUpperCase();

                hargaInfo.innerText = '/ ' + satuanHitung.toUpperCase();
            } else {
                perhitunganInfo.innerText =
                    'Perhitungan: jumlah ' + satuan.toUpperCase() +
                    ' x harga per ' + satuan.toUpperCase();

                hargaInfo.innerText = '/ ' + satuan.toUpperCase();
            }
        }

        function hitungSubtotalRow(row) {
            const select = row.querySelector('.barang-select');
            const selectedOption = getSelectedOption(select);

            const jumlah = parseFloat(row.querySelector('.jumlah-input').value) || 0;
            const harga = parseFloat(row.querySelector('.harga-input').value) || 0;

            if (!selectedOption) {
                return jumlah * harga;
            }

            const tipePerhitungan = selectedOption.getAttribute('data-tipe-perhitungan') || 'normal';
            const isiPerSatuan = parseFloat(selectedOption.getAttribute('data-isi-per-satuan')) || 1;

            if (tipePerhitungan === 'isi_kemasan') {
                return jumlah * isiPerSatuan * harga;
            }

            return jumlah * harga;
        }

        function hitungTotal() {
            let totalSubtotal = 0;

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                const subtotal = hitungSubtotalRow(row);

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

        function toggleJatuhTempo() {
            const metode = document.getElementById('metodePembayaran').value;
            const field = document.getElementById('fieldJatuhTempo');

            field.style.display = metode === 'kredit' ? 'block' : 'none';
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
            if (e.target.id === 'metodePembayaran') {
                toggleJatuhTempo();
            }

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
            initCustomerSelect();
            initAllBarangSelect();

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                updateBarangInfo(row);
            });

            toggleJatuhTempo();
            hitungTotal();
        });
    </script>
</x-app-layout>