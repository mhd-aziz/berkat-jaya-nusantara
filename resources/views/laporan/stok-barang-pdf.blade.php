<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Barang</title>

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

        .status-kosong {
            color: #b91c1c;
            font-weight: bold;
        }

        .status-rendah {
            color: #92400e;
            font-weight: bold;
        }

        .status-tersedia {
            color: #047857;
            font-weight: bold;
        }

        .nonaktif {
            color: #b91c1c;
            font-size: 9px;
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
        LAPORAN STOK BARANG
    </div>

    <div class="subtitle">
        Berkat Jaya Nusantara<br>
        Batas Stok Rendah: {{ $batasStokRendah }} |
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Total Jenis Barang</div>
                <div class="summary-value">{{ $totalBarang }}</div>
            </td>

            <td>
                <div class="summary-label">Total Stok</div>
                <div class="summary-value">{{ number_format($totalStok, 0, ',', '.') }}</div>
            </td>

            <td>
                <div class="summary-label">Barang Kosong</div>
                <div class="summary-value">{{ $totalBarangKosong }}</div>
            </td>

            <td>
                <div class="summary-label">Stok Rendah</div>
                <div class="summary-value">{{ $totalBarangStokRendah }}</div>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="summary-label">Estimasi Nilai Stok</div>
                <div class="summary-value">
                    Rp {{ number_format($totalNilaiStok, 0, ',', '.') }}
                </div>
            </td>

            <td colspan="2">
                <div class="summary-label">Estimasi Nilai Jual</div>
                <div class="summary-value">
                    Rp {{ number_format($totalEstimasiNilaiJual, 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 9%;">Kode</th>
                <th style="width: 18%;">Nama Barang</th>
                <th style="width: 7%;">Satuan</th>
                <th style="width: 7%;">Stok</th>
                <th style="width: 11%;">Harga Beli</th>
                <th style="width: 12%;">Nilai Stok</th>
                <th style="width: 11%;">Harga Jual</th>
                <th style="width: 12%;">Estimasi Jual</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($barang as $item)
            @php
            $nilaiStok = $item->stok_saat_ini * ($item->harga_beli_terakhir ?? 0);
            $estimasiNilaiJual = $item->stok_saat_ini * ($item->harga_jual_default ?? 0);

            if ($item->stok_saat_ini <= 0) {
                $statusStok='Kosong' ;
                $statusClass='status-kosong' ;
                } elseif ($item->stok_saat_ini <= $batasStokRendah) {
                    $statusStok='Stok Rendah' ;
                    $statusClass='status-rendah' ;
                    } else {
                    $statusStok='Tersedia' ;
                    $statusClass='status-tersedia' ;
                    }
                    @endphp

                    <tr>
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $item->kode_barang }}
                    </td>

                    <td>
                        {{ $item->nama_barang }}

                        @if (!$item->status_aktif)
                        <br>
                        <span class="nonaktif">Barang nonaktif</span>
                        @endif
                    </td>

                    <td class="text-center">
                        {{ $item->satuan }}
                    </td>

                    <td class="text-center">
                        {{ number_format($item->stok_saat_ini, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp {{ number_format($item->harga_beli_terakhir ?? 0, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp {{ number_format($nilaiStok, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp {{ number_format($item->harga_jual_default ?? 0, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        Rp {{ number_format($estimasiNilaiJual, 0, ',', '.') }}
                    </td>

                    <td class="text-center">
                        <span class="{{ $statusClass }}">
                            {{ $statusStok }}
                        </span>
                    </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">
                            Data stok barang belum tersedia.
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