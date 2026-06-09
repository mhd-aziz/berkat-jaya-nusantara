@php
$pajakDitambahkan = $pembelian->pajak_ditambahkan ?? true;
$statusPenerimaan = $pembelian->status_penerimaan ?? 'lengkap';
$nomorTelepon = $pembelian->supplier->nomor_telepon ?? '-';
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Nota Pembelian {{ $pembelian->nomor_pembelian }}</title>

    <style>
        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .subtitle {
            text-align: center;
        }

        .section-header {
            font-weight: bold;
            background-color: #eeeeee;
        }

        .header {
            font-weight: bold;
            background-color: #eeeeee;
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
    </style>
</head>

<body>
    <table border="1">
        <tr>
            <td colspan="8" class="title">
                NOTA PEMBELIAN
            </td>
        </tr>

        <tr>
            <td colspan="8" class="subtitle">
                {{ $pembelian->nomor_pembelian }}
            </td>
        </tr>

        <tr>
            <td colspan="8"></td>
        </tr>

        <tr class="section-header">
            <td colspan="4">Informasi Supplier</td>
            <td colspan="4">Informasi Pembelian</td>
        </tr>

        <tr>
            <td class="bold">Nama Supplier</td>
            <td colspan="3">{{ $pembelian->supplier->nama_supplier ?? '-' }}</td>

            <td class="bold">Tanggal</td>
            <td colspan="3">
                {{ $pembelian->tanggal_pembelian ? $pembelian->tanggal_pembelian->format('d-m-Y') : '-' }}
            </td>
        </tr>

        <tr>
            <td class="bold">Nomor Telepon</td>
            <td colspan="3">
                @if ($nomorTelepon !== '-')
                &#8203;{{ $nomorTelepon }}
                @else
                -
                @endif
            </td>

            <td class="bold">Status Penerimaan</td>
            <td colspan="3">
                @if ($statusPenerimaan === 'lengkap')
                Lengkap
                @elseif ($statusPenerimaan === 'sebagian')
                Sebagian
                @else
                Belum Dikirim
                @endif
            </td>
        </tr>

        <tr>
            <td class="bold">Alamat</td>
            <td colspan="3">{{ $pembelian->supplier->alamat ?? '-' }}</td>

            <td class="bold">Admin</td>
            <td colspan="3">{{ $pembelian->user->nama_user ?? '-' }}</td>
        </tr>

        <tr>
            <td colspan="8"></td>
        </tr>

        <tr class="section-header">
            <td colspan="8">Daftar Barang Dibeli</td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Kode Barang</td>
            <td>Nama Barang</td>
            <td>Jumlah Dipesan</td>
            <td>Jumlah Diterima</td>
            <td>Sisa Belum Dikirim</td>
            <td>Harga Beli</td>
            <td>Subtotal Diterima</td>
        </tr>

        @foreach ($pembelian->detailPembelian as $detail)
        @php
        $jumlahDipesan = $detail->jumlah_dipesan ?? $detail->jumlah;
        $jumlahDiterima = $detail->jumlah;
        $sisaBelumDikirim = max($jumlahDipesan - $jumlahDiterima, 0);
        @endphp

        <tr>
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td>
                {{ $detail->barang->kode_barang ?? '-' }}
            </td>

            <td>
                {{ $detail->barang->nama_barang ?? '-' }}
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

            <td class="text-right">
                Rp {{ number_format($detail->harga_beli, 0, ',', '.') }}
            </td>

            <td class="text-right">
                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="8"></td>
        </tr>

        <tr>
            <td colspan="6"></td>
            <td class="bold">Subtotal Diterima</td>
            <td class="text-right">
                Rp {{ number_format($pembelian->subtotal, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6"></td>
            <td class="bold">
                Pajak {{ number_format($pembelian->persentase_pajak, 2, ',', '.') }}%
            </td>
            <td class="text-right">
                Rp {{ number_format($pembelian->nilai_pajak, 0, ',', '.') }}
            </td>
        </tr>

        @if (!$pajakDitambahkan)
        <tr>
            <td colspan="6"></td>
            <td colspan="2">
                Pajak hanya ditampilkan dan tidak ditambahkan ke total akhir.
            </td>
        </tr>
        @endif

        <tr>
            <td colspan="6"></td>
            <td class="bold">Total Akhir</td>
            <td class="bold text-right">
                Rp {{ number_format($pembelian->total_akhir, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="8"></td>
        </tr>

        <tr>
            <td class="bold">Catatan</td>
            <td colspan="7">{{ $pembelian->catatan ?? '-' }}</td>
        </tr>

        @if ($statusPenerimaan === 'sebagian')
        <tr>
            <td class="bold">Keterangan</td>
            <td colspan="7">
                Sebagian barang belum dikirim oleh supplier. Stok hanya bertambah sesuai jumlah barang yang sudah diterima.
            </td>
        </tr>
        @endif
    </table>
</body>

</html>