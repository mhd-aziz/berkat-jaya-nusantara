<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Piutang</title>

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

        .status-lewat {
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
        LAPORAN PIUTANG
    </div>

    <div class="subtitle">
        Berkat Jaya Nusantara<br>
        Periode Jatuh Tempo:
        {{ $tanggalAwal === 'awal' ? 'Awal' : $tanggalAwal }}
        s/d
        {{ $tanggalAkhir === 'akhir' ? 'Akhir' : $tanggalAkhir }}
        |
        Dicetak: {{ now()->format('d-m-Y H:i') }}
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Total Data</div>
                <div class="summary-value">{{ $totalData }}</div>
            </td>

            <td>
                <div class="summary-label">Total Piutang</div>
                <div class="summary-value">
                    Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="summary-label">Total Dibayar</div>
                <div class="summary-value">
                    Rp {{ number_format($totalDibayar, 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="summary-label">Sisa Piutang</div>
                <div class="summary-value">
                    Rp {{ number_format($totalSisa, 0, ',', '.') }}
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="summary-label">Belum Lunas</div>
                <div class="summary-value">{{ $totalBelumLunas }}</div>
            </td>

            <td>
                <div class="summary-label">Sebagian Dibayar</div>
                <div class="summary-value">{{ $totalSebagian }}</div>
            </td>

            <td>
                <div class="summary-label">Lunas</div>
                <div class="summary-value">{{ $totalLunas }}</div>
            </td>

            <td>
                <div class="summary-label">Lewat Jatuh Tempo</div>
                <div class="summary-value">{{ $totalLewatJatuhTempo }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 12%;">Invoice</th>
                <th style="width: 16%;">Customer</th>
                <th style="width: 10%;">No. Telepon</th>
                <th style="width: 9%;">Jatuh Tempo</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 11%;">Total Piutang</th>
                <th style="width: 11%;">Dibayar</th>
                <th style="width: 11%;">Sisa</th>
                <th style="width: 7%;">Ket.</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($piutang as $item)
            @php
            $lewatJatuhTempo = $item->status_piutang !== 'lunas'
            && $item->tanggal_jatuh_tempo
            && $item->tanggal_jatuh_tempo->isPast();

            if ($item->status_piutang === 'lunas') {
            $statusText = 'Lunas';
            $statusClass = 'status-lunas';
            } elseif ($item->status_piutang === 'sebagian_dibayar') {
            $statusText = 'Sebagian Dibayar';
            $statusClass = 'status-sebagian';
            } else {
            $statusText = 'Belum Lunas';
            $statusClass = 'status-belum';
            }

            if ($item->status_piutang === 'lunas') {
            $keterangan = 'Selesai';
            $keteranganClass = 'status-lunas';
            } elseif ($lewatJatuhTempo) {
            $keterangan = 'Lewat Tempo';
            $keteranganClass = 'status-lewat';
            } else {
            $keterangan = 'Berjalan';
            $keteranganClass = 'status-belum';
            }
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
                    {{ $item->customer->nomor_telepon ?? '-' }}
                </td>

                <td class="text-center">
                    {{ $item->tanggal_jatuh_tempo ? $item->tanggal_jatuh_tempo->format('d-m-Y') : '-' }}
                </td>

                <td class="text-center">
                    <span class="{{ $statusClass }}">
                        {{ $statusText }}
                    </span>
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

                <td class="text-center">
                    <span class="{{ $keteranganClass }}">
                        {{ $keterangan }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">
                    Data laporan piutang belum tersedia.
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