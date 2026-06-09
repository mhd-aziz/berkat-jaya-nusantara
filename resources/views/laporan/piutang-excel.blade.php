<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Piutang</title>

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
            <td colspan="11" class="title">
                LAPORAN PIUTANG
            </td>
        </tr>

        <tr>
            <td colspan="11" class="subtitle">
                Periode Jatuh Tempo:
                {{ $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal }}
                s/d
                {{ $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir }}
            </td>
        </tr>

        <tr>
            <td colspan="11"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Nomor Invoice</td>
            <td>Customer</td>
            <td>Nomor Telepon</td>
            <td>Jatuh Tempo</td>
            <td>Status Piutang</td>
            <td>Total Piutang</td>
            <td>Total Dibayar</td>
            <td>Sisa Piutang</td>
            <td>Keterangan</td>
            <td>Catatan</td>
        </tr>

        @foreach ($piutang as $item)
        @php
        $lewatJatuhTempo = $item->status_piutang !== 'lunas'
        && $item->tanggal_jatuh_tempo
        && $item->tanggal_jatuh_tempo->isPast();

        if ($item->status_piutang === 'lunas') {
        $keterangan = 'Selesai';
        } elseif ($lewatJatuhTempo) {
        $keterangan = 'Lewat Jatuh Tempo';
        } else {
        $keterangan = 'Berjalan';
        }

        $nomorTelepon = $item->customer->nomor_telepon ?? '-';
        @endphp

        <tr>
            <td class="text-center">
                {{ $loop->iteration }}
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

            <td class="text-center">
                {{ $item->tanggal_jatuh_tempo ? $item->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
            </td>

            <td>
                {{ str_replace('_', ' ', ucfirst($item->status_piutang)) }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->total_piutang, 0, ',', '.') }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->total_dibayar, 0, ',', '.') }}
            </td>

            <td class="text-right">
                Rp {{ number_format($item->sisa_piutang, 0, ',', '.') }}
            </td>

            <td>
                {{ $keterangan }}
            </td>

            <td>
                {{ $item->catatan ?? '-' }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="11"></td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Data</td>
            <td colspan="5">{{ $totalData }}</td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Piutang</td>
            <td colspan="5" class="text-right">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Dibayar</td>
            <td colspan="5" class="text-right">
                Rp {{ number_format($totalDibayar, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Total Sisa Piutang</td>
            <td colspan="5" class="text-right">
                Rp {{ number_format($totalSisa, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Jumlah Belum Lunas</td>
            <td colspan="5">{{ $totalBelumLunas }}</td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Jumlah Sebagian Dibayar</td>
            <td colspan="5">{{ $totalSebagian }}</td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Jumlah Lunas</td>
            <td colspan="5">{{ $totalLunas }}</td>
        </tr>

        <tr>
            <td colspan="6" class="bold">Jumlah Lewat Jatuh Tempo</td>
            <td colspan="5">{{ $totalLewatJatuhTempo }}</td>
        </tr>
    </table>
</body>

</html>