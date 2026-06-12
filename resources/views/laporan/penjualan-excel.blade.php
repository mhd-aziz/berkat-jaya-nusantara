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
    <title>Laporan Penjualan</title>

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

        .header {
            font-weight: bold;
            background-color: #eeeeee;
            text-align: center;
        }

        .section-header {
            font-weight: bold;
            background-color: #dbeafe;
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
    </style>
</head>

<body>
    <table border="1">
        <tr>
            <td colspan="17" class="company-title">
                {{ $namaPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="17" class="text-center">
                {{ $alamatPerusahaan }} | Telp: {{ $teleponPerusahaan }}
            </td>
        </tr>

        <tr>
            <td colspan="17" class="title">
                LAPORAN PENJUALAN
            </td>
        </tr>

        <tr>
            <td colspan="17" class="subtitle">
                Periode: {{ $periodeAwal }} s/d {{ $periodeAkhir }}
            </td>
        </tr>

        <tr>
            <td colspan="17" class="subtitle">
                Dicetak: {{ now()->format('d-m-Y H:i') }}
            </td>
        </tr>

        <tr>
            <td colspan="17"></td>
        </tr>

        <tr class="section-header">
            <td colspan="17">Ringkasan Laporan</td>
        </tr>

        <tr>
            <td class="bold">Total Transaksi</td>
            <td class="text-center">{{ $totalTransaksi }}</td>

            <td class="bold">Sistem Berjalan</td>
            <td class="text-center">{{ $totalSistemBerjalan }}</td>

            <td class="bold">Historis</td>
            <td class="text-center">{{ $totalHistoris }}</td>

            <td class="bold">Total Tunai</td>
            <td class="text-right currency">{{ $totalTunai }}</td>

            <td class="bold">Total Kredit</td>
            <td class="text-right currency">{{ $totalKredit }}</td>

            <td class="bold">Total Piutang</td>
            <td class="text-right currency">{{ $totalPiutang }}</td>

            <td class="bold">Total Dibayar</td>
            <td class="text-right currency">{{ $totalDibayar }}</td>

            <td class="bold">Sisa Piutang</td>
            <td colspan="2" class="text-right currency">{{ $totalSisaPiutang }}</td>
        </tr>

        <tr>
            <td class="bold">Total Subtotal</td>
            <td colspan="4" class="text-right currency">{{ $totalSubtotal }}</td>

            <td class="bold">Total Pajak</td>
            <td colspan="4" class="text-right currency">{{ $totalPajak }}</td>

            <td class="bold">Total Akhir</td>
            <td colspan="6" class="text-right currency">{{ $totalAkhir }}</td>
        </tr>

        <tr>
            <td colspan="17"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Tanggal</td>
            <td>No Invoice Sistem</td>
            <td>No Dokumen Asli</td>
            <td>Customer</td>
            <td>Nomor Telepon</td>
            <td>NPWP</td>
            <td>Metode</td>
            <td>Status Pembayaran</td>
            <td>Tipe Invoice</td>
            <td>Mode Pajak</td>
            <td>Subtotal</td>
            <td>Pajak</td>
            <td>Total Akhir</td>
            <td>Total Piutang</td>
            <td>Total Dibayar</td>
            <td>Sisa Piutang</td>
        </tr>

        @foreach ($penjualan as $item)
        @php
        $nomorTelepon = $item->customer->nomor_telepon ?? '-';
        $npwp = $item->customer->npwp ?? '-';
        $isHistoris = (bool) ($item->is_historical ?? false);
        $pajakDitambahkan = $item->pajak_ditambahkan ?? true;

        $statusPembayaran = match ($item->status_pembayaran) {
        'lunas' => 'Lunas',
        'sebagian' => 'Sebagian',
        default => 'Belum Lunas',
        };

        $tipeInvoice = $isHistoris ? 'Historis / Lama' : 'Sistem Berjalan';

        $modePajak = $pajakDitambahkan
        ? 'Pajak ditambahkan ke total akhir'
        : 'Pajak hanya ditampilkan';
        @endphp

        <tr class="{{ $isHistoris ? 'historis' : 'sistem' }}">
            <td class="text-center">
                {{ $loop->iteration }}
            </td>

            <td class="text-center">
                {{ $item->tanggal_penjualan ? $item->tanggal_penjualan->format('d-m-Y') : '-' }}
            </td>

            <td class="text-format">
                {{ $item->nomor_invoice }}
            </td>

            <td class="text-format">
                {{ $item->nomor_dokumen_asli ?? '-' }}
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
                {{ ucfirst($item->metode_pembayaran) }}
            </td>

            <td class="text-center">
                {{ $statusPembayaran }}
            </td>

            <td class="text-center">
                {{ $tipeInvoice }}
            </td>

            <td>
                {{ $modePajak }}
            </td>

            <td class="text-right currency">
                {{ $item->subtotal }}
            </td>

            <td class="text-right currency">
                {{ $item->nilai_pajak }}
            </td>

            <td class="text-right currency">
                {{ $item->total_akhir }}
            </td>

            <td class="text-right currency">
                {{ $item->piutang->total_piutang ?? 0 }}
            </td>

            <td class="text-right currency">
                {{ $item->piutang->total_dibayar ?? 0 }}
            </td>

            <td class="text-right currency">
                {{ $item->piutang->sisa_piutang ?? 0 }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="17"></td>
        </tr>

        <tr class="total-row">
            <td colspan="11" class="bold">TOTAL SUBTOTAL</td>
            <td colspan="6" class="text-right currency">{{ $totalSubtotal }}</td>
        </tr>

        <tr class="total-row">
            <td colspan="11" class="bold">TOTAL PAJAK</td>
            <td colspan="6" class="text-right currency">{{ $totalPajak }}</td>
        </tr>

        <tr class="total-row">
            <td colspan="11" class="bold">TOTAL AKHIR</td>
            <td colspan="6" class="text-right currency">{{ $totalAkhir }}</td>
        </tr>

        <tr class="total-row">
            <td colspan="11" class="bold">TOTAL SISA PIUTANG</td>
            <td colspan="6" class="text-right currency">{{ $totalSisaPiutang }}</td>
        </tr>
    </table>
</body>

</html>