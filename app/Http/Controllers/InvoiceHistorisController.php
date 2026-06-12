<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Piutang;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceHistorisController extends Controller
{
    public function index()
    {
        $pembelianHistoris = Pembelian::with('supplier')
            ->where('is_historical', true)
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $penjualanHistoris = Penjualan::with('customer')
            ->where('is_historical', true)
            ->orderBy('tanggal_penjualan', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('invoice-historis.index', compact(
            'pembelianHistoris',
            'penjualanHistoris'
        ));
    }

    public function createPembelian()
    {
        $nomorPembelian = $this->generateNomorPembelianHistoris();

        $suppliers = Supplier::where('status_aktif', true)
            ->orderBy('nama_supplier')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orderBy('nama_barang')
            ->get();

        return view('invoice-historis.create-pembelian', compact(
            'nomorPembelian',
            'suppliers',
            'barang'
        ));
    }

    public function storePembelian(Request $request)
    {
        $request->validate([
            'nomor_dokumen_asli' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pembelian', 'nomor_dokumen_asli'),
            ],
            'tanggal_pembelian' => 'required|date',
            'id_supplier' => 'required|exists:suppliers,id_supplier',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',

            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $subtotalPembelian = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $subtotalPembelian += (int) $request->jumlah[$index] * (float) $request->harga_beli[$index];
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPembelian * ($persentasePajak / 100);

            $pajakDitambahkan = $request->has('pajak_ditambahkan')
                ? (bool) $request->boolean('pajak_ditambahkan')
                : true;

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPembelian + $nilaiPajak
                : $subtotalPembelian;

            $pembelian = Pembelian::create([
                'nomor_pembelian' => $this->generateNomorPembelianHistoris(),
                'is_historical' => true,
                'affect_stock' => false,
                'status_penerimaan' => 'lengkap',
                'nomor_dokumen_asli' => trim($request->nomor_dokumen_asli),
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'id_supplier' => $request->id_supplier,
                'subtotal' => $subtotalPembelian,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'catatan' => $request->catatan,
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($request->id_barang as $index => $idBarang) {
                $jumlah = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $idBarang,
                    'jumlah_dipesan' => $jumlah,
                    'jumlah' => $jumlah,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $jumlah * $hargaBeli,
                ]);
            }
        });

        return redirect()
            ->route('invoice-historis.index')
            ->with('success', 'Invoice pembelian lama berhasil disimpan tanpa memengaruhi stok.');
    }

    public function showPembelian(Pembelian $pembelian)
    {
        $this->pastikanPembelianHistoris($pembelian);

        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        return view('pembelian.show', compact('pembelian'));
    }

    public function editPembelian(Pembelian $pembelian)
    {
        $this->pastikanPembelianHistoris($pembelian);

        $pembelian->load([
            'supplier',
            'detailPembelian.barang',
        ]);

        $suppliers = Supplier::where('status_aktif', true)
            ->orWhere('id_supplier', $pembelian->id_supplier)
            ->orderBy('nama_supplier')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orWhereIn('id_barang', $pembelian->detailPembelian->pluck('id_barang'))
            ->orderBy('nama_barang')
            ->get();

        return view('invoice-historis.edit-pembelian', compact(
            'pembelian',
            'suppliers',
            'barang'
        ));
    }

    public function updatePembelian(Request $request, Pembelian $pembelian)
    {
        $this->pastikanPembelianHistoris($pembelian);

        $request->validate([
            'nomor_dokumen_asli' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pembelian', 'nomor_dokumen_asli')
                    ->ignore($pembelian->id_pembelian, 'id_pembelian'),
            ],
            'tanggal_pembelian' => 'required|date',
            'id_supplier' => 'required|exists:suppliers,id_supplier',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',

            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $pembelian) {
            $subtotalPembelian = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $subtotalPembelian += (int) $request->jumlah[$index] * (float) $request->harga_beli[$index];
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPembelian * ($persentasePajak / 100);

            $pajakDitambahkan = $request->has('pajak_ditambahkan')
                ? (bool) $request->boolean('pajak_ditambahkan')
                : true;

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPembelian + $nilaiPajak
                : $subtotalPembelian;

            $pembelian->update([
                'is_historical' => true,
                'affect_stock' => false,
                'status_penerimaan' => 'lengkap',
                'nomor_dokumen_asli' => trim($request->nomor_dokumen_asli),
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'id_supplier' => $request->id_supplier,
                'subtotal' => $subtotalPembelian,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'catatan' => $request->catatan,
            ]);

            $pembelian->detailPembelian()->delete();

            foreach ($request->id_barang as $index => $idBarang) {
                $jumlah = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $idBarang,
                    'jumlah_dipesan' => $jumlah,
                    'jumlah' => $jumlah,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $jumlah * $hargaBeli,
                ]);
            }
        });

        return redirect()
            ->route('invoice-historis.pembelian.show', [
                'pembelian' => $pembelian->id_pembelian,
                'back_url' => route('invoice-historis.index'),
            ])
            ->with('success', 'Invoice pembelian lama berhasil diperbarui tanpa memengaruhi stok.');
    }

    public function exportPembelianExcel(Pembelian $pembelian)
    {
        $this->pastikanPembelianHistoris($pembelian);

        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        $nomorFile = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $pembelian->nomor_pembelian ?? 'nota-pembelian-lama');
        $nomorFile = trim(preg_replace('/-+/', '-', $nomorFile), '-');

        $fileName = 'Invoice-Historis-Pembelian-' . $nomorFile . '.xls';

        return response()
            ->view('pembelian.export-excel', compact('pembelian'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function createPenjualan()
    {
        $nomorInvoice = $this->generateNomorInvoiceHistoris();

        $customers = Customer::where('status_aktif', true)
            ->orderBy('nama_customer')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orderBy('nama_barang')
            ->get();

        return view('invoice-historis.create-penjualan', compact(
            'nomorInvoice',
            'customers',
            'barang'
        ));
    }

    public function storePenjualan(Request $request)
    {
        $request->validate([
            'nomor_dokumen_asli' => [
                'required',
                'string',
                'max:255',
                Rule::unique('penjualan', 'nomor_dokumen_asli'),
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
                $barang = Barang::findOrFail($idBarang);
                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                $subtotalPenjualan += $this->hitungSubtotalDetailPenjualan($barang, $jumlah, $hargaJual);
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPenjualan * ($persentasePajak / 100);

            $pajakDitambahkan = $request->has('pajak_ditambahkan')
                ? (bool) $request->boolean('pajak_ditambahkan')
                : true;

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPenjualan + $nilaiPajak
                : $subtotalPenjualan;

            $statusPembayaran = $request->metode_pembayaran === 'tunai'
                ? 'lunas'
                : 'belum_lunas';

            $penjualan = Penjualan::create([
                'nomor_invoice' => $this->generateNomorInvoiceHistoris(),
                'is_historical' => true,
                'affect_stock' => false,
                'nomor_dokumen_asli' => trim($request->nomor_dokumen_asli),
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
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::findOrFail($idBarang);
                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                $this->buatDetailPenjualanHistoris($penjualan, $barang, $jumlah, $hargaJual);
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
                    'catatan' => 'Piutang dari invoice penjualan lama sebelum sistem digitalisasi',
                ]);
            }
        });

        return redirect()
            ->route('invoice-historis.index')
            ->with('success', 'Invoice penjualan lama berhasil disimpan tanpa mengurangi stok.');
    }

    public function showPenjualan(Penjualan $penjualan)
    {
        $this->pastikanPenjualanHistoris($penjualan);

        $penjualan->load([
            'customer',
            'user',
            'detailPenjualan.barang',
            'piutang',
        ]);

        return view('penjualan.show', compact('penjualan'));
    }

    public function editPenjualan(Penjualan $penjualan)
    {
        $this->pastikanPenjualanHistoris($penjualan);

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

        return view('invoice-historis.edit-penjualan', compact(
            'penjualan',
            'customers',
            'barang'
        ));
    }

    public function updatePenjualan(Request $request, Penjualan $penjualan)
    {
        $this->pastikanPenjualanHistoris($penjualan);

        $request->validate([
            'nomor_dokumen_asli' => [
                'required',
                'string',
                'max:255',
                Rule::unique('penjualan', 'nomor_dokumen_asli')
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
            $penjualan->load('piutang');

            $totalDibayarLama = $penjualan->piutang
                ? (float) $penjualan->piutang->total_dibayar
                : 0;

            if ($request->metode_pembayaran === 'tunai' && $totalDibayarLama > 0) {
                throw ValidationException::withMessages([
                    'metode_pembayaran' => 'Invoice kredit yang sudah memiliki pembayaran piutang tidak bisa diubah menjadi tunai. Hapus/atur pembayaran piutang terlebih dahulu jika memang diperlukan.',
                ]);
            }

            $subtotalPenjualan = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::findOrFail($idBarang);
                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                $subtotalPenjualan += $this->hitungSubtotalDetailPenjualan($barang, $jumlah, $hargaJual);
            }

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPenjualan * ($persentasePajak / 100);

            $pajakDitambahkan = $request->has('pajak_ditambahkan')
                ? (bool) $request->boolean('pajak_ditambahkan')
                : true;

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPenjualan + $nilaiPajak
                : $subtotalPenjualan;

            $statusPembayaran = $this->hitungStatusPembayaran(
                $request->metode_pembayaran,
                $totalAkhir,
                $totalDibayarLama
            );

            $penjualan->update([
                'is_historical' => true,
                'affect_stock' => false,
                'nomor_dokumen_asli' => trim($request->nomor_dokumen_asli),
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
                $barang = Barang::findOrFail($idBarang);
                $jumlah = (int) $request->jumlah[$index];
                $hargaJual = (float) $request->harga_jual[$index];

                $this->buatDetailPenjualanHistoris($penjualan, $barang, $jumlah, $hargaJual);
            }

            $this->sinkronkanPiutangHistorisSetelahEdit(
                $penjualan,
                $request,
                $totalAkhir,
                $totalDibayarLama
            );
        });

        return redirect()
            ->route('invoice-historis.penjualan.show', [
                'penjualan' => $penjualan->id_penjualan,
                'back_url' => route('invoice-historis.index'),
            ])
            ->with('success', 'Invoice penjualan lama berhasil diperbarui tanpa memengaruhi stok.');
    }

    public function exportPenjualanExcel(Penjualan $penjualan)
    {
        $this->pastikanPenjualanHistoris($penjualan);

        $penjualan->load([
            'customer',
            'user',
            'detailPenjualan.barang',
            'piutang',
        ]);

        $nomorFile = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $penjualan->nomor_invoice ?? 'invoice-penjualan-lama');
        $nomorFile = trim(preg_replace('/-+/', '-', $nomorFile), '-');

        $fileName = 'Invoice-Historis-Penjualan-' . $nomorFile . '.xls';

        return response()
            ->view('penjualan.export-excel', compact('penjualan'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    private function pastikanPembelianHistoris(Pembelian $pembelian): void
    {
        abort_unless((bool) $pembelian->is_historical, 404);
    }

    private function pastikanPenjualanHistoris(Penjualan $penjualan): void
    {
        abort_unless((bool) $penjualan->is_historical, 404);
    }

    private function hitungSubtotalDetailPenjualan(Barang $barang, int $jumlah, float $hargaJual): float
    {
        $tipePerhitunganHarga = $barang->tipe_perhitungan_harga ?? 'normal';

        if ($tipePerhitunganHarga === 'isi_kemasan') {
            $isiPerSatuan = (float) ($barang->isi_per_satuan ?? 1);

            return $jumlah * $isiPerSatuan * $hargaJual;
        }

        return $jumlah * $hargaJual;
    }

    private function buatDetailPenjualanHistoris(Penjualan $penjualan, Barang $barang, int $jumlah, float $hargaJual): void
    {
        $tipePerhitunganHarga = $barang->tipe_perhitungan_harga ?? 'normal';
        $satuanTransaksi = $barang->satuan;
        $satuanHitungHarga = $tipePerhitunganHarga === 'isi_kemasan'
            ? $barang->satuan_hitung_harga
            : $barang->satuan;

        $isiPerSatuan = $tipePerhitunganHarga === 'isi_kemasan'
            ? (float) ($barang->isi_per_satuan ?? 1)
            : 1;

        $subtotalDetail = $this->hitungSubtotalDetailPenjualan($barang, $jumlah, $hargaJual);

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

    private function sinkronkanPiutangHistorisSetelahEdit(Penjualan $penjualan, Request $request, float $totalAkhir, float $totalDibayarLama): void
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
                'catatan' => 'Piutang diperbarui dari edit invoice penjualan lama',
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
            'catatan' => 'Piutang dari invoice penjualan lama sebelum sistem digitalisasi',
        ]);
    }

    private function generateNomorPembelianHistoris()
    {
        $tanggal = now()->format('Ymd');

        $lastPembelian = Pembelian::where('is_historical', true)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id_pembelian', 'desc')
            ->first();

        if (!$lastPembelian) {
            return 'HPB-' . $tanggal . '-0001';
        }

        $lastNumber = (int) substr($lastPembelian->nomor_pembelian, -4);

        return 'HPB-' . $tanggal . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorInvoiceHistoris()
    {
        $tanggal = now()->format('Ymd');

        $lastPenjualan = Penjualan::where('is_historical', true)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id_penjualan', 'desc')
            ->first();

        if (!$lastPenjualan) {
            return 'HINV-' . $tanggal . '-0001';
        }

        $lastNumber = (int) substr($lastPenjualan->nomor_invoice, -4);

        return 'HINV-' . $tanggal . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
