<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>

    <style>
        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .subtitle {
            text-align: center;
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
            <td colspan="13" class="title">
                LAPORAN PEMBELIAN
            </td>
        </tr>

        <tr>
            <td colspan="13" class="subtitle">
                Periode:
                {{ $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal }}
                s/d
                {{ $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir }}
            </td>
        </tr>

        <tr>
            <td colspan="13"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Tanggal</td>
            <td>Nomor Pembelian</td>
            <td>Supplier</td>
            <td>Nomor Telepon</td>
            <td>Status Penerimaan</td>
            <td>Jumlah Dipesan</td>
            <td>Jumlah Diterima</td>
            <td>Sisa Belum Dikirim</td>
            <td>Subtotal</td>
            <td>Pajak</td>
            <td>Total Akhir</td>
            <td>Admin</td>
        </tr>

        @foreach ($pembelian as $item)
        @php
        $jumlahDipesan = 0;
        $jumlahDiterima = 0;

        foreach ($item->detailPembelian as $detail) {
        $jumlahDipesan += $detail->jumlah_dipesan ?? $detail->jumlah;
        $jumlahDiterima += $detail->jumlah;
        }

        $sisa = max($jumlahDipesan - $jumlahDiterima, 0);

        $statusPenerimaan = $item->status_penerimaan ?? 'lengkap';
        $nomorTelepon = $item->supplier->nomor_telepon ?? '-';
        @endphp

        <tr>
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td class="text-center">
                {{ $item->tanggal_pembelian ? $item->tanggal_pembelian->format('d-m-Y') : '-' }}
            </td>

            <td>
                {{ $item->nomor_pembelian }}
            </td>

            <td>
                {{ $item->supplier->nama_supplier ?? '-' }}
            </td>

            <td>
                @if ($nomorTelepon !== '-')
                &#8203;{{ $nomorTelepon }}
                @else
                -
                @endif
            </td>

            <td>
                @if ($statusPenerimaan === 'lengkap')
                Lengkap
                @elseif ($statusPenerimaan === 'sebagian')
                Sebagian
                @else
                Belum Dikirim
                @endif
            </td>

            <td class="text-center">
                {{ $jumlahDipesan }}
            </td>

            <td class="text-center">
                {{ $jumlahDiterima }}
            </td>

            <td class="text-center">
                {{ $sisa }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->nilai_pajak, 0, ',', '.') }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->total_akhir, 0, ',', '.') }}
            </td>

            <td>
                {{ $item->user->nama_user ?? '-' }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="13"></td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Transaksi</td>
            <td colspan="7" class="text-center">
                {{ $totalTransaksi }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Barang Dipesan</td>
            <td colspan="7" class="text-center">
                {{ $totalDipesan }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Barang Diterima</td>
            <td colspan="7" class="text-center">
                {{ $totalDiterima }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Sisa Belum Dikirim</td>
            <td colspan="7" class="text-center">
                {{ $totalSisa }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Subtotal</td>
            <td colspan="7" class="text-right">
                Rp {{ number_format($totalSubtotal, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Pajak</td>
            <td colspan="7" class="text-right">
                Rp {{ number_format($totalPajak, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Akhir</td>
            <td colspan="7" class="text-right">
                Rp {{ number_format($totalAkhir, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</body>

</html>