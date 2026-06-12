@php
$pajakDitambahkan = $pembelian->pajak_ditambahkan ?? true;
$statusPenerimaan = $pembelian->status_penerimaan ?? 'lengkap';

$namaPerusahaan = 'CV. BERKAT JAYA NUSANTARA';
$alamatPerusahaan = 'Alamat perusahaan belum diisi';
$teleponPerusahaan = 'Telepon belum diisi';

$nomorTeleponSupplier = $pembelian->supplier->nomor_telepon ?? '-';
$npwpSupplier = $pembelian->supplier->npwp ?? '-';

$statusText = match ($statusPenerimaan) {
'lengkap' => 'Lengkap',
'sebagian' => 'Sebagian',
default => 'Belum Dikirim',
};

$modePajak = $pajakDitambahkan
? 'Pajak ditambahkan ke total akhir'
: 'Pajak hanya ditampilkan dan tidak ditambahkan ke total akhir';
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Nota Pembelian {{ $pembelian->nomor_pembelian }}</title>

    <style>
        table {
            border-collapse: collapse;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            background-color: #dbeafe;
        }

        .company-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            background-color: #eff6ff;
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
                INVOICE / NOTA PEMBELIAN
            </td>
        </tr>

        <tr>
            <td colspan="10" class="subtitle text-format">
                No. Nota Pembelian: {{ $pembelian->nomor_pembelian }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="5">Informasi Supplier</td>
            <td colspan="5">Informasi Dokumen Pembelian</td>
        </tr>

        <tr>
            <td class="bold">Nama Supplier</td>
            <td colspan="4">{{ $pembelian->supplier->nama_supplier ?? '-' }}</td>

            <td class="bold">Tanggal Pembelian</td>
            <td colspan="4">
                {{ $pembelian->tanggal_pembelian ? $pembelian->tanggal_pembelian->format('d-m-Y') : '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">Nomor Telepon</td>
            <td colspan="4" class="text-format">
                @if ($nomorTeleponSupplier !== '-')
                &#8203;{{ $nomorTeleponSupplier }}
                @else
                -
                @endif
            </td>

            <td class="bold">No. Delivery Order</td>
            <td colspan="4" class="text-format">
                {{ $pembelian->nomor_delivery_order ?? '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">NPWP Supplier</td>
            <td colspan="4" class="text-format">
                @if ($npwpSupplier !== '-')
                &#8203;{{ $npwpSupplier }}
                @else
                -
                @endif
            </td>

            <td class="bold">No. Surat Jalan</td>
            <td colspan="4" class="text-format">
                {{ $pembelian->nomor_surat_jalan ?? '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">Alamat Supplier</td>
            <td colspan="4">{{ $pembelian->supplier->alamat ?? '-' }}</td>

            <td class="bold">Status Penerimaan</td>
            <td colspan="4">
                {{ $statusText }}
            </td>
        </tr>

        <tr>
            <td class="bold">Catatan Supplier</td>
            <td colspan="4">{{ $pembelian->supplier->catatan ?? '-' }}</td>

            <td class="bold">Admin</td>
            <td colspan="4">{{ $pembelian->user->nama_user ?? '-' }}</td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="10">Daftar Barang Dibeli</td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Kode Barang</td>
            <td>Nama Barang</td>
            <td>Satuan</td>
            <td>Jumlah Dipesan</td>
            <td>Jumlah Diterima</td>
            <td>Sisa Belum Dikirim</td>
            <td>Status Item</td>
            <td>Harga Beli</td>
            <td>Subtotal Diterima</td>
        </tr>

        @foreach ($pembelian->detailPembelian as $detail)
        @php
        $jumlahDipesan = $detail->jumlah_dipesan ?? $detail->jumlah;
        $jumlahDiterima = $detail->jumlah;
        $sisaBelumDikirim = max($jumlahDipesan - $jumlahDiterima, 0);
        $satuan = $detail->barang->satuan ?? '-';
        $statusItem = $sisaBelumDikirim > 0 ? 'Sebagian' : 'Lengkap';
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
                {{ strtoupper($satuan) }}
            </td>

            <td class="text-center">
                {{ $jumlahDipesan }}
            </td>

            <td class="text-center">
                {{ $jumlahDiterima }}
            </td>

            <td class="text-center">
                {{ $sisaBelumDikirim }}
            </td>

            <td class="text-center {{ $sisaBelumDikirim > 0 ? 'warning' : 'success' }}">
                {{ $statusItem }}
            </td>

            <td class="text-right currency">
                {{ $detail->harga_beli }}
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
            <td class="bold">Subtotal Diterima</td>
            <td class="text-right currency">
                {{ $pembelian->subtotal }}
            </td>
        </tr>

        <tr>
            <td colspan="8"></td>
            <td class="bold">
                Pajak {{ number_format($pembelian->persentase_pajak, 2, ',', '.') }}%
            </td>
            <td class="text-right currency">
                {{ $pembelian->nilai_pajak }}
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
                {{ $pembelian->total_akhir }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="10">Catatan Pembelian</td>
        </tr>

        <tr>
            <td class="bold">Catatan</td>
            <td colspan="9">
                {{ $pembelian->catatan ?? '-' }}
            </td>
        </tr>

        @if ($statusPenerimaan === 'sebagian')
        <tr class="warning">
            <td class="bold">Keterangan</td>
            <td colspan="9">
                Sebagian barang belum dikirim oleh supplier. Stok hanya bertambah sesuai jumlah barang yang sudah diterima.
            </td>
        </tr>
        @endif

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="section-header">
            <td colspan="5">Tanda Tangan Supplier</td>
            <td colspan="5">Diterima Oleh</td>
        </tr>

        <tr>
            <td colspan="5" style="height: 70px;"></td>
            <td colspan="5" style="height: 70px;"></td>
        </tr>

        <tr>
            <td colspan="5" class="text-center bold">
                {{ $pembelian->supplier->nama_supplier ?? 'Supplier' }}
            </td>
            <td colspan="5" class="text-center bold">
                {{ $namaPerusahaan }}
            </td>
        </tr>
    </table>
</body>

</html>