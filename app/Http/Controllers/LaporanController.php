<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Piutang;
use App\Models\RiwayatStok;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function penjualan(Request $request)
    {
        $query = $this->queryLaporanPenjualan($request);

        $penjualanUntukTotal = (clone $query)->get();
        $ringkasan = $this->hitungRingkasanPenjualan($penjualanUntukTotal);

        $penjualan = $query
            ->orderBy('tanggal_penjualan', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::where('status_aktif', true)
            ->orderBy('nama_customer')
            ->get();

        return view('laporan.penjualan', array_merge([
            'penjualan' => $penjualan,
            'customers' => $customers,
        ], $ringkasan));
    }

    public function penjualanExportExcel(Request $request)
    {
        $penjualan = $this->queryLaporanPenjualan($request)
            ->orderBy('tanggal_penjualan', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $ringkasan = $this->hitungRingkasanPenjualan($penjualan);

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Penjualan', $tanggalAwal, $tanggalAkhir, 'xls');

        return response()
            ->view('laporan.penjualan-excel', array_merge([
                'penjualan' => $penjualan,
                'tanggalAwal' => $tanggalAwal,
                'tanggalAkhir' => $tanggalAkhir,
            ], $ringkasan))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function penjualanExportPdf(Request $request)
    {
        $penjualan = $this->queryLaporanPenjualan($request)
            ->orderBy('tanggal_penjualan', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $ringkasan = $this->hitungRingkasanPenjualan($penjualan);

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Penjualan', $tanggalAwal, $tanggalAkhir, 'pdf');

        $pdf = Pdf::loadView('laporan.penjualan-pdf', array_merge([
            'penjualan' => $penjualan,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ], $ringkasan))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function pembelian(Request $request)
    {
        $query = $this->queryLaporanPembelian($request);

        $pembelianUntukTotal = (clone $query)->get();
        $ringkasan = $this->hitungRingkasanPembelian($pembelianUntukTotal);

        $pembelian = $query
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::where('status_aktif', true)
            ->orderBy('nama_supplier')
            ->get();

        return view('laporan.pembelian', array_merge([
            'pembelian' => $pembelian,
            'suppliers' => $suppliers,
        ], $ringkasan));
    }

    public function pembelianExportExcel(Request $request)
    {
        $pembelian = $this->queryLaporanPembelian($request)
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $ringkasan = $this->hitungRingkasanPembelian($pembelian);

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Pembelian', $tanggalAwal, $tanggalAkhir, 'xls');

        return response()
            ->view('laporan.pembelian-excel', array_merge([
                'pembelian' => $pembelian,
                'tanggalAwal' => $tanggalAwal,
                'tanggalAkhir' => $tanggalAkhir,
            ], $ringkasan))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function pembelianExportPdf(Request $request)
    {
        $pembelian = $this->queryLaporanPembelian($request)
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $ringkasan = $this->hitungRingkasanPembelian($pembelian);

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Pembelian', $tanggalAwal, $tanggalAkhir, 'pdf');

        $pdf = Pdf::loadView('laporan.pembelian-pdf', array_merge([
            'pembelian' => $pembelian,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ], $ringkasan))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function piutang(Request $request)
    {
        $query = $this->queryLaporanPiutang($request);

        $piutangUntukTotal = (clone $query)->get();

        $totalData = $piutangUntukTotal->count();
        $totalPiutang = $piutangUntukTotal->sum('total_piutang');
        $totalDibayar = $piutangUntukTotal->sum('total_dibayar');
        $totalSisa = $piutangUntukTotal->sum('sisa_piutang');

        $totalBelumLunas = $piutangUntukTotal
            ->where('status_piutang', 'belum_lunas')
            ->count();

        $totalSebagian = $piutangUntukTotal
            ->where('status_piutang', 'sebagian_dibayar')
            ->count();

        $totalLunas = $piutangUntukTotal
            ->where('status_piutang', 'lunas')
            ->count();

        $totalLewatJatuhTempo = $piutangUntukTotal
            ->filter(function ($item) {
                return $item->status_piutang !== 'lunas'
                    && $item->tanggal_jatuh_tempo
                    && $item->tanggal_jatuh_tempo->isPast();
            })
            ->count();

        $totalHistoris = $piutangUntukTotal
            ->filter(function ($item) {
                return (bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $totalSistemBerjalan = $piutangUntukTotal
            ->filter(function ($item) {
                return !(bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $piutang = $query
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::where('status_aktif', true)
            ->orderBy('nama_customer')
            ->get();

        return view('laporan.piutang', compact(
            'piutang',
            'customers',
            'totalData',
            'totalPiutang',
            'totalDibayar',
            'totalSisa',
            'totalBelumLunas',
            'totalSebagian',
            'totalLunas',
            'totalLewatJatuhTempo',
            'totalHistoris',
            'totalSistemBerjalan'
        ));
    }

    public function piutangExportExcel(Request $request)
    {
        $piutang = $this->queryLaporanPiutang($request)
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalData = $piutang->count();
        $totalPiutang = $piutang->sum('total_piutang');
        $totalDibayar = $piutang->sum('total_dibayar');
        $totalSisa = $piutang->sum('sisa_piutang');

        $totalBelumLunas = $piutang
            ->where('status_piutang', 'belum_lunas')
            ->count();

        $totalSebagian = $piutang
            ->where('status_piutang', 'sebagian_dibayar')
            ->count();

        $totalLunas = $piutang
            ->where('status_piutang', 'lunas')
            ->count();

        $totalLewatJatuhTempo = $piutang
            ->filter(function ($item) {
                return $item->status_piutang !== 'lunas'
                    && $item->tanggal_jatuh_tempo
                    && $item->tanggal_jatuh_tempo->isPast();
            })
            ->count();

        $totalHistoris = $piutang
            ->filter(function ($item) {
                return (bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $totalSistemBerjalan = $piutang
            ->filter(function ($item) {
                return !(bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Piutang', $tanggalAwal, $tanggalAkhir, 'xls');

        return response()
            ->view('laporan.piutang-excel', compact(
                'piutang',
                'totalData',
                'totalPiutang',
                'totalDibayar',
                'totalSisa',
                'totalBelumLunas',
                'totalSebagian',
                'totalLunas',
                'totalLewatJatuhTempo',
                'totalHistoris',
                'totalSistemBerjalan',
                'tanggalAwal',
                'tanggalAkhir'
            ))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function piutangExportPdf(Request $request)
    {
        $piutang = $this->queryLaporanPiutang($request)
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalData = $piutang->count();
        $totalPiutang = $piutang->sum('total_piutang');
        $totalDibayar = $piutang->sum('total_dibayar');
        $totalSisa = $piutang->sum('sisa_piutang');

        $totalBelumLunas = $piutang
            ->where('status_piutang', 'belum_lunas')
            ->count();

        $totalSebagian = $piutang
            ->where('status_piutang', 'sebagian_dibayar')
            ->count();

        $totalLunas = $piutang
            ->where('status_piutang', 'lunas')
            ->count();

        $totalLewatJatuhTempo = $piutang
            ->filter(function ($item) {
                return $item->status_piutang !== 'lunas'
                    && $item->tanggal_jatuh_tempo
                    && $item->tanggal_jatuh_tempo->isPast();
            })
            ->count();

        $totalHistoris = $piutang
            ->filter(function ($item) {
                return (bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $totalSistemBerjalan = $piutang
            ->filter(function ($item) {
                return !(bool) ($item->penjualan->is_historical ?? false);
            })
            ->count();

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Piutang', $tanggalAwal, $tanggalAkhir, 'pdf');

        $pdf = Pdf::loadView('laporan.piutang-pdf', compact(
            'piutang',
            'totalData',
            'totalPiutang',
            'totalDibayar',
            'totalSisa',
            'totalBelumLunas',
            'totalSebagian',
            'totalLunas',
            'totalLewatJatuhTempo',
            'totalHistoris',
            'totalSistemBerjalan',
            'tanggalAwal',
            'tanggalAkhir'
        ))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function stokBarang(Request $request)
    {
        $batasStokRendah = (int) ($request->batas_stok_rendah ?? 5);

        $query = $this->queryLaporanStokBarang($request, $batasStokRendah);

        $barangUntukTotal = (clone $query)->get();

        $totalBarang = $barangUntukTotal->count();
        $totalStok = $barangUntukTotal->sum('stok_saat_ini');

        $totalBarangKosong = $barangUntukTotal
            ->where('stok_saat_ini', '<=', 0)
            ->count();

        $totalBarangStokRendah = $barangUntukTotal
            ->filter(function ($barang) use ($batasStokRendah) {
                return $barang->stok_saat_ini > 0
                    && $barang->stok_saat_ini <= $batasStokRendah;
            })
            ->count();

        $totalNilaiStok = $barangUntukTotal->sum(function ($barang) {
            return $barang->stok_saat_ini * ($barang->harga_beli_terakhir ?? 0);
        });

        $totalEstimasiNilaiJual = $barangUntukTotal->sum(function ($barang) {
            return $barang->stok_saat_ini * ($barang->harga_jual_default ?? 0);
        });

        $barang = $query
            ->orderBy('nama_barang', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.stok-barang', compact(
            'barang',
            'batasStokRendah',
            'totalBarang',
            'totalStok',
            'totalBarangKosong',
            'totalBarangStokRendah',
            'totalNilaiStok',
            'totalEstimasiNilaiJual'
        ));
    }

    public function stokBarangExportExcel(Request $request)
    {
        $batasStokRendah = (int) ($request->batas_stok_rendah ?? 5);

        $barang = $this->queryLaporanStokBarang($request, $batasStokRendah)
            ->orderBy('nama_barang', 'asc')
            ->get();

        $totalBarang = $barang->count();
        $totalStok = $barang->sum('stok_saat_ini');

        $totalBarangKosong = $barang
            ->where('stok_saat_ini', '<=', 0)
            ->count();

        $totalBarangStokRendah = $barang
            ->filter(function ($item) use ($batasStokRendah) {
                return $item->stok_saat_ini > 0
                    && $item->stok_saat_ini <= $batasStokRendah;
            })
            ->count();

        $totalNilaiStok = $barang->sum(function ($item) {
            return $item->stok_saat_ini * ($item->harga_beli_terakhir ?? 0);
        });

        $totalEstimasiNilaiJual = $barang->sum(function ($item) {
            return $item->stok_saat_ini * ($item->harga_jual_default ?? 0);
        });

        $fileName = 'Laporan-Stok-Barang.xls';

        return response()
            ->view('laporan.stok-barang-excel', compact(
                'barang',
                'batasStokRendah',
                'totalBarang',
                'totalStok',
                'totalBarangKosong',
                'totalBarangStokRendah',
                'totalNilaiStok',
                'totalEstimasiNilaiJual'
            ))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function stokBarangExportPdf(Request $request)
    {
        $batasStokRendah = (int) ($request->batas_stok_rendah ?? 5);

        $barang = $this->queryLaporanStokBarang($request, $batasStokRendah)
            ->orderBy('nama_barang', 'asc')
            ->get();

        $totalBarang = $barang->count();
        $totalStok = $barang->sum('stok_saat_ini');

        $totalBarangKosong = $barang
            ->where('stok_saat_ini', '<=', 0)
            ->count();

        $totalBarangStokRendah = $barang
            ->filter(function ($item) use ($batasStokRendah) {
                return $item->stok_saat_ini > 0
                    && $item->stok_saat_ini <= $batasStokRendah;
            })
            ->count();

        $totalNilaiStok = $barang->sum(function ($item) {
            return $item->stok_saat_ini * ($item->harga_beli_terakhir ?? 0);
        });

        $totalEstimasiNilaiJual = $barang->sum(function ($item) {
            return $item->stok_saat_ini * ($item->harga_jual_default ?? 0);
        });

        $fileName = 'Laporan-Stok-Barang.pdf';

        $pdf = Pdf::loadView('laporan.stok-barang-pdf', compact(
            'barang',
            'batasStokRendah',
            'totalBarang',
            'totalStok',
            'totalBarangKosong',
            'totalBarangStokRendah',
            'totalNilaiStok',
            'totalEstimasiNilaiJual'
        ))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function riwayatStok(Request $request)
    {
        $query = $this->queryLaporanRiwayatStok($request);

        $riwayatUntukTotal = (clone $query)->get();

        $totalData = $riwayatUntukTotal->count();

        $totalMasuk = $riwayatUntukTotal
            ->where('jenis_pergerakan', 'masuk')
            ->sum('jumlah');

        $totalKeluar = $riwayatUntukTotal
            ->where('jenis_pergerakan', 'keluar')
            ->sum('jumlah');

        $totalPenyesuaian = $riwayatUntukTotal
            ->where('jenis_pergerakan', 'penyesuaian')
            ->count();

        $totalOpname = $riwayatUntukTotal
            ->filter(function ($item) {
                return str_starts_with((string) $item->sumber_transaksi, 'STOCK-OPNAME');
            })
            ->count();

        $totalSelisihPlus = $riwayatUntukTotal
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) > 0;
            })
            ->sum(function ($item) {
                return (int) $item->stok_sesudah - (int) $item->stok_sebelum;
            });

        $totalSelisihMinus = $riwayatUntukTotal
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) < 0;
            })
            ->sum(function ($item) {
                return abs((int) $item->stok_sesudah - (int) $item->stok_sebelum);
            });

        $riwayatStok = $query
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $barang = Barang::orderBy('nama_barang')->get();

        return view('laporan.riwayat-stok', compact(
            'riwayatStok',
            'barang',
            'totalData',
            'totalMasuk',
            'totalKeluar',
            'totalPenyesuaian',
            'totalOpname',
            'totalSelisihPlus',
            'totalSelisihMinus'
        ));
    }

    public function riwayatStokExportExcel(Request $request)
    {
        $riwayatStok = $this->queryLaporanRiwayatStok($request)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalData = $riwayatStok->count();

        $totalMasuk = $riwayatStok
            ->where('jenis_pergerakan', 'masuk')
            ->sum('jumlah');

        $totalKeluar = $riwayatStok
            ->where('jenis_pergerakan', 'keluar')
            ->sum('jumlah');

        $totalPenyesuaian = $riwayatStok
            ->where('jenis_pergerakan', 'penyesuaian')
            ->count();

        $totalOpname = $riwayatStok
            ->filter(function ($item) {
                return str_starts_with((string) $item->sumber_transaksi, 'STOCK-OPNAME');
            })
            ->count();

        $totalSelisihPlus = $riwayatStok
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) > 0;
            })
            ->sum(function ($item) {
                return (int) $item->stok_sesudah - (int) $item->stok_sebelum;
            });

        $totalSelisihMinus = $riwayatStok
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) < 0;
            })
            ->sum(function ($item) {
                return abs((int) $item->stok_sesudah - (int) $item->stok_sebelum);
            });

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Riwayat-Stok', $tanggalAwal, $tanggalAkhir, 'xls');

        return response()
            ->view('laporan.riwayat-stok-excel', compact(
                'riwayatStok',
                'totalData',
                'totalMasuk',
                'totalKeluar',
                'totalPenyesuaian',
                'totalOpname',
                'totalSelisihPlus',
                'totalSelisihMinus',
                'tanggalAwal',
                'tanggalAkhir'
            ))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function riwayatStokExportPdf(Request $request)
    {
        $riwayatStok = $this->queryLaporanRiwayatStok($request)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalData = $riwayatStok->count();

        $totalMasuk = $riwayatStok
            ->where('jenis_pergerakan', 'masuk')
            ->sum('jumlah');

        $totalKeluar = $riwayatStok
            ->where('jenis_pergerakan', 'keluar')
            ->sum('jumlah');

        $totalPenyesuaian = $riwayatStok
            ->where('jenis_pergerakan', 'penyesuaian')
            ->count();

        $totalOpname = $riwayatStok
            ->filter(function ($item) {
                return str_starts_with((string) $item->sumber_transaksi, 'STOCK-OPNAME');
            })
            ->count();

        $totalSelisihPlus = $riwayatStok
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) > 0;
            })
            ->sum(function ($item) {
                return (int) $item->stok_sesudah - (int) $item->stok_sebelum;
            });

        $totalSelisihMinus = $riwayatStok
            ->filter(function ($item) {
                return $item->jenis_pergerakan === 'penyesuaian'
                    && ((int) $item->stok_sesudah - (int) $item->stok_sebelum) < 0;
            })
            ->sum(function ($item) {
                return abs((int) $item->stok_sesudah - (int) $item->stok_sebelum);
            });

        $tanggalAwal = $request->tanggal_awal ?: 'awal';
        $tanggalAkhir = $request->tanggal_akhir ?: 'akhir';

        $fileName = $this->namaFileLaporan('Laporan-Riwayat-Stok', $tanggalAwal, $tanggalAkhir, 'pdf');

        $pdf = Pdf::loadView('laporan.riwayat-stok-pdf', compact(
            'riwayatStok',
            'totalData',
            'totalMasuk',
            'totalKeluar',
            'totalPenyesuaian',
            'totalOpname',
            'totalSelisihPlus',
            'totalSelisihMinus',
            'tanggalAwal',
            'tanggalAkhir'
        ))->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    private function queryLaporanPenjualan(Request $request)
    {
        return Penjualan::with(['customer', 'user', 'piutang'])
            ->when($request->tanggal_awal, function ($query) use ($request) {
                $query->whereDate('tanggal_penjualan', '>=', $request->tanggal_awal);
            })
            ->when($request->tanggal_akhir, function ($query) use ($request) {
                $query->whereDate('tanggal_penjualan', '<=', $request->tanggal_akhir);
            })
            ->when($request->id_customer, function ($query) use ($request) {
                $query->where('id_customer', $request->id_customer);
            })
            ->when($request->metode_pembayaran, function ($query) use ($request) {
                $query->where('metode_pembayaran', $request->metode_pembayaran);
            })
            ->when($request->status_pembayaran, function ($query) use ($request) {
                $query->where('status_pembayaran', $request->status_pembayaran);
            })
            ->when($request->tipe_invoice === 'historis', function ($query) {
                $query->where('is_historical', true);
            })
            ->when($request->tipe_invoice === 'sistem', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('is_historical', false)
                        ->orWhereNull('is_historical');
                });
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nomor_invoice', 'like', "%{$search}%")
                        ->orWhere('nomor_dokumen_asli', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('nama_customer', 'like', "%{$search}%")
                                ->orWhere('nomor_telepon', 'like', "%{$search}%")
                                ->orWhere('npwp', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function queryLaporanPembelian(Request $request)
    {
        return Pembelian::with(['supplier', 'user', 'detailPembelian.barang'])
            ->when($request->tanggal_awal, function ($query) use ($request) {
                $query->whereDate('tanggal_pembelian', '>=', $request->tanggal_awal);
            })
            ->when($request->tanggal_akhir, function ($query) use ($request) {
                $query->whereDate('tanggal_pembelian', '<=', $request->tanggal_akhir);
            })
            ->when($request->id_supplier, function ($query) use ($request) {
                $query->where('id_supplier', $request->id_supplier);
            })
            ->when($request->status_penerimaan, function ($query) use ($request) {
                $query->where('status_penerimaan', $request->status_penerimaan);
            })
            ->when($request->tipe_invoice === 'historis', function ($query) {
                $query->where('is_historical', true);
            })
            ->when($request->tipe_invoice === 'sistem', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('is_historical', false)
                        ->orWhereNull('is_historical');
                });
            })
            ->when($request->pengaruh_stok === 'mempengaruhi', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('affect_stock', true)
                        ->orWhereNull('affect_stock');
                });
            })
            ->when($request->pengaruh_stok === 'tidak_mempengaruhi', function ($query) {
                $query->where('affect_stock', false);
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nomor_pembelian', 'like', "%{$search}%")
                        ->orWhere('nomor_dokumen_asli', 'like', "%{$search}%")
                        ->orWhere('nomor_delivery_order', 'like', "%{$search}%")
                        ->orWhere('nomor_surat_jalan', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('nama_supplier', 'like', "%{$search}%")
                                ->orWhere('nomor_telepon', 'like', "%{$search}%")
                                ->orWhere('npwp', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function queryLaporanPiutang(Request $request)
    {
        return Piutang::with(['customer', 'penjualan'])
            ->when($request->tanggal_awal, function ($query) use ($request) {
                $query->whereDate('tanggal_jatuh_tempo', '>=', $request->tanggal_awal);
            })
            ->when($request->tanggal_akhir, function ($query) use ($request) {
                $query->whereDate('tanggal_jatuh_tempo', '<=', $request->tanggal_akhir);
            })
            ->when($request->id_customer, function ($query) use ($request) {
                $query->where('id_customer', $request->id_customer);
            })
            ->when($request->status_piutang, function ($query) use ($request) {
                $query->where('status_piutang', $request->status_piutang);
            })
            ->when($request->jatuh_tempo === 'lewat', function ($query) {
                $query->where('status_piutang', '!=', 'lunas')
                    ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString());
            })
            ->when($request->jatuh_tempo === 'belum', function ($query) {
                $query->where('status_piutang', '!=', 'lunas')
                    ->whereDate('tanggal_jatuh_tempo', '>=', now()->toDateString());
            })
            ->when($request->tipe_invoice === 'historis', function ($query) {
                $query->whereHas('penjualan', function ($penjualanQuery) {
                    $penjualanQuery->where('is_historical', true);
                });
            })
            ->when($request->tipe_invoice === 'sistem', function ($query) {
                $query->whereHas('penjualan', function ($penjualanQuery) {
                    $penjualanQuery->where(function ($subQuery) {
                        $subQuery->where('is_historical', false)
                            ->orWhereNull('is_historical');
                    });
                });
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nomor_invoice', 'like', "%{$search}%")
                        ->orWhereHas('penjualan', function ($penjualanQuery) use ($search) {
                            $penjualanQuery->where('nomor_dokumen_asli', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('nama_customer', 'like', "%{$search}%")
                                ->orWhere('nomor_telepon', 'like', "%{$search}%")
                                ->orWhere('npwp', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function queryLaporanStokBarang(Request $request, int $batasStokRendah)
    {
        return Barang::query()
            ->when($request->status_barang !== null && $request->status_barang !== '', function ($query) use ($request) {
                $query->where('status_aktif', $request->status_barang);
            })
            ->when($request->kondisi_stok === 'kosong', function ($query) {
                $query->where('stok_saat_ini', '<=', 0);
            })
            ->when($request->kondisi_stok === 'rendah', function ($query) use ($batasStokRendah) {
                $query->where('stok_saat_ini', '>', 0)
                    ->where('stok_saat_ini', '<=', $batasStokRendah);
            })
            ->when($request->kondisi_stok === 'tersedia', function ($query) use ($batasStokRendah) {
                $query->where('stok_saat_ini', '>', $batasStokRendah);
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('satuan', 'like', "%{$search}%");
                });
            });
    }

    private function queryLaporanRiwayatStok(Request $request)
    {
        return RiwayatStok::with(['barang', 'user'])
            ->when($request->tanggal_awal, function ($query) use ($request) {
                $query->whereDate('tanggal', '>=', $request->tanggal_awal);
            })
            ->when($request->tanggal_akhir, function ($query) use ($request) {
                $query->whereDate('tanggal', '<=', $request->tanggal_akhir);
            })
            ->when($request->id_barang, function ($query) use ($request) {
                $query->where('id_barang', $request->id_barang);
            })
            ->when($request->jenis_pergerakan, function ($query) use ($request) {
                $query->where('jenis_pergerakan', $request->jenis_pergerakan);
            })
            ->when($request->tipe_riwayat === 'opname', function ($query) {
                $query->where('sumber_transaksi', 'like', 'STOCK-OPNAME%');
            })
            ->when($request->tipe_riwayat === 'non_opname', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('sumber_transaksi')
                        ->orWhere('sumber_transaksi', 'not like', 'STOCK-OPNAME%');
                });
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('sumber_transaksi', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%")
                        ->orWhereHas('barang', function ($barangQuery) use ($search) {
                            $barangQuery->where('kode_barang', 'like', "%{$search}%")
                                ->orWhere('nama_barang', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function hitungRingkasanPenjualan($penjualan): array
    {
        $totalTransaksi = $penjualan->count();
        $totalSubtotal = $penjualan->sum('subtotal');
        $totalPajak = $penjualan->sum('nilai_pajak');
        $totalAkhir = $penjualan->sum('total_akhir');

        $totalTunai = $penjualan
            ->where('metode_pembayaran', 'tunai')
            ->sum('total_akhir');

        $totalKredit = $penjualan
            ->where('metode_pembayaran', 'kredit')
            ->sum('total_akhir');

        $totalHistoris = $penjualan
            ->filter(function ($item) {
                return (bool) ($item->is_historical ?? false);
            })
            ->count();

        $totalSistemBerjalan = $penjualan
            ->filter(function ($item) {
                return !(bool) ($item->is_historical ?? false);
            })
            ->count();

        $totalPiutang = $penjualan->sum(function ($item) {
            return $item->piutang->total_piutang ?? 0;
        });

        $totalDibayar = $penjualan->sum(function ($item) {
            return $item->piutang->total_dibayar ?? 0;
        });

        $totalSisaPiutang = $penjualan->sum(function ($item) {
            return $item->piutang->sisa_piutang ?? 0;
        });

        return compact(
            'totalTransaksi',
            'totalSubtotal',
            'totalPajak',
            'totalAkhir',
            'totalTunai',
            'totalKredit',
            'totalHistoris',
            'totalSistemBerjalan',
            'totalPiutang',
            'totalDibayar',
            'totalSisaPiutang'
        );
    }

    private function hitungRingkasanPembelian($pembelian): array
    {
        $totalTransaksi = $pembelian->count();
        $totalSubtotal = $pembelian->sum('subtotal');
        $totalPajak = $pembelian->sum('nilai_pajak');
        $totalAkhir = $pembelian->sum('total_akhir');

        $totalDipesan = 0;
        $totalDiterima = 0;

        foreach ($pembelian as $item) {
            foreach ($item->detailPembelian as $detail) {
                $jumlahDipesan = $detail->jumlah_dipesan ?? $detail->jumlah;
                $jumlahDiterima = $detail->jumlah;

                $totalDipesan += $jumlahDipesan;
                $totalDiterima += $jumlahDiterima;
            }
        }

        $totalSisa = max($totalDipesan - $totalDiterima, 0);

        $totalLengkap = $pembelian
            ->where('status_penerimaan', 'lengkap')
            ->count();

        $totalSebagian = $pembelian
            ->where('status_penerimaan', 'sebagian')
            ->count();

        $totalBelumDikirim = $pembelian
            ->where('status_penerimaan', 'belum_dikirim')
            ->count();

        $totalHistoris = $pembelian
            ->filter(function ($item) {
                return (bool) ($item->is_historical ?? false);
            })
            ->count();

        $totalSistemBerjalan = $pembelian
            ->filter(function ($item) {
                return !(bool) ($item->is_historical ?? false);
            })
            ->count();

        $totalMemengaruhiStok = $pembelian
            ->filter(function ($item) {
                return (bool) ($item->affect_stock ?? true);
            })
            ->count();

        $totalTidakMemengaruhiStok = $pembelian
            ->filter(function ($item) {
                return !(bool) ($item->affect_stock ?? true);
            })
            ->count();

        return compact(
            'totalTransaksi',
            'totalSubtotal',
            'totalPajak',
            'totalAkhir',
            'totalDipesan',
            'totalDiterima',
            'totalSisa',
            'totalLengkap',
            'totalSebagian',
            'totalBelumDikirim',
            'totalHistoris',
            'totalSistemBerjalan',
            'totalMemengaruhiStok',
            'totalTidakMemengaruhiStok'
        );
    }

    private function namaFileLaporan(string $prefix, string $tanggalAwal, string $tanggalAkhir, string $extension): string
    {
        $awal = $tanggalAwal ?: 'awal';
        $akhir = $tanggalAkhir ?: 'akhir';

        $namaFile = $prefix . '-' . $awal . '-sd-' . $akhir;
        $namaFile = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $namaFile);
        $namaFile = trim(preg_replace('/-+/', '-', $namaFile), '-');

        return $namaFile . '.' . $extension;
    }
}
