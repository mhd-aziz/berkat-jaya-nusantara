@php
$namaPerusahaan = 'CV. BERKAT JAYA NUSANTARA';
$alamatPerusahaan = 'Alamat perusahaan belum diisi';
$teleponPerusahaan = 'Telepon belum diisi';

$periodeAwal = $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal;
$periodeAkhir = $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir;
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Piutang</title>

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

        .date-format {
            mso-number-format: "dd-mm-yyyy";
        }

        .total-row {
            font-weight: bold;
            background-color: #eff6ff;
        }

        .historis {
            background-color: #f3e8ff;
        }

        .sistem {
            background-color: #f9fafb;
        }

        .warning {
            background-color: #fef3c7;
        }

        .success {
            background-color: #dcfce7;
        }

        .danger {
            background-color: #fee2e2;
        }
    </style>
</head>

<body>
    <table border="1">
        <tr>
            <td colspan="18" class="company-title">
                {{ $namaPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="18" class="text-center">
                {{ $alamatPerusahaan }} | Telp: {{ $teleponPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="18" class="title">
                LAPORAN PIUTANG
            </td>
        </tr>

        <tr>
            <td colspan="18" class="subtitle">
                Periode Jatuh Tempo: {{ $periodeAwal }} s/d {{ $periodeAkhir }}
            </td>
        </tr>

        <tr>
            <td colspan="18" class="subtitle">
                Dicetak: {{ now()->format('d-m-Y H:i') }}
            </td>
        </tr>

        <tr>
            <td colspan="18"></td>
        </tr>

        <tr class="section-header">
            <td colspan="18">Ringkasan Laporan</td>
        </tr>

        <tr>
            <td class="bold">Total Data</td>
            <td class="text-center">{{ $totalData }}</td>

            <td class="bold">Sistem Berjalan</td>
            <td class="text-center">{{ $totalSistemBerjalan ?? 0 }}</td>

            <td class="bold">Historis</td>
            <td class="text-center">{{ $totalHistoris ?? 0 }}</td>

            <td class="bold">Belum Lunas</td>
            <td class="text-center">{{ $totalBelumLunas }}</td>

            <td class="bold">Sebagian Dibayar</td>
            <td class="text-center">{{ $totalSebagian }}</td>

            <td class="bold">Lunas</td>
            <td class="text-center">{{ $totalLunas }}</td>

            <td class="bold">Lewat Jatuh Tempo</td>
            <td colspan="5" class="text-center">{{ $totalLewatJatuhTempo }}</td>
        </tr>

        <tr>
            <td class="bold">Total Piutang</td>
            <td colspan="5" class="text-right currency">{{ $totalPiutang }}</td>

            <td class="bold">Total Dibayar</td>
            <td colspan="5" class="text-right currency">{{ $totalDibayar }}</td>

            <td class="bold">Total Sisa</td>
            <td colspan="5" class="text-right currency">{{ $totalSisa }}</td>
        </tr>

        <tr>
            <td colspan="18"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>No Invoice Sistem</td>
            <td>No Dokumen Asli</td>
            <td>Tanggal Invoice</td>
            <td>Customer</td>
            <td>Nomor Telepon</td>
            <td>NPWP</td>
            <td>Metode Pembayaran</td>
            <td>Status Pembayaran</td>
            <td>Tipe Invoice</td>
            <td>Jatuh Tempo</td>
            <td>Status Piutang</td>
            <td>Total Piutang</td>
            <td>Total Dibayar</td>
            <td>Sisa Piutang</td>
            <td>Keterangan</td>
            <td>Catatan Piutang</td>
            <td>Catatan Penjualan</td>
        </tr>

        @foreach ($piutang as $item)
        @php
        $isHistoris = (bool) ($item->penjualan->is_historical ?? false);

        $lewatJatuhTempo = $item->status_piutang !== 'lunas'
        && $item->tanggal_jatuh_tempo
        && $item->tanggal_jatuh_tempo->isPast();

        if ($item->status_piutang === 'lunas') {
        $statusText = 'Lunas';
        $statusClass = 'success';
        } elseif ($item->status_piutang === 'sebagian_dibayar') {
        $statusText = 'Sebagian Dibayar';
        $statusClass = 'warning';
        } elseif ($item->status_piutang === 'jatuh_tempo') {
        $statusText = 'Jatuh Tempo';
        $statusClass = 'danger';
        } else {
        $statusText = 'Belum Lunas';
        $statusClass = 'warning';
        }

        if ($item->status_piutang === 'lunas') {
        $keterangan = 'Selesai';
        $keteranganClass = 'success';
        } elseif ($lewatJatuhTempo || $item->status_piutang === 'jatuh_tempo') {
        $keterangan = 'Lewat Jatuh Tempo';
        $keteranganClass = 'danger';
        } else {
        $keterangan = 'Berjalan';
        $keteranganClass = 'warning';
        }

        $tipeInvoice = $isHistoris ? 'Historis / Lama' : 'Sistem Berjalan';
        $nomorTelepon = $item->customer->nomor_telepon ?? '-';
        $npwp = $item->customer->npwp ?? '-';
        @endphp

        <tr class="{{ $isHistoris ? 'historis' : 'sistem' }}">
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td class="text-format">
                {{ $item->nomor_invoice }}
            </td>

            <td class="text-format">
                {{ $item->penjualan->nomor_dokumen_asli ?? '-' }}
            </td>

            <td class="text-center">
                {{ $item->penjualan?->tanggal_penjualan ? $item->penjualan->tanggal_penjualan->format('d-m-Y') : '-' }}
            </td>

            <td>
                {{ $item->customer->nama_customer ?? '-' }}
            </td>

            <td class="text-format">
                @if ($nomorTelepon !== '-')
                &#8203;{{ $nomorTelepon }}
                @else
                -
                @endif
            </td>

            <td class="text-format">
                @if ($npwp !== '-')
                &#8203;{{ $npwp }}
                @else
                -
                @endif
            </td>

            <td class="text-center">
                {{ ucfirst($item->penjualan->metode_pembayaran ?? '-') }}
            </td>

            <td class="text-center">
                {{ str_replace('_', ' ', ucfirst($item->penjualan->status_pembayaran ?? '-')) }}
            </td>

            <td class="text-center">
                {{ $tipeInvoice }}
            </td>

            <td class="text-center">
                {{ $item->tanggal_jatuh_tempo ? $item->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
            </td>

            <td class="text-center {{ $statusClass }}">
                {{ $statusText }}
            </td>

            <td class="text-right currency">
                {{ $item->total_piutang }}
            </td>

            <td class="text-right currency">
                {{ $item->total_dibayar }}
            </td>

            <td class="text-right currency">
                {{ $item->sisa_piutang }}
            </td>

            <td class="text-center {{ $keteranganClass }}">
                {{ $keterangan }}
            </td>

            <td>
                {{ $item->catatan ?? '-' }}
            </td>

            <td>
                {{ $item->penjualan->catatan ?? '-' }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="18"></td>
        </tr>

        <tr class="total-row">
            <td colspan="12" class="bold">TOTAL PIUTANG</td>
            <td colspan="6" class="text-right currency">{{ $totalPiutang }}</td>
        </tr>

        <tr class="total-row">
            <td colspan="12" class="bold">TOTAL DIBAYAR</td>
            <td colspan="6" class="text-right currency">{{ $totalDibayar }}</td>
        </tr>

        <tr class="total-row">
            <td colspan="12" class="bold">TOTAL SISA PIUTANG</td>
            <td colspan="6" class="text-right currency">{{ $totalSisa }}</td>
        </tr>
    </table>
</body>

</html>