<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Barang</title>

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
                LAPORAN STOK BARANG
            </td>
        </tr>

        <tr>
            <td colspan="11" class="subtitle">
                Batas Stok Rendah: {{ $batasStokRendah }}
            </td>
        </tr>

        <tr>
            <td colspan="11"></td>
        </tr>

        <tr class="header">
            <td>No</td>
            <td>Kode Barang</td>
            <td>Nama Barang</td>
            <td>Satuan</td>
            <td>Stok Saat Ini</td>
            <td>Harga Beli Terakhir</td>
            <td>Nilai Stok</td>
            <td>Harga Jual Default</td>
            <td>Estimasi Nilai Jual</td>
            <td>Status Stok</td>
            <td>Status Barang</td>
        </tr>

        @foreach ($barang as $item)
        @php
        $nilaiStok = $item->stok_saat_ini * ($item->harga_beli_terakhir ?? 0);
        $estimasiNilaiJual = $item->stok_saat_ini * ($item->harga_jual_default ?? 0);

        if ($item->stok_saat_ini <= 0) {
            $statusStok='Kosong' ;
            } elseif ($item->stok_saat_ini <= $batasStokRendah) {
                $statusStok='Stok Rendah' ;
                } else {
                $statusStok='Tersedia' ;
                }

                $statusBarang=$item->status_aktif ? 'Aktif' : 'Nonaktif';
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

                    <td>
                        {{ $statusStok }}
                    </td>

                    <td>
                        {{ $statusBarang }}
                    </td>
                </tr>
                @endforeach

                <tr>
                    <td colspan="11"></td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Total Jenis Barang</td>
                    <td colspan="6" class="text-center">
                        {{ $totalBarang }}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Total Stok</td>
                    <td colspan="6" class="text-center">
                        {{ number_format($totalStok, 0, ',', '.') }}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Jumlah Barang Kosong</td>
                    <td colspan="6" class="text-center">
                        {{ $totalBarangKosong }}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Jumlah Barang Stok Rendah</td>
                    <td colspan="6" class="text-center">
                        {{ $totalBarangStokRendah }}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Estimasi Nilai Stok</td>
                    <td colspan="6" class="text-right">
                        Rp {{ number_format($totalNilaiStok, 0, ',', '.') }}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="bold">Estimasi Nilai Jual</td>
                    <td colspan="6" class="text-right">
                        Rp {{ number_format($totalEstimasiNilaiJual, 0, ',', '.') }}
                    </td>
                </tr>
    </table>
</body>

</html>