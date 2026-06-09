<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
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
            font-size: 12px;
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

        .status-lengkap {
            color: #047857;
            font-weight: bold;
        }

        .status-sebagian {
            color: #1d4ed8;
            font-weight: bold;
        }

        .status-belum {
            color: #b91c1c;
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
        LAPORAN PEMBELIAN
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
                <div class="summary-label">Total Barang Dipesan</div>
                <div class="summary-value">{{ number_format($totalDipesan, 0, ',', '.') }}</div>
            </td>

            <td>
                <div class="summary-label">Total Barang Diterima</div>
                <div class="summary-value">{{ number_format($totalDiterima, 0, ',', '.') }}</div>
            </td>

            <td>
                <div class="summary-label">Total Sisa Belum Dikirim</div>
                <div class="summary-value">{{ number_format($totalSisa, 0, ',', '.') }}</div>
            </td>
        </tr>

        <tr>
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

            <td colspan="2">
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
                <th style="width: 12%;">No. Pembelian</th>
                <th style="width: 15%;">Supplier</th>
                <th style="width: 10%;">No. Telepon</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 7%;">Dipesan</th>
                <th style="width: 7%;">Diterima</th>
                <th style="width: 7%;">Sisa</th>
                <th style="width: 9%;">Subtotal</th>
                <th style="width: 6%;">Pajak</th>
                <th style="width: 10%;">Total</th>
                <th style="width: 7%;">Admin</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($pembelian as $item)
            @php
            $jumlahDipesan = 0;
            $jumlahDiterima = 0;

            foreach ($item->detailPembelian as $detail) {
            $jumlahDipesan += $detail->jumlah_dipesan ?? $detail->jumlah;
            $jumlahDiterima += $detail->jumlah;
            }

            $sisa = max($jumlahDipesan - $jumlahDiterima, 0);
            $statusPenerimaan = $item->status_penerimaan ?? 'lengkap';

            if ($statusPenerimaan === 'lengkap') {
            $statusText = 'Lengkap';
            $statusClass = 'status-lengkap';
            } elseif ($statusPenerimaan === 'sebagian') {
            $statusText = 'Sebagian';
            $statusClass = 'status-sebagian';
            } else {
            $statusText = 'Belum Dikirim';
            $statusClass = 'status-belum';
            }
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
                    {{ $item->supplier->nomor_telepon ?? '-' }}
                </td>

                <td class="text-center">
                    <span class="{{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </td>

                <td class="text-center">
                    {{ number_format($jumlahDipesan, 0, ',', '.') }}
                </td>

                <td class="text-center">
                    {{ number_format($jumlahDiterima, 0, ',', '.') }}
                </td>

                <td class="text-center">
                    {{ number_format($sisa, 0, ',', '.') }}
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
            @empty
            <tr>
                <td colspan="13" class="text-center">
                    Data laporan pembelian belum tersedia.
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