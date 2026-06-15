<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Piutang;
use App\Models\RiwayatStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PenjualanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $penjualan = Penjualan::with(['customer', 'user'])
            ->when($search, function ($query, $search) {
                $query->where('nomor_invoice', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('nama_customer', 'like', "%{$search}%");
                    });
            })
            ->orderBy('tanggal_penjualan', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('penjualan.index', compact('penjualan', 'search'));
    }

    public function create()
    {
        $customers = Customer::where('status_aktif', true)
            ->orderBy('nama_customer')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orderBy('nama_barang')
            ->get();

        return view('penjualan.create', compact(
            'customers',
            'barang'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_invoice' => [
                'required',
                'string',
                'max:100',
                Rule::unique('penjualan', 'nomor_invoice'),
            ],
            'tanggal_penjualan' => 'required|date',
            'id_customer' => 'required|exists:customers,id_customer',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'metode_pembayaran' => 'required|in:tunai,kredit',
            'tanggal_jatuh_tempo' => 'nullable|required_if:metode_pembayaran,kredit|date',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',

            'harga_jual' => 'required|array|min:1',
            'harga_jual.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $subtotalPenjualan = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlah = (int) $request->jumlah[$index];

                if ($jumlah > $barang->stok_saat_ini) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok barang ' . $barang->nama_barang . ' tidak mencukupi. Stok tersedia: ' . $barang->stok_saat_ini . ' ' . $barang->satuan,
                    ]);
                }

                $hargaJual = (float) $request->harga_jual[$index];
                $subtotalPenjualan += $this->hitungSubtotalDetail($barang, $jumlah, $hargaJual);
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPenjualan * ($persentasePajak / 100);

            $pajakDitambahkan = (bool) $request->boolean('pajak_ditambahkan');

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPenjualan + $nilaiPajak
                : $subtotalPenjualan;

            $statusPembayaran = $request->metode_pembayaran === 'tunai'
                ? 'lunas'
                : 'belum_lunas';

            $nomorInvoice = trim($request->nomor_invoice);

            $penjualan = Penjualan::create([
                'nomor_invoice' => $nomorInvoice,
                'tanggal_penjualan' => $request->tanggal_penjualan,
                'id_customer' => $request->id_customer,
                'subtotal' => $subtotalPenjualan,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => $statusPembayaran,
                'tanggal_jatuh_tempo' => $request->tanggal_jatuh_tempo,
                'catatan' => $request->catatan,
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                if ($jumlah > $barang->stok_saat_ini) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok barang ' . $barang->nama_barang . ' tidak mencukupi. Stok tersedia: ' . $barang->stok_saat_ini . ' ' . $barang->satuan,
                    ]);
                }

                $tipePerhitunganHarga = $barang->tipe_perhitungan_harga ?? 'normal';
                $satuanTransaksi = $barang->satuan;
                $satuanHitungHarga = $tipePerhitunganHarga === 'isi_kemasan'
                    ? $barang->satuan_hitung_harga
                    : $barang->satuan;

                $isiPerSatuan = $tipePerhitunganHarga === 'isi_kemasan'
                    ? (float) $barang->isi_per_satuan
                    : 1;

                $subtotalDetail = $this->hitungSubtotalDetail($barang, $jumlah, $hargaJual);

                DetailPenjualan::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_barang' => $barang->id_barang,
                    'jumlah' => $jumlah,
                    'harga_jual' => $hargaJual,
                    'tipe_perhitungan_harga' => $tipePerhitunganHarga,
                    'satuan_transaksi' => $satuanTransaksi,
                    'satuan_hitung_harga' => $satuanHitungHarga,
                    'isi_per_satuan' => $isiPerSatuan,
                    'subtotal' => $subtotalDetail,
                ]);

                $stokSebelum = $barang->stok_saat_ini;
                $stokSesudah = $stokSebelum - $jumlah;

                $barang->update([
                    'stok_saat_ini' => $stokSesudah,
                ]);

                RiwayatStok::create([
                    'id_barang' => $barang->id_barang,
                    'tanggal' => $request->tanggal_penjualan,
                    'jenis_pergerakan' => 'keluar',
                    'jumlah' => $jumlah,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'sumber_transaksi' => $penjualan->nomor_invoice,
                    'keterangan' => 'Stok keluar dari penjualan',
                    'dibuat_oleh' => Auth::id(),
                    'created_at' => now(),
                ]);
            }

            if ($request->metode_pembayaran === 'kredit') {
                Piutang::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'nomor_invoice' => $penjualan->nomor_invoice,
                    'id_customer' => $penjualan->id_customer,
                    'total_piutang' => $totalAkhir,
                    'total_dibayar' => 0,
                    'sisa_piutang' => $totalAkhir,
                    'tanggal_jatuh_tempo' => $request->tanggal_jatuh_tempo,
                    'status_piutang' => 'belum_lunas',
                    'catatan' => 'Piutang otomatis dari transaksi penjualan kredit',
                ]);
            }
        });

        return redirect()
            ->route('penjualan.index')
            ->with('success', 'Transaksi penjualan berhasil disimpan.');
    }

    public function edit(Penjualan $penjualan)
    {
        $penjualan->load([
            'customer',
            'detailPenjualan.barang',
            'piutang.pembayaranPiutang',
        ]);

        $customers = Customer::where('status_aktif', true)
            ->orWhere('id_customer', $penjualan->id_customer)
            ->orderBy('nama_customer')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orWhereIn('id_barang', $penjualan->detailPenjualan->pluck('id_barang'))
            ->orderBy('nama_barang')
            ->get();

        return view('penjualan.edit', compact(
            'penjualan',
            'customers',
            'barang'
        ));
    }

    public function update(Request $request, Penjualan $penjualan)
    {
        $request->validate([
            'nomor_invoice' => [
                'required',
                'string',
                'max:100',
                Rule::unique('penjualan', 'nomor_invoice')
                    ->ignore($penjualan->id_penjualan, 'id_penjualan'),
            ],
            'tanggal_penjualan' => 'required|date',
            'id_customer' => 'required|exists:customers,id_customer',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'metode_pembayaran' => 'required|in:tunai,kredit',
            'tanggal_jatuh_tempo' => 'nullable|required_if:metode_pembayaran,kredit|date',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',

            'harga_jual' => 'required|array|min:1',
            'harga_jual.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $penjualan) {
            $penjualan->load([
                'detailPenjualan',
                'piutang.pembayaranPiutang',
            ]);

            $totalDibayarLama = $penjualan->piutang
                ? (float) $penjualan->piutang->total_dibayar
                : 0;

            if ($request->metode_pembayaran === 'tunai' && $totalDibayarLama > 0) {
                throw ValidationException::withMessages([
                    'metode_pembayaran' => 'Penjualan kredit yang sudah memiliki pembayaran piutang tidak bisa diubah menjadi tunai. Hapus/atur pembayaran piutang terlebih dahulu jika memang diperlukan.',
                ]);
            }

            $affectStock = $penjualan->affect_stock ?? true;

            if ($affectStock) {
                foreach ($penjualan->detailPenjualan as $detailLama) {
                    $barangLama = Barang::where('id_barang', $detailLama->id_barang)
                        ->lockForUpdate()
                        ->first();

                    if (!$barangLama) {
                        continue;
                    }

                    $stokSebelum = $barangLama->stok_saat_ini;
                    $stokSesudah = $stokSebelum + $detailLama->jumlah;

                    $barangLama->update([
                        'stok_saat_ini' => $stokSesudah,
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $barangLama->id_barang,
                        'tanggal' => $request->tanggal_penjualan,
                        'jenis_pergerakan' => 'penyesuaian',
                        'jumlah' => $detailLama->jumlah,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'sumber_transaksi' => $penjualan->nomor_invoice,
                        'keterangan' => 'Pengembalian stok karena edit penjualan',
                        'dibuat_oleh' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            $subtotalPenjualan = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                if ($affectStock && $jumlah > $barang->stok_saat_ini) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok barang ' . $barang->nama_barang . ' tidak mencukupi. Stok tersedia: ' . $barang->stok_saat_ini . ' ' . $barang->satuan,
                    ]);
                }

                $subtotalPenjualan += $this->hitungSubtotalDetail($barang, $jumlah, $hargaJual);
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPenjualan * ($persentasePajak / 100);

            $pajakDitambahkan = (bool) $request->boolean('pajak_ditambahkan');

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPenjualan + $nilaiPajak
                : $subtotalPenjualan;

            $statusPembayaran = $this->hitungStatusPembayaran(
                $request->metode_pembayaran,
                $totalAkhir,
                $totalDibayarLama
            );

            $penjualan->update([
                'nomor_invoice' => trim($request->nomor_invoice),
                'tanggal_penjualan' => $request->tanggal_penjualan,
                'id_customer' => $request->id_customer,
                'subtotal' => $subtotalPenjualan,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => $statusPembayaran,
                'tanggal_jatuh_tempo' => $request->metode_pembayaran === 'kredit'
                    ? $request->tanggal_jatuh_tempo
                    : null,
                'catatan' => $request->catatan,
            ]);

            $penjualan->detailPenjualan()->delete();

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                if ($affectStock && $jumlah > $barang->stok_saat_ini) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok barang ' . $barang->nama_barang . ' tidak mencukupi. Stok tersedia: ' . $barang->stok_saat_ini . ' ' . $barang->satuan,
                    ]);
                }

                $tipePerhitunganHarga = $barang->tipe_perhitungan_harga ?? 'normal';
                $satuanTransaksi = $barang->satuan;
                $satuanHitungHarga = $tipePerhitunganHarga === 'isi_kemasan'
                    ? $barang->satuan_hitung_harga
                    : $barang->satuan;

                $isiPerSatuan = $tipePerhitunganHarga === 'isi_kemasan'
                    ? (float) $barang->isi_per_satuan
                    : 1;

                $subtotalDetail = $this->hitungSubtotalDetail($barang, $jumlah, $hargaJual);

                DetailPenjualan::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_barang' => $barang->id_barang,
                    'jumlah' => $jumlah,
                    'harga_jual' => $hargaJual,
                    'tipe_perhitungan_harga' => $tipePerhitunganHarga,
                    'satuan_transaksi' => $satuanTransaksi,
                    'satuan_hitung_harga' => $satuanHitungHarga,
                    'isi_per_satuan' => $isiPerSatuan,
                    'subtotal' => $subtotalDetail,
                ]);

                if ($affectStock) {
                    $stokSebelum = $barang->stok_saat_ini;
                    $stokSesudah = $stokSebelum - $jumlah;

                    $barang->update([
                        'stok_saat_ini' => $stokSesudah,
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $barang->id_barang,
                        'tanggal' => $request->tanggal_penjualan,
                        'jenis_pergerakan' => 'keluar',
                        'jumlah' => $jumlah,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'sumber_transaksi' => $penjualan->nomor_invoice,
                        'keterangan' => 'Stok keluar dari edit penjualan',
                        'dibuat_oleh' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            $this->sinkronkanPiutangSetelahEdit($penjualan, $request, $totalAkhir, $totalDibayarLama);
        });

        return redirect()
            ->route('penjualan.show', $penjualan->id_penjualan)
            ->with('success', 'Transaksi penjualan berhasil diperbarui.');
    }

    public function show(Penjualan $penjualan)
    {
        $penjualan->load([
            'customer',
            'user',
            'detailPenjualan.barang',
            'piutang',
        ]);

        return view('penjualan.show', compact('penjualan'));
    }

    public function exportExcel(Penjualan $penjualan)
    {
        $penjualan->load([
            'customer',
            'user',
            'detailPenjualan.barang',
            'piutang',
        ]);

        $pajakDitambahkan = $penjualan->pajak_ditambahkan ?? true;

        $namaPerusahaan = 'CV. BERKAT JAYA NUSANTARA';
        $alamatPerusahaan = 'Jl. Jelambar Utama 1 No. 6A RT. 007 RW. 004, Jakarta Barat 11460';
        $teleponPerusahaan = '(021) 5664892, 5676277';

        $formatAngka = function ($angka) {
            return rtrim(rtrim(number_format((float) $angka, 3, ',', '.'), '0'), ',');
        };

        $terbilang = function ($nilai) use (&$terbilang) {
            $nilai = abs((int) $nilai);

            $huruf = [
                '',
                'satu',
                'dua',
                'tiga',
                'empat',
                'lima',
                'enam',
                'tujuh',
                'delapan',
                'sembilan',
                'sepuluh',
                'sebelas',
            ];

            if ($nilai < 12) {
                return $huruf[$nilai];
            }

            if ($nilai < 20) {
                return $terbilang($nilai - 10) . ' belas';
            }

            if ($nilai < 100) {
                return $terbilang(floor($nilai / 10)) . ' puluh ' . $terbilang($nilai % 10);
            }

            if ($nilai < 200) {
                return 'seratus ' . $terbilang($nilai - 100);
            }

            if ($nilai < 1000) {
                return $terbilang(floor($nilai / 100)) . ' ratus ' . $terbilang($nilai % 100);
            }

            if ($nilai < 2000) {
                return 'seribu ' . $terbilang($nilai - 1000);
            }

            if ($nilai < 1000000) {
                return $terbilang(floor($nilai / 1000)) . ' ribu ' . $terbilang($nilai % 1000);
            }

            if ($nilai < 1000000000) {
                return $terbilang(floor($nilai / 1000000)) . ' juta ' . $terbilang($nilai % 1000000);
            }

            if ($nilai < 1000000000000) {
                return $terbilang(floor($nilai / 1000000000)) . ' miliar ' . $terbilang($nilai % 1000000000);
            }

            return $terbilang(floor($nilai / 1000000000000)) . ' triliun ' . $terbilang($nilai % 1000000000000);
        };

        $bersihkanTerbilang = function ($teks) {
            $teks = trim(preg_replace('/\s+/', ' ', $teks));

            return $teks === '' ? 'nol' : $teks;
        };

        $terbilangTotal = $bersihkanTerbilang($terbilang(round($penjualan->total_akhir))) . ' rupiah';

        $statusPembayaran = str_replace('_', ' ', ucfirst($penjualan->status_pembayaran ?? '-'));
        $metodePembayaran = ucfirst($penjualan->metode_pembayaran ?? '-');

        $modePajak = $pajakDitambahkan
            ? 'Pajak ditambahkan ke total akhir'
            : 'Pajak hanya ditampilkan dan tidak ditambahkan ke total akhir';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Invoice Penjualan');

        $spreadsheet->getProperties()
            ->setCreator('Berkat Jaya Nusantara')
            ->setLastModifiedBy('Berkat Jaya Nusantara')
            ->setTitle('Invoice Penjualan ' . $penjualan->nomor_invoice)
            ->setSubject('Invoice Penjualan')
            ->setDescription('Invoice penjualan Berkat Jaya Nusantara');

        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.3)
            ->setRight(0.25)
            ->setLeft(0.25)
            ->setBottom(0.3);

        $sheet->getDefaultRowDimension()->setRowHeight(20);

        $columnWidths = [
            'A' => 6,
            'B' => 16,
            'C' => 28,
            'D' => 12,
            'E' => 10,
            'F' => 15,
            'G' => 14,
            'H' => 16,
            'I' => 30,
            'J' => 18,
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $bottomBorder = [
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $headerFill = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
        ];

        $centerStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $leftStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ];

        $rightStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:I1');
        $sheet->mergeCells('B2:I2');
        $sheet->mergeCells('B3:I3');
        $sheet->mergeCells('J1:J3');

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $logoPath = public_path('assets/img/logo-bjn.png');

        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo Berkat Jaya Nusantara');
            $drawing->setDescription('Logo Berkat Jaya Nusantara');
            $drawing->setPath($logoPath);
            $drawing->setHeight(58);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('A1', 'BJN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        }

        $sheet->setCellValue('B1', $namaPerusahaan);
        $sheet->setCellValue('B2', $alamatPerusahaan);
        $sheet->setCellValue('B3', 'Telp: ' . $teleponPerusahaan);
        $sheet->setCellValue('J1', 'CUSTOMER');

        $sheet->getStyle('A1:J3')->applyFromArray($centerStyle);
        $sheet->getStyle('A1:J3')->applyFromArray($headerFill);
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('B2:B3')->getFont()->setSize(10);
        $sheet->getStyle('J1')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('J1:J3')->applyFromArray($thinBorder);

        $sheet->mergeCells('A4:J4');
        $sheet->getStyle('A4:J4')->applyFromArray($bottomBorder);

        $sheet->mergeCells('A5:E5');
        $sheet->mergeCells('F5:J5');
        $sheet->setCellValue('A5', 'INVOICE / NOTA PENJUALAN');
        $sheet->setCellValue('F5', 'Tanggal: ' . ($penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d-m-Y') : '-'));

        $sheet->mergeCells('A6:E6');
        $sheet->mergeCells('F6:J6');
        $sheet->setCellValue('A6', 'No: ' . $penjualan->nomor_invoice);
        $sheet->setCellValue('F6', 'Pembayaran: ' . $metodePembayaran);

        $sheet->mergeCells('A7:E7');
        $sheet->mergeCells('F7:J7');
        $sheet->setCellValue('F7', 'Status: ' . $statusPembayaran);

        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('F5:F7')->applyFromArray($rightStyle);
        $sheet->getStyle('A5:E7')->applyFromArray($leftStyle);

        $sheet->mergeCells('A9:E9');
        $sheet->mergeCells('F9:J9');
        $sheet->setCellValue('A9', 'Informasi Customer');
        $sheet->setCellValue('F9', 'Informasi Transaksi');

        $sheet->getStyle('A9:E9')->getFont()->setBold(true);
        $sheet->getStyle('F9:J9')->getFont()->setBold(true);
        $sheet->getStyle('A9:E9')->applyFromArray($bottomBorder);
        $sheet->getStyle('F9:J9')->applyFromArray($bottomBorder);

        $customer = $penjualan->customer;

        $sheet->setCellValue('A10', 'Nama');
        $sheet->mergeCells('B10:E10');
        $sheet->setCellValue('B10', ': ' . ($customer->nama_customer ?? '-'));

        $sheet->setCellValue('F10', 'Jatuh Tempo');
        $sheet->mergeCells('G10:J10');
        $sheet->setCellValue('G10', ': ' . ($penjualan->tanggal_jatuh_tempo ? $penjualan->tanggal_jatuh_tempo->format('d-m-Y') : '-'));

        $sheet->setCellValue('A11', 'Telepon');
        $sheet->mergeCells('B11:E11');
        $sheet->setCellValueExplicit('B11', ': ' . ($customer->nomor_telepon ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $sheet->setCellValue('F11', 'Admin');
        $sheet->mergeCells('G11:J11');
        $sheet->setCellValue('G11', ': ' . ($penjualan->user->nama_user ?? '-'));

        $sheet->setCellValue('A12', 'NPWP');
        $sheet->mergeCells('B12:E12');
        $sheet->setCellValueExplicit('B12', ': ' . ($customer->npwp ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $sheet->setCellValue('F12', 'Mode Pajak');
        $sheet->mergeCells('G12:J12');
        $sheet->setCellValue('G12', ': ' . $modePajak);

        $sheet->setCellValue('A13', 'Alamat');
        $sheet->mergeCells('B13:E13');
        $sheet->setCellValue('B13', ': ' . ($customer->alamat ?? '-'));

        $sheet->setCellValue('F13', 'Catatan');
        $sheet->mergeCells('G13:J13');
        $sheet->setCellValue('G13', ': ' . ($penjualan->catatan ?? '-'));

        $sheet->setCellValue('A14', 'Kategori');
        $sheet->mergeCells('B14:E14');
        $sheet->setCellValue('B14', ': ' . ($customer->kategori_customer ?? '-'));

        $sheet->getStyle('A10:A14')->getFont()->setBold(true);
        $sheet->getStyle('F10:F13')->getFont()->setBold(true);
        $sheet->getStyle('A10:J14')->applyFromArray($leftStyle);

        $sheet->mergeCells('A16:J16');
        $sheet->setCellValue('A16', 'Daftar Barang');
        $sheet->getStyle('A16')->getFont()->setBold(true);
        $sheet->getStyle('A16:J16')->applyFromArray($bottomBorder);

        $headerRow = 17;

        $headers = [
            'A' => 'No',
            'B' => 'Kode Barang',
            'C' => 'Nama Barang',
            'D' => 'Satuan',
            'E' => 'Qty',
            'F' => 'Satuan Harga',
            'G' => 'Isi/Satuan',
            'H' => 'Harga',
            'I' => 'Perhitungan',
            'J' => 'Subtotal',
        ];

        foreach ($headers as $column => $text) {
            $sheet->setCellValue($column . $headerRow, $text);
        }

        $sheet->getStyle('A' . $headerRow . ':J' . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow . ':J' . $headerRow)->applyFromArray($thinBorder);
        $sheet->getStyle('A' . $headerRow . ':J' . $headerRow)->applyFromArray($centerStyle);

        $row = $headerRow + 1;

        foreach ($penjualan->detailPenjualan as $index => $detail) {
            $tipePerhitungan = $detail->tipe_perhitungan_harga ?? 'normal';
            $satuanTransaksi = $detail->satuan_transaksi ?? ($detail->barang->satuan ?? '');
            $satuanHitung = $detail->satuan_hitung_harga ?? $satuanTransaksi;
            $isiPerSatuan = (float) ($detail->isi_per_satuan ?? 1);

            if ($tipePerhitungan === 'isi_kemasan') {
                $teksPerhitungan =
                    $detail->jumlah . ' ' . strtoupper($satuanTransaksi) .
                    ' x ' . $formatAngka($isiPerSatuan) . ' ' . strtoupper($satuanHitung) .
                    ' x Rp ' . number_format($detail->harga_jual, 0, ',', '.');
            } else {
                $teksPerhitungan =
                    $detail->jumlah . ' ' . strtoupper($satuanTransaksi) .
                    ' x Rp ' . number_format($detail->harga_jual, 0, ',', '.');
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $detail->barang->kode_barang ?? '-');
            $sheet->setCellValue('C' . $row, $detail->barang->nama_barang ?? '-');
            $sheet->setCellValue('D' . $row, strtoupper($satuanTransaksi));
            $sheet->setCellValue('E' . $row, $detail->jumlah);
            $sheet->setCellValue('F' . $row, strtoupper($tipePerhitungan === 'isi_kemasan' ? $satuanHitung : $satuanTransaksi));
            $sheet->setCellValue('G' . $row, $tipePerhitungan === 'isi_kemasan' ? $isiPerSatuan : 1);
            $sheet->setCellValue('H' . $row, $detail->harga_jual);
            $sheet->setCellValue('I' . $row, $teksPerhitungan);
            $sheet->setCellValue('J' . $row, $detail->subtotal);

            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($thinBorder);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($leftStyle);
            $sheet->getStyle('A' . $row)->applyFromArray($centerStyle);
            $sheet->getStyle('D' . $row . ':G' . $row)->applyFromArray($centerStyle);
            $sheet->getStyle('H' . $row . ':J' . $row)->applyFromArray($rightStyle);

            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.###');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.###');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');

            $row++;
        }

        $totalStartRow = $row;

        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->setCellValue('I' . $row, 'Subtotal');
        $sheet->setCellValue('J' . $row, $penjualan->subtotal);
        $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('I' . $row . ':J' . $row)->applyFromArray($bottomBorder);
        $sheet->getStyle('I' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $row++;

        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->setCellValue('I' . $row, 'Pajak ' . number_format($penjualan->persentase_pajak, 2, ',', '.') . '%');
        $sheet->setCellValue('J' . $row, $penjualan->nilai_pajak);
        $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('I' . $row . ':J' . $row)->applyFromArray($bottomBorder);
        $sheet->getStyle('I' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $row++;

        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->setCellValue('I' . $row, 'Total Akhir');
        $sheet->setCellValue('J' . $row, $penjualan->total_akhir);
        $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('I' . $row . ':J' . $row)->applyFromArray($bottomBorder);
        $sheet->getStyle('I' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->applyFromArray($rightStyle);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $row++;

        if (!$pajakDitambahkan) {
            $sheet->mergeCells('I' . $row . ':J' . $row);
            $sheet->setCellValue('I' . $row, 'Catatan: Pajak hanya ditampilkan dan tidak ditambahkan ke total akhir.');
            $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setItalic(true)->setSize(10);
            $sheet->getStyle('I' . $row . ':J' . $row)->applyFromArray($rightStyle);
            $row++;
        }

        $row++;

        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'Terbilang: ' . $terbilangTotal);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
        $row++;

        $sheet->mergeCells('A' . $row . ':D' . ($row + 3));
        $sheet->setCellValue(
            'A' . $row,
            "Berkat\nBCA : 5280902227\n------------------------------\nBerkat\nOCBC NISP : 565 8000 15150"
        );
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('B91C1C');
        $sheet->getStyle('A' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $row += 5;

        if ($penjualan->piutang) {
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->setCellValue('A' . $row, 'Informasi Piutang');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($bottomBorder);
            $row++;

            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':F' . $row);
            $sheet->mergeCells('G' . $row . ':H' . $row);

            $sheet->setCellValue('A' . $row, 'Total Piutang');
            $sheet->setCellValue('C' . $row, $penjualan->piutang->total_piutang);
            $sheet->setCellValue('E' . $row, 'Total Dibayar');
            $sheet->setCellValue('G' . $row, $penjualan->piutang->total_dibayar);
            $sheet->setCellValue('I' . $row, 'Sisa Piutang');
            $sheet->setCellValue('J' . $row, $penjualan->piutang->sisa_piutang);

            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($thinBorder);
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
            $row++;

            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':F' . $row);
            $sheet->mergeCells('G' . $row . ':J' . $row);

            $sheet->setCellValue('A' . $row, 'Tanggal Jatuh Tempo');
            $sheet->setCellValue('C' . $row, $penjualan->piutang->tanggal_jatuh_tempo ? $penjualan->piutang->tanggal_jatuh_tempo->format('d-m-Y') : '-');
            $sheet->setCellValue('E' . $row, 'Status Piutang');
            $sheet->setCellValue('G' . $row, str_replace('_', ' ', ucfirst($penjualan->piutang->status_piutang)));

            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($thinBorder);
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
            $row++;

            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->mergeCells('C' . $row . ':J' . $row);
            $sheet->setCellValue('A' . $row, 'Catatan Piutang');
            $sheet->setCellValue('C' . $row, $penjualan->piutang->catatan ?? '-');
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($thinBorder);
            $row += 2;
        }

        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->mergeCells('F' . $row . ':J' . $row);
        $sheet->setCellValue('A' . $row, 'Penerima,');
        $sheet->setCellValue('F' . $row, 'Hormat Kami,');
        $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($centerStyle);
        $row++;

        $signatureStartRow = $row;
        $signatureEndRow = $row + 3;

        $sheet->mergeCells('A' . $signatureStartRow . ':E' . $signatureEndRow);
        $sheet->mergeCells('F' . $signatureStartRow . ':J' . $signatureEndRow);

        for ($i = $signatureStartRow; $i <= $signatureEndRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(18);
        }

        $row = $signatureEndRow + 1;

        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->mergeCells('F' . $row . ':J' . $row);
        $sheet->setCellValue('A' . $row, $customer->nama_customer ?? 'Customer');
        $sheet->setCellValue('F' . $row, $namaPerusahaan);
        $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($centerStyle);

        $sheet->getStyle('A1:J' . $row)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:J' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A1:J' . $row)->getFont()->setName('Arial');

        $sheet->getStyle('A' . $totalStartRow . ':J' . $row)->getAlignment()->setWrapText(true);

        // $sheet->freezePane('A18');

        $safeInvoice = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $penjualan->nomor_invoice ?? 'nota');
        $safeInvoice = trim(preg_replace('/-+/', '-', $safeInvoice), '-');
        $fileName = 'Invoice-' . ($safeInvoice ?: 'nota') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function hitungSubtotalDetail(Barang $barang, int $jumlah, float $hargaJual): float
    {
        $tipePerhitunganHarga = $barang->tipe_perhitungan_harga ?? 'normal';

        if ($tipePerhitunganHarga === 'isi_kemasan') {
            $isiPerSatuan = (float) ($barang->isi_per_satuan ?? 1);

            return $jumlah * $isiPerSatuan * $hargaJual;
        }

        return $jumlah * $hargaJual;
    }

    private function hitungStatusPembayaran(string $metodePembayaran, float $totalAkhir, float $totalDibayar): string
    {
        if ($metodePembayaran === 'tunai') {
            return 'lunas';
        }

        if ($totalDibayar >= $totalAkhir && $totalAkhir > 0) {
            return 'lunas';
        }

        if ($totalDibayar > 0) {
            return 'sebagian';
        }

        return 'belum_lunas';
    }

    private function sinkronkanPiutangSetelahEdit(Penjualan $penjualan, Request $request, float $totalAkhir, float $totalDibayarLama): void
    {
        $penjualan->load('piutang');

        if ($request->metode_pembayaran === 'tunai') {
            if ($penjualan->piutang && $totalDibayarLama <= 0) {
                $penjualan->piutang->delete();
            }

            return;
        }

        $sisaPiutang = max($totalAkhir - $totalDibayarLama, 0);
        $statusPiutang = $this->hitungStatusPiutang(
            $sisaPiutang,
            $totalDibayarLama,
            $request->tanggal_jatuh_tempo
        );

        if ($penjualan->piutang) {
            $penjualan->piutang->update([
                'nomor_invoice' => $penjualan->nomor_invoice,
                'id_customer' => $penjualan->id_customer,
                'total_piutang' => $totalAkhir,
                'total_dibayar' => $totalDibayarLama,
                'sisa_piutang' => $sisaPiutang,
                'tanggal_jatuh_tempo' => $request->tanggal_jatuh_tempo,
                'status_piutang' => $statusPiutang,
                'catatan' => 'Piutang diperbarui dari edit transaksi penjualan',
            ]);

            return;
        }

        Piutang::create([
            'id_penjualan' => $penjualan->id_penjualan,
            'nomor_invoice' => $penjualan->nomor_invoice,
            'id_customer' => $penjualan->id_customer,
            'total_piutang' => $totalAkhir,
            'total_dibayar' => 0,
            'sisa_piutang' => $totalAkhir,
            'tanggal_jatuh_tempo' => $request->tanggal_jatuh_tempo,
            'status_piutang' => 'belum_lunas',
            'catatan' => 'Piutang otomatis dari edit transaksi penjualan kredit',
        ]);
    }

    private function hitungStatusPiutang(float $sisaPiutang, float $totalDibayar, ?string $tanggalJatuhTempo): string
    {
        if ($sisaPiutang <= 0) {
            return 'lunas';
        }

        if ($tanggalJatuhTempo && now()->toDateString() > $tanggalJatuhTempo) {
            return 'jatuh_tempo';
        }

        if ($totalDibayar > 0) {
            return 'sebagian_dibayar';
        }

        return 'belum_lunas';
    }

    private function generateNomorInvoice(bool $lock = false)
    {
        $tanggal = now()->format('Ymd');
        $prefix = 'INV-' . $tanggal . '-';

        $query = Penjualan::where('nomor_invoice', 'like', $prefix . '%')
            ->orderBy('nomor_invoice', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastPenjualan = $query->first();

        if (!$lastPenjualan) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastPenjualan->nomor_invoice, -4);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
