<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPembelian;
use App\Models\Pembelian;
use App\Models\RiwayatStok;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PembelianController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $pembelian = Pembelian::with(['supplier', 'user'])
            ->when($search, function ($query, $search) {
                $query->where('nomor_pembelian', 'like', "%{$search}%")
                    ->orWhere('nomor_delivery_order', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_jalan', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('nama_supplier', 'like', "%{$search}%");
                    });
            })
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pembelian.index', compact('pembelian', 'search'));
    }

    public function create()
    {
        $nomorPembelian = $this->generateNomorPembelian();
        $nomorDeliveryOrder = $this->generateNomorDeliveryOrder();
        $nomorSuratJalan = $this->generateNomorSuratJalan();

        $suppliers = Supplier::where('status_aktif', true)
            ->orderBy('nama_supplier')
            ->get();

        $barang = Barang::where('status_aktif', true)
            ->orderBy('nama_barang')
            ->get();

        return view('pembelian.create', compact(
            'nomorPembelian',
            'nomorDeliveryOrder',
            'nomorSuratJalan',
            'suppliers',
            'barang'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_pembelian' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_pembelian'),
            ],
            'nomor_delivery_order' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_delivery_order'),
            ],
            'nomor_surat_jalan' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_surat_jalan'),
            ],
            'tanggal_pembelian' => 'required|date',
            'id_supplier' => 'required|exists:suppliers,id_supplier',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah_dipesan' => 'required|array|min:1',
            'jumlah_dipesan.*' => 'required|integer|min:1',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:0',

            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $subtotalPembelian = 0;
            $totalDipesan = 0;
            $totalDiterima = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $jumlahDipesan = (int) $request->jumlah_dipesan[$index];
                $jumlahDiterima = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];

                if ($jumlahDiterima > $jumlahDipesan) {
                    throw ValidationException::withMessages([
                        'jumlah' => 'Jumlah diterima tidak boleh lebih besar dari jumlah dipesan.',
                    ]);
                }

                $totalDipesan += $jumlahDipesan;
                $totalDiterima += $jumlahDiterima;
                $subtotalPembelian += $jumlahDiterima * $hargaBeli;
            }

            if ($totalDiterima <= 0) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Minimal harus ada barang yang diterima.',
                ]);
            }

            $statusPenerimaan = $totalDiterima < $totalDipesan
                ? 'sebagian'
                : 'lengkap';

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPembelian * ($persentasePajak / 100);

            $pajakDitambahkan = (bool) $request->boolean('pajak_ditambahkan');

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPembelian + $nilaiPajak
                : $subtotalPembelian;

            $pembelian = Pembelian::create([
                'nomor_pembelian' => trim($request->nomor_pembelian),
                'nomor_delivery_order' => $this->ubahKosongMenjadiNull($request->nomor_delivery_order),
                'nomor_surat_jalan' => $this->ubahKosongMenjadiNull($request->nomor_surat_jalan),
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'id_supplier' => $request->id_supplier,
                'status_penerimaan' => $statusPenerimaan,
                'subtotal' => $subtotalPembelian,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'catatan' => $request->catatan,
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlahDipesan = (int) $request->jumlah_dipesan[$index];
                $jumlahDiterima = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];
                $subtotalDetail = $jumlahDiterima * $hargaBeli;

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $barang->id_barang,
                    'jumlah_dipesan' => $jumlahDipesan,
                    'jumlah' => $jumlahDiterima,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotalDetail,
                ]);

                if ($jumlahDiterima > 0) {
                    $stokSebelum = $barang->stok_saat_ini;
                    $stokSesudah = $stokSebelum + $jumlahDiterima;

                    $barang->update([
                        'stok_saat_ini' => $stokSesudah,
                        'harga_beli_terakhir' => $hargaBeli,
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $barang->id_barang,
                        'tanggal' => $request->tanggal_pembelian,
                        'jenis_pergerakan' => 'masuk',
                        'jumlah' => $jumlahDiterima,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'sumber_transaksi' => $pembelian->nomor_pembelian,
                        'keterangan' => 'Stok masuk dari pembelian. Dipesan: ' . $jumlahDipesan . ', diterima: ' . $jumlahDiterima,
                        'dibuat_oleh' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Transaksi pembelian berhasil disimpan dan stok barang berhasil diperbarui sesuai jumlah diterima.');
    }

    public function edit(Pembelian $pembelian)
    {
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

        return view('pembelian.edit', compact(
            'pembelian',
            'suppliers',
            'barang'
        ));
    }

    public function update(Request $request, Pembelian $pembelian)
    {
        $request->validate([
            'nomor_pembelian' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_pembelian')
                    ->ignore($pembelian->id_pembelian, 'id_pembelian'),
            ],
            'nomor_delivery_order' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_delivery_order')
                    ->ignore($pembelian->id_pembelian, 'id_pembelian'),
            ],
            'nomor_surat_jalan' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_surat_jalan')
                    ->ignore($pembelian->id_pembelian, 'id_pembelian'),
            ],
            'tanggal_pembelian' => 'required|date',
            'id_supplier' => 'required|exists:suppliers,id_supplier',
            'persentase_pajak' => 'nullable|numeric|min:0|max:100',
            'pajak_ditambahkan' => 'nullable|in:0,1',
            'catatan' => 'nullable|string',

            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id_barang',

            'jumlah_dipesan' => 'required|array|min:1',
            'jumlah_dipesan.*' => 'required|integer|min:1',

            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:0',

            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $pembelian) {
            $pembelian->load('detailPembelian');

            $subtotalPembelian = 0;
            $totalDipesan = 0;
            $totalDiterima = 0;

            foreach ($request->id_barang as $index => $idBarang) {
                $jumlahDipesan = (int) $request->jumlah_dipesan[$index];
                $jumlahDiterima = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];

                if ($jumlahDiterima > $jumlahDipesan) {
                    throw ValidationException::withMessages([
                        'jumlah' => 'Jumlah diterima tidak boleh lebih besar dari jumlah dipesan.',
                    ]);
                }

                $totalDipesan += $jumlahDipesan;
                $totalDiterima += $jumlahDiterima;
                $subtotalPembelian += $jumlahDiterima * $hargaBeli;
            }

            if ($totalDiterima <= 0) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Minimal harus ada barang yang diterima.',
                ]);
            }

            $affectStock = $pembelian->affect_stock ?? true;

            if ($affectStock) {
                foreach ($pembelian->detailPembelian as $detailLama) {
                    $jumlahLama = (int) $detailLama->jumlah;

                    if ($jumlahLama <= 0) {
                        continue;
                    }

                    $barangLama = Barang::where('id_barang', $detailLama->id_barang)
                        ->lockForUpdate()
                        ->first();

                    if (!$barangLama) {
                        continue;
                    }

                    if ($barangLama->stok_saat_ini < $jumlahLama) {
                        throw ValidationException::withMessages([
                            'stok' => 'Pembelian tidak bisa diedit karena stok barang "' . $barangLama->nama_barang . '" sudah berkurang/dipakai. Stok tersedia: ' . $barangLama->stok_saat_ini . ', stok lama yang harus dikembalikan: ' . $jumlahLama . '.',
                        ]);
                    }

                    $stokSebelum = $barangLama->stok_saat_ini;
                    $stokSesudah = $stokSebelum - $jumlahLama;

                    $barangLama->update([
                        'stok_saat_ini' => $stokSesudah,
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $barangLama->id_barang,
                        'tanggal' => $request->tanggal_pembelian,
                        'jenis_pergerakan' => 'penyesuaian',
                        'jumlah' => $jumlahLama,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'sumber_transaksi' => $pembelian->nomor_pembelian,
                        'keterangan' => 'Pengurangan stok karena edit pembelian lama',
                        'dibuat_oleh' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            $statusPenerimaan = $totalDiterima < $totalDipesan
                ? 'sebagian'
                : 'lengkap';

            $persentasePajak = (float) ($request->persentase_pajak ?? 0);
            $nilaiPajak = $subtotalPembelian * ($persentasePajak / 100);

            $pajakDitambahkan = (bool) $request->boolean('pajak_ditambahkan');

            $totalAkhir = $pajakDitambahkan
                ? $subtotalPembelian + $nilaiPajak
                : $subtotalPembelian;

            $nomorPembelianLama = $pembelian->nomor_pembelian;

            $pembelian->update([
                'nomor_pembelian' => trim($request->nomor_pembelian),
                'nomor_delivery_order' => $this->ubahKosongMenjadiNull($request->nomor_delivery_order),
                'nomor_surat_jalan' => $this->ubahKosongMenjadiNull($request->nomor_surat_jalan),
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'id_supplier' => $request->id_supplier,
                'status_penerimaan' => $statusPenerimaan,
                'subtotal' => $subtotalPembelian,
                'persentase_pajak' => $persentasePajak,
                'nilai_pajak' => $nilaiPajak,
                'pajak_ditambahkan' => $pajakDitambahkan,
                'total_akhir' => $totalAkhir,
                'catatan' => $request->catatan,
            ]);

            $pembelian->detailPembelian()->delete();

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->firstOrFail();

                $jumlahDipesan = (int) $request->jumlah_dipesan[$index];
                $jumlahDiterima = (int) $request->jumlah[$index];
                $hargaBeli = (float) $request->harga_beli[$index];
                $subtotalDetail = $jumlahDiterima * $hargaBeli;

                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $barang->id_barang,
                    'jumlah_dipesan' => $jumlahDipesan,
                    'jumlah' => $jumlahDiterima,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $subtotalDetail,
                ]);

                if ($affectStock && $jumlahDiterima > 0) {
                    $stokSebelum = $barang->stok_saat_ini;
                    $stokSesudah = $stokSebelum + $jumlahDiterima;

                    $barang->update([
                        'stok_saat_ini' => $stokSesudah,
                        'harga_beli_terakhir' => $hargaBeli,
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $barang->id_barang,
                        'tanggal' => $request->tanggal_pembelian,
                        'jenis_pergerakan' => 'masuk',
                        'jumlah' => $jumlahDiterima,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'sumber_transaksi' => $pembelian->nomor_pembelian,
                        'keterangan' => 'Stok masuk dari edit pembelian. Nomor lama: ' . $nomorPembelianLama . '. Dipesan: ' . $jumlahDipesan . ', diterima: ' . $jumlahDiterima,
                        'dibuat_oleh' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('pembelian.show', $pembelian->id_pembelian)
            ->with('success', 'Transaksi pembelian berhasil diperbarui dan stok barang sudah disesuaikan.');
    }

    public function show(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        return view('pembelian.show', compact('pembelian'));
    }

    public function deliveryOrder(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        return view('pembelian.delivery-order', compact('pembelian'));
    }

    public function suratJalan(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        return view('pembelian.surat-jalan', compact('pembelian'));
    }

    public function exportExcel(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'user',
            'detailPembelian.barang',
        ]);

        $fileName = 'Nota-Pembelian-' . $pembelian->nomor_pembelian . '.xls';

        return response()
            ->view('pembelian.export-excel', compact('pembelian'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    private function generateNomorPembelian(bool $lock = false)
    {
        $tanggal = now()->format('Ymd');
        $prefix = 'PB-' . $tanggal . '-';

        $query = Pembelian::where('nomor_pembelian', 'like', $prefix . '%')
            ->orderBy('nomor_pembelian', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastPembelian = $query->first();

        if (!$lastPembelian) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastPembelian->nomor_pembelian, -4);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorDeliveryOrder(bool $lock = false)
    {
        $tanggal = now()->format('Ymd');
        $prefix = 'DO-SUP-' . $tanggal . '-';

        $query = Pembelian::where('nomor_delivery_order', 'like', $prefix . '%')
            ->orderBy('nomor_delivery_order', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastPembelian = $query->first();

        if (!$lastPembelian || !$lastPembelian->nomor_delivery_order) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastPembelian->nomor_delivery_order, -4);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorSuratJalan(bool $lock = false)
    {
        $tanggal = now()->format('Ymd');
        $prefix = 'SJ-SUP-' . $tanggal . '-';

        $query = Pembelian::where('nomor_surat_jalan', 'like', $prefix . '%')
            ->orderBy('nomor_surat_jalan', 'desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastPembelian = $query->first();

        if (!$lastPembelian || !$lastPembelian->nomor_surat_jalan) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastPembelian->nomor_surat_jalan, -4);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function ubahKosongMenjadiNull(?string $value): ?string
    {
        $value = trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
