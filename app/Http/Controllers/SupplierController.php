<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $suppliers = Supplier::query()
            ->when($search, function ($query, $search) {
                $query->where('nama_supplier', 'like', "%{$search}%")
                    ->orWhere('kode_supplier', 'like', "%{$search}%")
                    ->orWhere('nomor_telepon', 'like', "%{$search}%")
                    ->orWhere('npwp', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create()
    {
        $kodeSupplier = $this->generateKodeSupplier();

        return view('suppliers.create', compact('kodeSupplier'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_supplier' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $this->tambahkanValidasiDuplikatSupplier($validator, $request);

        $validator->validate();

        Supplier::create([
            'kode_supplier' => $this->generateKodeSupplier(),
            'nama_supplier' => trim($request->nama_supplier),
            'nomor_telepon' => $this->ubahKosongMenjadiNull($request->nomor_telepon),
            'npwp' => $this->ubahKosongMenjadiNull($request->npwp),
            'alamat' => $this->ubahKosongMenjadiNull($request->alamat),
            'catatan' => $this->ubahKosongMenjadiNull($request->catatan),
            'status_aktif' => true,
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Data supplier berhasil ditambahkan.');
    }

    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_supplier' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data supplier tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingSupplier = $this->cariSupplierDuplikat($request);

        if ($existingSupplier) {
            if (!$existingSupplier->status_aktif) {
                $existingSupplier->update([
                    'status_aktif' => true,
                ]);
            }

            return response()->json([
                'status' => 'exists',
                'message' => 'Supplier sudah tersedia dan langsung dipilih.',
                'supplier' => [
                    'id_supplier' => $existingSupplier->id_supplier,
                    'kode_supplier' => $existingSupplier->kode_supplier,
                    'nama_supplier' => $existingSupplier->nama_supplier,
                    'nomor_telepon' => $existingSupplier->nomor_telepon,
                    'npwp' => $existingSupplier->npwp,
                    'alamat' => $existingSupplier->alamat,
                ],
            ]);
        }

        $supplier = Supplier::create([
            'kode_supplier' => $this->generateKodeSupplier(),
            'nama_supplier' => trim($request->nama_supplier),
            'nomor_telepon' => $this->ubahKosongMenjadiNull($request->nomor_telepon),
            'npwp' => $this->ubahKosongMenjadiNull($request->npwp),
            'alamat' => $this->ubahKosongMenjadiNull($request->alamat),
            'catatan' => $this->ubahKosongMenjadiNull($request->catatan),
            'status_aktif' => true,
        ]);

        return response()->json([
            'status' => 'created',
            'message' => 'Supplier baru berhasil ditambahkan dan langsung dipilih.',
            'supplier' => [
                'id_supplier' => $supplier->id_supplier,
                'kode_supplier' => $supplier->kode_supplier,
                'nama_supplier' => $supplier->nama_supplier,
                'nomor_telepon' => $supplier->nomor_telepon,
                'npwp' => $supplier->npwp,
                'alamat' => $supplier->alamat,
            ],
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'nama_supplier' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string|max:30',
            'npwp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'catatan' => 'nullable|string',
            'status_aktif' => 'required|boolean',
        ]);

        $this->tambahkanValidasiDuplikatSupplier($validator, $request, $supplier->id_supplier);

        $validator->validate();

        $supplier->update([
            'nama_supplier' => trim($request->nama_supplier),
            'nomor_telepon' => $this->ubahKosongMenjadiNull($request->nomor_telepon),
            'npwp' => $this->ubahKosongMenjadiNull($request->npwp),
            'alamat' => $this->ubahKosongMenjadiNull($request->alamat),
            'catatan' => $this->ubahKosongMenjadiNull($request->catatan),
            'status_aktif' => $request->status_aktif,
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Data supplier berhasil diperbarui.');
    }

    public function nonaktifkan(Supplier $supplier)
    {
        $supplier->update([
            'status_aktif' => false,
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier berhasil dinonaktifkan.');
    }

    private function tambahkanValidasiDuplikatSupplier($validator, Request $request, ?int $ignoreId = null): void
    {
        $validator->after(function ($validator) use ($request, $ignoreId) {
            $namaSupplier = trim($request->nama_supplier ?? '');
            $nomorTelepon = trim($request->nomor_telepon ?? '');
            $npwp = trim($request->npwp ?? '');
            $alamat = trim($request->alamat ?? '');

            if ($namaSupplier !== '' && $this->namaSupplierSudahAda($namaSupplier, $ignoreId)) {
                $validator->errors()->add('nama_supplier', 'Nama perusahaan supplier sudah digunakan oleh supplier lain.');
            }

            if ($nomorTelepon !== '' && $this->nomorTeleponSudahAda($nomorTelepon, $ignoreId)) {
                $validator->errors()->add('nomor_telepon', 'Nomor telepon sudah digunakan oleh supplier lain.');
            }

            if ($npwp !== '' && $this->npwpSudahAda($npwp, $ignoreId)) {
                $validator->errors()->add('npwp', 'NPWP sudah digunakan oleh supplier lain.');
            }

            if ($alamat !== '' && $this->alamatSudahAda($alamat, $ignoreId)) {
                $validator->errors()->add('alamat', 'Alamat sudah digunakan oleh supplier lain.');
            }
        });
    }

    private function cariSupplierDuplikat(Request $request): ?Supplier
    {
        $namaSupplier = trim($request->nama_supplier ?? '');
        $nomorTeleponNormal = $this->normalisasiNomorTelepon($request->nomor_telepon);
        $npwpNormal = $this->normalisasiNpwp($request->npwp);
        $alamatNormal = $this->normalisasiTeks($request->alamat);

        return Supplier::query()
            ->get()
            ->first(function ($supplier) use ($namaSupplier, $nomorTeleponNormal, $npwpNormal, $alamatNormal) {
                $namaSama = $namaSupplier !== ''
                    && $this->normalisasiTeks($supplier->nama_supplier) === $this->normalisasiTeks($namaSupplier);

                $nomorSama = $nomorTeleponNormal !== ''
                    && $this->normalisasiNomorTelepon($supplier->nomor_telepon) === $nomorTeleponNormal;

                $npwpSama = $npwpNormal !== ''
                    && $this->normalisasiNpwp($supplier->npwp) === $npwpNormal;

                $alamatSama = $alamatNormal !== ''
                    && $this->normalisasiTeks($supplier->alamat) === $alamatNormal;

                return $namaSama || $nomorSama || $npwpSama || $alamatSama;
            });
    }

    private function namaSupplierSudahAda(string $namaSupplier, ?int $ignoreId = null): bool
    {
        return Supplier::query()
            ->when($ignoreId, function ($query, $ignoreId) {
                $query->where('id_supplier', '!=', $ignoreId);
            })
            ->whereRaw('LOWER(TRIM(nama_supplier)) = ?', [strtolower(trim($namaSupplier))])
            ->exists();
    }

    private function nomorTeleponSudahAda(string $nomorTelepon, ?int $ignoreId = null): bool
    {
        $nomorTeleponNormal = $this->normalisasiNomorTelepon($nomorTelepon);

        if ($nomorTeleponNormal === '') {
            return false;
        }

        return Supplier::query()
            ->when($ignoreId, function ($query, $ignoreId) {
                $query->where('id_supplier', '!=', $ignoreId);
            })
            ->whereNotNull('nomor_telepon')
            ->get()
            ->contains(function ($supplier) use ($nomorTeleponNormal) {
                return $this->normalisasiNomorTelepon($supplier->nomor_telepon) === $nomorTeleponNormal;
            });
    }

    private function npwpSudahAda(string $npwp, ?int $ignoreId = null): bool
    {
        $npwpNormal = $this->normalisasiNpwp($npwp);

        if ($npwpNormal === '') {
            return false;
        }

        return Supplier::query()
            ->when($ignoreId, function ($query, $ignoreId) {
                $query->where('id_supplier', '!=', $ignoreId);
            })
            ->whereNotNull('npwp')
            ->get()
            ->contains(function ($supplier) use ($npwpNormal) {
                return $this->normalisasiNpwp($supplier->npwp) === $npwpNormal;
            });
    }

    private function alamatSudahAda(string $alamat, ?int $ignoreId = null): bool
    {
        $alamatNormal = $this->normalisasiTeks($alamat);

        if ($alamatNormal === '') {
            return false;
        }

        return Supplier::query()
            ->when($ignoreId, function ($query, $ignoreId) {
                $query->where('id_supplier', '!=', $ignoreId);
            })
            ->whereNotNull('alamat')
            ->get()
            ->contains(function ($supplier) use ($alamatNormal) {
                return $this->normalisasiTeks($supplier->alamat) === $alamatNormal;
            });
    }

    private function generateKodeSupplier()
    {
        $lastSupplier = Supplier::orderBy('id_supplier', 'desc')->first();

        if (!$lastSupplier) {
            return 'SUP-0001';
        }

        $lastNumber = (int) substr($lastSupplier->kode_supplier, 4);
        $newNumber = $lastNumber + 1;

        return 'SUP-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function normalisasiNomorTelepon(?string $nomorTelepon): string
    {
        return preg_replace('/[^0-9]/', '', $nomorTelepon ?? '');
    }

    private function normalisasiNpwp(?string $npwp): string
    {
        return preg_replace('/[^0-9]/', '', $npwp ?? '');
    }

    private function normalisasiTeks(?string $teks): string
    {
        $teks = trim($teks ?? '');
        $teks = preg_replace('/\s+/', ' ', $teks);

        return strtolower($teks);
    }

    private function ubahKosongMenjadiNull(?string $value): ?string
    {
        $value = trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
