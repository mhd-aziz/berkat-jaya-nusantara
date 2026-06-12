@php
$pajakDitambahkan = $penjualan->pajak_ditambahkan ?? true;

$namaPerusahaan = 'CV. BERKAT JAYA NUSANTARA';
$alamatPerusahaan = 'Alamat perusahaan belum diisi';
$teleponPerusahaan = 'Telepon belum diisi';

$nomorTeleponCustomer = $penjualan->customer->nomor_telepon ?? '-';
$npwpCustomer = $penjualan->customer->npwp ?? '-';

$statusPembayaran = str_replace('_', ' ', ucfirst($penjualan->status_pembayaran ?? '-'));
$metodePembayaran = ucfirst($penjualan->metode_pembayaran ?? '-');

$modePajak = $pajakDitambahkan
? 'Pajak ditambahkan ke total akhir'
: 'Pajak hanya ditampilkan dan tidak ditambahkan ke total akhir';

$formatAngka = function ($angka) {
return rtrim(rtrim(number_format((float) $angka, 3, ',', '.'), '0'), ',');
};
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice Penjualan {{ $penjualan->nomor_invoice }}</title>

    <style>
        table {
            border-collapse: collapse;
        }

        .company-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            background-color: #eff6ff;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            background-color: #dbeafe;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
        }

        .section-header {
            font-weight: bold;
            background-color: #dbeafe;
            color: #0f172a;
        }

        .header {
            font-weight: bold;
            background-color: #eeeeee;
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-format {
            mso-number-format: "\@";
        }

        .currency {
            mso-number-format: "#,##0";
        }

        .number-format {
            mso-number-format: "#,##0.###";
        }

        .warning {
            background-color: #fef3c7;
        }

        .success {
            background-color: #dcfce7;
        }

        .total-row {
            font-weight: bold;
            background-color: #eff6ff;
        }

        .danger {
            background-color: #fee2e2;
        }
    </style>
</head>

<body>
    <table border="1">
        <tr>
            <td colspan="10" class="company-title">
                {{ $namaPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="10" class="text-center">
                {{ $alamatPerusahaan }} | Telp: {{ $teleponPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="10" class="title">
                INVOICE / NOTA PENJUALAN
            </td>
        </tr>

        <tr>
            <td colspan="10" class="subtitle text-format">
                No. Invoice: {{ $penjualan->nomor_invoice }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="5">Informasi Customer</td>
            <td colspan="5">Informasi Penjualan</td>
        </tr>

        <tr>
            <td class="bold">Nama Customer</td>
            <td colspan="4">
                {{ $penjualan->customer->nama_customer ?? '-' }}
            </td>

            <td class="bold">Tanggal Penjualan</td>
            <td colspan="4">
                {{ $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d-m-Y') : '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">Nomor Telepon</td>
            <td colspan="4" class="text-format">
                @if ($nomorTeleponCustomer !== '-')
                &#8203;{{ $nomorTeleponCustomer }}
                @else
                -
                @endif
            </td>

            <td class="bold">Metode Pembayaran</td>
            <td colspan="4">
                {{ $metodePembayaran }}
            </td>
        </tr>

        <tr>
            <td class="bold">NPWP Customer</td>
            <td colspan="4" class="text-format">
                @if ($npwpCustomer !== '-')
                &#8203;{{ $npwpCustomer }}
                @else
                -
                @endif
            </td>

            <td class="bold">Status Pembayaran</td>
            <td colspan="4">
                {{ $statusPembayaran }}
            </td>
        </tr>

        <tr>
            <td class="bold">Alamat Customer</td>
            <td colspan="4">
                {{ $penjualan->customer->alamat ?? '-' }}
            </td>

            <td class="bold">Tanggal Jatuh Tempo</td>
            <td colspan="4">
                {{ $penjualan->tanggal_jatuh_tempo ? $penjualan->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">Kategori Customer</td>
            <td colspan="4">
                {{ $penjualan->customer->kategori_customer ?? '-' }}
            </td>

            <td class="bold">Admin</td>
            <td colspan="4">
                {{ $penjualan->user->nama_user ?? '-' }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="10">Daftar Barang Dijual</td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Kode Barang</td>
            <td>Nama Barang</td>
            <td>Satuan Transaksi</td>
            <td>Qty</td>
            <td>Satuan Harga</td>
            <td>Isi Per Satuan</td>
            <td>Harga Jual</td>
            <td>Perhitungan</td>
            <td>Subtotal</td>
        </tr>

        @foreach ($penjualan->detailPenjualan as $detail)
        @php
        $tipePerhitungan = $detail->tipe_perhitungan_harga ?? 'normal';
        $satuanTransaksi = $detail->satuan_transaksi ?? ($detail->barang->satuan ?? '');
        $satuanHitung = $detail->satuan_hitung_harga ?? $satuanTransaksi;
        $isiPerSatuan = (float) ($detail->isi_per_satuan ?? 1);

        if ($tipePerhitungan === 'isi_kemasan') {
        $teksPerhitungan =
        $detail->jumlah . ' ' . strtoupper($satuanTransaksi) .
        ' x ' . $formatAngka($isiPerSatuan) . ' ' . strtoupper($satuanHitung) .
        ' x Rp ' . number_format($detail->harga_jual, 0, ',', '.');
        } else {
        $teksPerhitungan =
        $detail->jumlah . ' ' . strtoupper($satuanTransaksi) .
        ' x Rp ' . number_format($detail->harga_jual, 0, ',', '.');
        }
        @endphp

        <tr>
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td class="text-format">
                {{ $detail->barang->kode_barang ?? '-' }}
            </td>

            <td>
                {{ $detail->barang->nama_barang ?? '-' }}
            </td>

            <td class="text-center">
                {{ strtoupper($satuanTransaksi) }}
            </td>

            <td class="text-center number-format">
                {{ $detail->jumlah }}
            </td>

            <td class="text-center">
                {{ strtoupper($tipePerhitungan === 'isi_kemasan' ? $satuanHitung : $satuanTransaksi) }}
            </td>

            <td class="text-center number-format">
                @if ($tipePerhitungan === 'isi_kemasan')
                {{ $isiPerSatuan }}
                @else
                1
                @endif
            </td>

            <td class="text-right currency">
                {{ $detail->harga_jual }}
            </td>

            <td>
                {{ $teksPerhitungan }}
            </td>

            <td class="text-right currency">
                {{ $detail->subtotal }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr>
            <td colspan="8"></td>
            <td class="bold">Subtotal</td>
            <td class="text-right currency">
                {{ $penjualan->subtotal }}
            </td>
        </tr>

        <tr>
            <td colspan="8"></td>
            <td class="bold">
                Pajak {{ number_format($penjualan->persentase_pajak, 2, ',', '.') }}%
            </td>
            <td class="text-right currency">
                {{ $penjualan->nilai_pajak }}
            </td>
        </tr>

        <tr>
            <td colspan="8"></td>
            <td class="bold">Mode Pajak</td>
            <td>
                {{ $modePajak }}
            </td>
        </tr>

        <tr class="total-row">
            <td colspan="8"></td>
            <td class="bold">Total Akhir</td>
            <td class="bold text-right currency">
                {{ $penjualan->total_akhir }}
            </td>
        </tr>

        @if ($penjualan->piutang)
        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="10">Informasi Piutang</td>
        </tr>

        <tr>
            <td class="bold">Total Piutang</td>
            <td colspan="2" class="text-right currency">
                {{ $penjualan->piutang->total_piutang }}
            </td>

            <td class="bold">Total Dibayar</td>
            <td colspan="2" class="text-right currency">
                {{ $penjualan->piutang->total_dibayar }}
            </td>

            <td class="bold">Sisa Piutang</td>
            <td colspan="3" class="text-right currency">
                {{ $penjualan->piutang->sisa_piutang }}
            </td>
        </tr>

        <tr>
            <td class="bold">Tanggal Jatuh Tempo</td>
            <td colspan="2">
                {{ $penjualan->piutang->tanggal_jatuh_tempo ? $penjualan->piutang->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
            </td>

            <td class="bold">Status Piutang</td>
            <td colspan="6">
                {{ str_replace('_', ' ', ucfirst($penjualan->piutang->status_piutang)) }}
            </td>
        </tr>

        <tr>
            <td class="bold">Catatan Piutang</td>
            <td colspan="9">
                {{ $penjualan->piutang->catatan ?? '-' }}
            </td>
        </tr>
        @endif

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="10">Catatan Penjualan</td>
        </tr>

        <tr>
            <td class="bold">Catatan</td>
            <td colspan="9">
                {{ $penjualan->catatan ?? '-' }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="5">Tanda Tangan Customer</td>
            <td colspan="5">Hormat Kami</td>
        </tr>

        <tr>
            <td colspan="5" style="height: 70px;"></td>
            <td colspan="5" style="height: 70px;"></td>
        </tr>

        <tr>
            <td colspan="5" class="text-center bold">
                {{ $penjualan->customer->nama_customer ?? 'Customer' }}
            </td>
            <td colspan="5" class="text-center bold">
                {{ $namaPerusahaan }}
            </td>
        </tr>
    </table>
</body>

</html>