<x-app-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Penjualan / Barang Keluar
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('penjualan.store') }}" method="POST" id="formPenjualan">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block mb-1 font-medium">Nomor Invoice</label>
                            <input type="text"
                                value="{{ $nomorInvoice }}"
                                class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                                readonly>
                            <p class="text-sm text-gray-500 mt-1">
                                Nomor invoice dibuat otomatis.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 font-medium">Tanggal Penjualan</label>
                            <input type="date"
                                name="tanggal_penjualan"
                                value="{{ old('tanggal_penjualan', date('Y-m-d')) }}"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                required>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-medium">Customer</label>

                                <button type="button"
                                    id="btnBukaModalCustomer"
                                    class="text-sm px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    + Tambah Customer
                                </button>
                            </div>

                            <select name="id_customer"
                                id="customerSelect"
                                class="w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Cari kode atau nama customer..."
                                required>
                                <option value="">-- Cari / Pilih Customer --</option>
                                @foreach ($customers as $customer)
                                <option value="{{ $customer->id_customer }}"
                                    {{ old('id_customer') == $customer->id_customer ? 'selected' : '' }}>
                                    {{ $customer->kode_customer }} - {{ $customer->nama_customer }}
                                    @if ($customer->nomor_telepon)
                                    | {{ $customer->nomor_telepon }}
                                    @endif
                                </option>
                                @endforeach
                            </select>

                            <p class="text-sm text-gray-500 mt-1">
                                Pilih customer lama atau tambah customer baru jika belum terdaftar.
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="font-semibold text-lg mb-2">Daftar Barang Dijual</h3>

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
                                        <td class="border px-3 py-2 min-w-[420px]">
                                            <select name="id_barang[]"
                                                class="w-full barang-select"
                                                placeholder="Cari kode atau nama barang..."
                                                required>
                                                <option value="">-- Cari / Pilih Barang --</option>
                                                @foreach ($barang as $item)
                                                <option value="{{ $item->id_barang }}"
                                                    data-harga="{{ $item->harga_jual_default }}"
                                                    data-stok="{{ $item->stok_saat_ini }}">
                                                    {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                    | Stok: {{ $item->stok_saat_ini }}
                                                    | Harga: Rp {{ number_format($item->harga_jual_default, 0, ',', '.') }}
                                                </option>
                                                @endforeach
                                            </select>
                                            <p class="text-sm text-gray-500 mt-1 stok-info">Stok tersedia: -</p>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="jumlah[]"
                                                value="1"
                                                min="1"
                                                class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                                required>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <input type="number"
                                                name="harga_jual[]"
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
                                </tbody>
                            </table>

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
                                                data-harga="{{ $item->harga_jual_default }}"
                                                data-stok="{{ $item->stok_saat_ini }}">
                                                {{ $item->kode_barang }} - {{ $item->nama_barang }}
                                                | Stok: {{ $item->stok_saat_ini }}
                                                | Harga: Rp {{ number_format($item->harga_jual_default, 0, ',', '.') }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <p class="text-sm text-gray-500 mt-1 stok-info">Stok tersedia: -</p>
                                    </td>

                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="jumlah[]"
                                            value="1"
                                            min="1"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-right jumlah-input"
                                            required>
                                    </td>

                                    <td class="border px-3 py-2">
                                        <input type="number"
                                            name="harga_jual[]"
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
                            <div class="mb-4">
                                <label class="block mb-1 font-medium">Metode Pembayaran</label>
                                <select name="metode_pembayaran"
                                    id="metodePembayaran"
                                    class="w-full border-gray-300 rounded-md shadow-sm"
                                    required>
                                    <option value="tunai" {{ old('metode_pembayaran') === 'tunai' ? 'selected' : '' }}>
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

                            <div>
                                <label class="block mb-1 font-medium">Catatan</label>
                                <textarea name="catatan"
                                    rows="4"
                                    class="w-full border-gray-300 rounded-md shadow-sm">{{ old('catatan') }}</textarea>
                            </div>
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

                                <p class="text-sm text-gray-500 mt-1">
                                    Pajak tetap bisa ditampilkan di invoice, walaupun tidak ditambahkan ke total akhir.
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
                                            {{ old('pajak_ditambahkan', '1') == '1' ? 'checked' : '' }}>

                                        <span>
                                            <strong>Pajak ditambahkan ke total</strong>
                                            <br>
                                            <small class="text-gray-500">
                                                Untuk customer yang memang dikenakan pajak.
                                            </small>
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
                                            <br>
                                            <small class="text-gray-500">
                                                Untuk customer yang pajaknya hanya ingin dicatat/ditampilkan, tetapi tidak menambah total.
                                            </small>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-between mb-2">
                                <span>Subtotal Penjualan</span>
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
                        <a href="{{ route('penjualan.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            onclick="return confirm('Simpan transaksi penjualan ini? Stok barang akan berkurang otomatis.')">
                            Simpan Penjualan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div id="modalCustomer"
        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl mx-4">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="text-lg font-semibold">
                    Tambah Customer Baru
                </h3>

                <button type="button"
                    id="btnTutupModalCustomer"
                    class="text-gray-500 hover:text-gray-800 text-2xl leading-none">
                    &times;
                </button>
            </div>

            <form id="formQuickCustomer" class="p-6">
                @csrf

                <div id="quickCustomerMessage"
                    class="hidden mb-4 p-4 rounded-md">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium">
                            Nama Customer <span class="text-red-600">*</span>
                        </label>
                        <input type="text"
                            name="nama_customer"
                            id="quickNamaCustomer"
                            class="w-full border-gray-300 rounded-md shadow-sm"
                            required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium">
                            Nomor Handphone <span class="text-red-600">*</span>
                        </label>
                        <input type="text"
                            name="nomor_telepon"
                            id="quickNomorTelepon"
                            class="w-full border-gray-300 rounded-md shadow-sm"
                            required>
                        <p class="text-sm text-gray-500 mt-1">
                            Jika nama atau nomor handphone sudah tersedia, customer lama akan langsung dipilih.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium">Kategori Customer</label>
                        <input type="text"
                            name="kategori_customer"
                            id="quickKategoriCustomer"
                            class="w-full border-gray-300 rounded-md shadow-sm"
                            placeholder="Contoh: Customer Baru, Customer Lama, Grosir">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium">Alamat</label>
                        <textarea name="alamat"
                            id="quickAlamatCustomer"
                            rows="2"
                            class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 font-medium">Catatan</label>
                        <textarea name="catatan"
                            id="quickCatatanCustomer"
                            rows="2"
                            class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button"
                        id="btnBatalCustomer"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Batal
                    </button>

                    <button type="submit"
                        id="btnSimpanQuickCustomer"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }

        function initCustomerSelect() {
            const customerSelect = document.getElementById('customerSelect');

            if (!customerSelect) {
                return;
            }

            if (customerSelect.tomselect) {
                return;
            }

            new TomSelect(customerSelect, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                searchField: ['text'],
                placeholder: 'Cari kode atau nama customer...'
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
            if (!selectElement) {
                return null;
            }

            const selectedValue = selectElement.value;

            if (!selectedValue) {
                return null;
            }

            return selectElement.querySelector('option[value="' + selectedValue + '"]');
        }

        function updateBarangInfo(row) {
            if (!row) {
                return;
            }

            const select = row.querySelector('.barang-select');
            const selectedOption = getSelectedOption(select);

            const harga = selectedOption ? selectedOption.getAttribute('data-harga') : 0;
            const stok = selectedOption ? selectedOption.getAttribute('data-stok') : '-';

            row.querySelector('.harga-input').value = harga || 0;
            row.querySelector('.stok-info').innerText = 'Stok tersedia: ' + stok;
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

            const totalAkhir = pajakDitambahkan ?
                totalSubtotal + nilaiPajak :
                totalSubtotal;

            document.getElementById('totalSubtotal').innerText = formatRupiah(totalSubtotal);
            document.getElementById('totalPajak').innerText = formatRupiah(nilaiPajak);
            document.getElementById('totalAkhir').innerText = formatRupiah(totalAkhir);
        }

        function updateMetodePembayaran() {
            const metode = document.getElementById('metodePembayaran').value;
            const fieldJatuhTempo = document.getElementById('fieldJatuhTempo');

            if (metode === 'kredit') {
                fieldJatuhTempo.style.display = 'block';
                fieldJatuhTempo.querySelector('input').setAttribute('required', 'required');
            } else {
                fieldJatuhTempo.style.display = 'none';
                fieldJatuhTempo.querySelector('input').removeAttribute('required');
            }
        }

        function bukaModalCustomer() {
            const modal = document.getElementById('modalCustomer');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.getElementById('quickCustomerMessage').classList.add('hidden');
            document.getElementById('quickNamaCustomer').focus();
        }

        function tutupModalCustomer() {
            const modal = document.getElementById('modalCustomer');
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            document.getElementById('formQuickCustomer').reset();
            document.getElementById('quickCustomerMessage').classList.add('hidden');
        }

        function tampilkanPesanCustomer(type, message) {
            const box = document.getElementById('quickCustomerMessage');

            box.classList.remove(
                'hidden',
                'bg-red-100',
                'text-red-700',
                'bg-green-100',
                'text-green-700',
                'bg-yellow-100',
                'text-yellow-700'
            );

            if (type === 'error') {
                box.classList.add('bg-red-100', 'text-red-700');
            } else if (type === 'exists') {
                box.classList.add('bg-yellow-100', 'text-yellow-700');
            } else {
                box.classList.add('bg-green-100', 'text-green-700');
            }

            box.innerText = message;
        }

        function pilihCustomer(customer) {
            const customerSelect = document.getElementById('customerSelect');

            const text = customer.kode_customer + ' - ' + customer.nama_customer +
                (customer.nomor_telepon ? ' | ' + customer.nomor_telepon : '');

            let option = customerSelect.querySelector('option[value="' + customer.id_customer + '"]');

            if (!option) {
                option = new Option(text, customer.id_customer, true, true);
                customerSelect.add(option);
            } else {
                option.text = text;
            }

            if (customerSelect.tomselect) {
                customerSelect.tomselect.addOption({
                    value: String(customer.id_customer),
                    text: text
                });

                customerSelect.tomselect.addItem(String(customer.id_customer), true);
                customerSelect.tomselect.setValue(String(customer.id_customer), true);
                customerSelect.tomselect.refreshOptions(false);
            } else {
                customerSelect.value = customer.id_customer;
            }
        }

        async function simpanQuickCustomer(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSimpanQuickCustomer');
            const form = document.getElementById('formQuickCustomer');
            const formData = new FormData(form);

            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            try {
                const response = await fetch("{{ route('customers.quickStore') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    let pesan = 'Customer gagal disimpan.';

                    if (data.errors) {
                        pesan = Object.values(data.errors).flat().join('\n');
                    } else if (data.message) {
                        pesan = data.message;
                    }

                    tampilkanPesanCustomer('error', pesan);
                    return;
                }

                pilihCustomer(data.customer);

                if (data.status === 'exists') {
                    tampilkanPesanCustomer('exists', data.message || 'Customer sudah tersedia dan langsung dipilih.');
                } else {
                    tampilkanPesanCustomer('success', data.message || 'Customer baru berhasil ditambahkan dan langsung dipilih.');
                }

                setTimeout(function() {
                    tutupModalCustomer();
                }, 900);
            } catch (error) {
                tampilkanPesanCustomer('error', 'Terjadi kesalahan. Silakan coba lagi.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan Customer';
            }
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
            if (
                e.target.id === 'metodePembayaran' ||
                e.target.name === 'pajak_ditambahkan'
            ) {
                updateMetodePembayaran();
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
                    alert('Minimal harus ada satu barang dalam transaksi penjualan.');
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

        document.getElementById('formPenjualan').addEventListener('submit', function(e) {
            let valid = true;
            let pesan = '';

            document.querySelectorAll('#tableBarang tbody tr').forEach(function(row) {
                const select = row.querySelector('.barang-select');
                const selectedOption = getSelectedOption(select);

                if (!select.value) {
                    valid = false;
                    pesan = 'Barang wajib dipilih.';
                    return;
                }

                const stok = selectedOption ? parseInt(selectedOption.getAttribute('data-stok')) || 0 : 0;
                const jumlah = parseInt(row.querySelector('.jumlah-input').value) || 0;

                if (jumlah > stok) {
                    valid = false;
                    pesan = 'Jumlah penjualan tidak boleh melebihi stok tersedia.';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert(pesan);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            initCustomerSelect();
            initAllBarangSelect();
            updateMetodePembayaran();
            hitungTotal();

            document.getElementById('btnBukaModalCustomer').addEventListener('click', bukaModalCustomer);
            document.getElementById('btnTutupModalCustomer').addEventListener('click', tutupModalCustomer);
            document.getElementById('btnBatalCustomer').addEventListener('click', tutupModalCustomer);
            document.getElementById('formQuickCustomer').addEventListener('submit', simpanQuickCustomer);

            document.getElementById('modalCustomer').addEventListener('click', function(e) {
                if (e.target.id === 'modalCustomer') {
                    tutupModalCustomer();
                }
            });
        });
    </script>
</x-app-layout>