<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CompanyJobFamily;
use App\Models\CompanySubFamily;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class JobFamilyController extends Controller
{
    /**
     * GET Index: Mengambil Data job family
     * Struktur: Job Family -> Sub Family
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Validasi companyid wajib dikirim (bisa via query param ?companyid=1)
        $this->validate($request, [
            'companyid' => 'required|integer'
        ]);

        $companyId = $request->input('companyid');

        try {
            // 2. Ambil data mulai dari Parent teratas (job family)
            // Gunakan 'with' untuk Eager Loading (mengambil data anak sekaligus)
            $data = CompanyJobFamily::with([
                'subfamily' => function($query) {
                    $query->where('active', true); // Filter anak yang aktif saja
                }
            ])
            ->where('companyid', $companyId)
            ->where('active', true)
            ->get()
            ->map(function ($jobfamily) {
                if (!empty($jobfamily->subfamily)) {
                    foreach ($jobfamily->subfamily as $sub) {
                        if (!empty($sub->dokumenfilename)) {
                            $sub->dokumen_url = url('/storage/unitkerja/' . $sub->dokumenfilename);
                        } else {
                            $sub->dokumen_url = null;
                        }
                    }
                }
                return $jobfamily;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data Job Family berhasil diambil',
                'data' => $data
            ], 200);

        } catch (QueryException $e) {
            // Tangani kesalahan dari database
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database',
                'count' => 0,
                'data' => [],
            ], 500);

        } catch (\Exception $e) {
            // Tangani kesalahan umum lainnya
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga',
                'count' => 0,
                'data' => [],
            ], 500);
        }
    }

    /**
     * POST Insert: Simpan Job Family + Upload File
     */

    public function insert(Request $request): JsonResponse
    {
        $this->validate($request, [
            'companyid'       => 'required|integer',
            'jobfamilyname'   => 'nullable|string',
            'subfamilyname'   => 'nullable|string',
            'dokumenfile'     => 'nullable|file|max:2048'
        ]);       

        $companyId = $request->input('companyid');

        DB::beginTransaction();

        try {
            // --- helper lokal untuk mendapatkan kode berikutnya ---
            $getNextCode = function(string $table, string $column, string $prefix, int $digits, int $companyId) {
                // Ambil kode maksimal untuk companyid yang sama dan prefix tertentu
                $maxCodeRow = DB::table($table)
                    ->select(DB::raw("MAX({$column}) as max_code"))
                    ->where('companyid', $companyId)
                    ->where($column, 'like', $prefix . '%')
                    ->first();

                $maxCode = $maxCodeRow ? $maxCodeRow->max_code : null;

                if (!$maxCode) {
                    $nextNumber = 1;
                } else {
                    // Ambil bagian numeric setelah prefix
                    $numPart = substr($maxCode, strlen($prefix));
                    // pastikan hanya angka
                    $numPart = preg_replace('/[^0-9]/', '', $numPart);
                    $nextNumber = (int)$numPart + 1;
                }

                $padded = str_pad((string)$nextNumber, $digits, '0', STR_PAD_LEFT);
                return $prefix . $padded;
            };

            // --- LEVEL 1 : JOB FAMILY --- //
            if ($request->filled('companyjobfamilyid')) {
                $jobfamily = CompanyJobFamily::where('companyjobfamilyid', $request->companyjobfamilyid)
                    ->where('companyid', $companyId)
                    ->firstOrFail();
            } else {
                // Validasi: Jika buat baru, nama wajib ada
                if (!$request->jobfamilyname) {
                    throw new \Exception("Nama Job Family wajib diisi jika membuat baru.");
                }

                // generate jobfamilycode otomatis, prefix "JF", 9 digit numeric (contoh JF000000001)
                $jobfamilyCode = $getNextCode('companyjobfamily', 'jobfamilycode', 'JF', 9, $companyId);

                $jobfamily = CompanyJobFamily::create([
                    'companyid'       => $companyId,
                    'jobfamilyname'   => $request->jobfamilyname,
                    'jobfamilycode'   => $jobfamilyCode,
                    'active'          => true,
                ]);
            }

            // --- LEVEL 2 : SUB FAMILY --- //
            $subfamily = null;
            if ($jobfamily) {
                // Kalau companysubfamilyid dikirim dan ingin update, kita bisa handle itu.
                if ($request->filled('companysubfamilyid')) {
                    // Update existing subfamily (jika perlu)
                    $subfamily = CompanySubFamily::where('companysubfamilyid', $request->companysubfamilyid)
                        ->where('companyid', $companyId)
                        ->firstOrFail();

                    // handle file jika ada
                    if ($request->hasFile('dokumenfile')) {
                        $file = $request->file('dokumenfile');
                        $cleanName   = str_replace(' ', '_', $file->getClientOriginalName());
                        $docFilename = time() . '_' . $cleanName;
                        $file->storeAs('subfamily', $docFilename, 'public');

                        $subfamily->dokumenname = $request->dokumenname;
                        $subfamily->dokumenfilename = $docFilename;
                    }

                    // update fields lain bila ada
                    if ($request->filled('subfamilyname')) $subfamily->subfamilyname = $request->subfamilyname;
                    if ($request->filled('subfamilycode')) $subfamily->subfamilycode = $request->subfamilycode;
                    $subfamily->active = true;
                    $subfamily->save();
                } else if ($request->filled('subfamilyname')) {
                    // create new subfamily

                    // generate subfamilycode otomatis, prefix "SF", 9 digit numeric
                    $subfamilyCode = $getNextCode('companysubfamily', 'subfamilycode', 'SF', 9, $companyId);

                    $docFilename = null;
                    if ($request->hasFile('dokumenfile')) {
                        $file = $request->file('dokumenfile');
                        $cleanName   = str_replace(' ', '_', $file->getClientOriginalName());
                        $docFilename = time() . '_' . $cleanName;
                        $file->storeAs('subfamily', $docFilename, 'public');
                    }

                    $subfamily = CompanySubFamily::create([
                        'companyid'           => $companyId,
                        'companyjobfamilyid'  => $jobfamily->companyjobfamilyid,
                        'subfamilyname'       => $request->subfamilyname,
                        'subfamilycode'       => $subfamilyCode,
                        'dokumenname'         => $request->dokumenname,
                        'dokumenfilename'     => $docFilename,
                        'active'              => true
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Job Family berhasil disimpan',
                'data'    => [
                    'jobfamily' => $jobfamily,
                    'subfamily' => $subfamily
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Data induk tidak ditemukan. Cek ID yang dikirim.',
                'debug'   => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}