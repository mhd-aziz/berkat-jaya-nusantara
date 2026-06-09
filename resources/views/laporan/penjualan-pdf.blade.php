<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .subtitle {
            text-align: center;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 9px;
            color: #4b5563;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            border: 1px solid #9ca3af;
            background-color: #e5e7eb;
            padding: 5px;
            font-weight: bold;
            text-align: center;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 5px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .status-lunas {
            color: #047857;
            font-weight: bold;
        }

        .status-sebagian {
            color: #1d4ed8;
            font-weight: bold;
        }

        .status-belum {
            color: #92400e;
            font-weight: bold;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="title">
        LAPORAN PENJUALAN
    </div>

    <div class="subtitle">
        Berkat Jaya Nusantara<br>
        Periode:
        {{ $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal }}
        s/d
        {{ $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir }}
        |
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Total Transaksi</div>
                <div class="summary-value">{{ $totalTransaksi }}</div>
            </td>

            <td>
                <div class="summary-label">Total Subtotal</div>
                <div class="summary-value">
                    Rp {{ number_format($totalSubtotal, 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="summary-label">Total Pajak</div>
                <div class="summary-value">
                    Rp {{ number_format($totalPajak, 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="summary-label">Total Akhir</div>
                <div class="summary-value">
                    Rp {{ number_format($totalAkhir, 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 12%;">Invoice</th>
                <th style="width: 17%;">Customer</th>
                <th style="width: 10%;">No. Telepon</th>
                <th style="width: 9%;">Metode</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Subtotal</th>
                <th style="width: 9%;">Pajak</th>
                <th style="width: 12%;">Total Akhir</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($penjualan as $item)
            @php
            if ($item->status_pembayaran === 'lunas') {
            $statusPembayaran = 'Lunas';
            $statusClass = 'status-lunas';
            } elseif ($item->status_pembayaran === 'sebagian') {
            $statusPembayaran = 'Sebagian';
            $statusClass = 'status-sebagian';
            } else {
            $statusPembayaran = 'Belum Lunas';
            $statusClass = 'status-belum';
            }
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
                    {{ $item->customer->nomor_telepon ?? '-' }}
                </td>

                <td>
                    {{ ucfirst($item->metode_pembayaran) }}
                </td>

                <td class="text-center">
                    <span class="{{ $statusClass }}">
                        {{ $statusPembayaran }}
                    </span>
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
            @empty
            <tr>
                <td colspan="10" class="text-center">
                    Data laporan penjualan belum tersedia.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan ini dibuat otomatis oleh sistem Berkat Jaya Nusantara.
    </div>
</body>

</html>