<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>

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
            <td colspan="10" class="title">
                LAPORAN PENJUALAN
            </td>
        </tr>

        <tr>
            <td colspan="10" class="subtitle">
                Periode:
                {{ $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal }}
                s/d
                {{ $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir }}
            </td>
        </tr>

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Tanggal</td>
            <td>Nomor Invoice</td>
            <td>Customer</td>
            <td>Nomor Telepon</td>
            <td>Metode Pembayaran</td>
            <td>Status Pembayaran</td>
            <td>Subtotal</td>
            <td>Pajak</td>
            <td>Total Akhir</td>
        </tr>

        @foreach ($penjualan as $item)
        @php
        $nomorTelepon = $item->customer->nomor_telepon ?? '-';
        @endphp

        <tr>
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td class="text-center">
                {{ $item->tanggal_penjualan ? $item->tanggal_penjualan->format('d-m-Y') : '-' }}
            </td>

            <td>
                {{ $item->nomor_invoice }}
            </td>

            <td>
                {{ $item->customer->nama_customer ?? '-' }}
            </td>

            <td>
                @if ($nomorTelepon !== '-')
                &#8203;{{ $nomorTelepon }}
                @else
                -
                @endif
            </td>

            <td>
                {{ ucfirst($item->metode_pembayaran) }}
            </td>

            <td>
                {{ str_replace('_', ' ', ucfirst($item->status_pembayaran)) }}
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
        </tr>
        @endforeach

        <tr>
            <td colspan="10"></td>
        </tr>

        <tr>
            <td colspan="7" class="bold">Total Transaksi</td>
            <td colspan="3" class="text-center">
                {{ $totalTransaksi }}
            </td>
        </tr>

        <tr>
            <td colspan="7" class="bold">Total Subtotal</td>
            <td colspan="3" class="text-right">
                Rp {{ number_format($totalSubtotal, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="7" class="bold">Total Pajak</td>
            <td colspan="3" class="text-right">
                Rp {{ number_format($totalPajak, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="7" class="bold">Total Akhir</td>
            <td colspan="3" class="text-right">
                Rp {{ number_format($totalAkhir, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</body>

</html>