<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyJobRepository;
use Carbon\Carbon;

class CompanyJobRepositoryController extends Controller
{
    /**
     * GET /companyjobrepository
     * Menampilkan semua perusahaan dan base organisasi
    */
    public function index() : JsonResponse
    {
        try {
        // Ambil semua data perusahaan beserta relasi 
        $companyjobrepository = CompanyJobRepository::with([
            'company', 
            'companydirectorate', 
            'companydivision', 
            'companydepartment',  
            'companyunitkerja',
            'companyjobfamily',
            'companysubfamily',
            'reportto'
        ])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companyjobrepository->count(),
            'data' => $companyjobrepository
        ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database',
                'count' => 0,
                'data' => [],
        ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga',
                'count' => 0,
                'data' => [],
            ], 500);
        }
    }

    /**
     * PUT /companyjobrepository
     * Insert atau update data
    */
    public function upsert(Request $request): JsonResponse
    {
        // Pastikan request berupa array
        $jobrepositoryData = $request->json()->all();
        if (!is_array($jobrepositoryData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data'
            ], 400);
        }

        DB::beginTransaction(); // Mulai transaksi database

        try {
            // --- Helper lokal untuk generate kode otomatis (diadaptasi dari referensi) ---
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

            $results = [];

            foreach ($jobrepositoryData as $data) {
                // Validasi tiap item
                $validator = Validator::make($data, [
                    'companyjobrepositoryid' => 'nullable|integer|exists:companyjobrepository,companyjobrepositoryid',
                    'companyid'              => 'required|integer|exists:company,companyid',
                    'companydirectorateid'   => 'required|integer|exists:companydirectorate,companydirectorateid',
                    'companydivisionid'      => 'nullable|integer|exists:companydivision,companydivisionid',
                    'companydepartmentid'    => 'nullable|integer|exists:companydepartment,companydepartmentid',
                    'companyunitkerjaid'     => 'nullable|integer|exists:companyunitkerja,companyunitkerjaid',
                    'companyjobfamilyid'     => 'required|integer|exists:companyjobfamily,companyjobfamilyid',
                    'companysubfamilyid'     => 'nullable|integer|exists:companysubfamily,companysubfamilyid',
                    'kodeposisi'             => 'nullable|string|max:255', 
                    'namaposisi'             => 'required|string|max:255',
                    'reporttoid'             => 'nullable|integer|exists:companyjobrepository,companyjobrepositoryid',
                ]);

                if ($validator->fails()) {
                    $results[] = [
                        'status'  => 'failed',
                        'message' => $validator->errors()->all()
                    ];
                    continue; // Lanjut ke data berikutnya
                }

                $validated = $validator->validated();

                // --- KONDISI 1: UPDATE (Jika ID dikirim) ---
                if (isset($validated['companyjobrepositoryid'])) {
                    $jobrepository = CompanyJobRepository::find($validated['companyjobrepositoryid']);
                    
                    if ($jobrepository) {
                        $updateData = [
                            'companyid'            => $validated['companyid'],
                            'companydirectorateid' => $validated['companydirectorateid'],
                            'companydivisionid'    => $validated['companydivisionid'],
                            'companydepartmentid'  => $validated['companydepartmentid'],
                            'companyunitkerjaid'   => $validated['companyunitkerjaid'],
                            'companyjobfamilyid'   => $validated['companyjobfamilyid'],
                            'companysubfamilyid'   => $validated['companysubfamilyid'],
                            'namaposisi'           => $validated['namaposisi'],
                            'reporttoid'           => $validated['reporttoid'],
                            'updatedon'            => Carbon::now(),
                            'updatedby'            => $data['updatedby'] ?? null,
                        ];

                        $jobrepository->update($updateData);

                        $results[] = [
                            'status' => 'updated',
                            'data'   => $jobrepository
                        ];
                    } else {
                        $results[] = [
                            'status'  => 'failed',
                            'message' => "Company Job Repository ID {$validated['companyjobrepositoryid']} not found"
                        ];
                    }
                } 
                // --- KONDISI 2: INSERT BARU (Jika ID tidak ada) ---
                else {
                    $generatedCode = $getNextCode('companyjobrepository', 'kodeposisi', 'JR', 9, $validated['companyid']);

                    $newJobrepository = CompanyJobRepository::create([
                        'companyid'            => $validated['companyid'],
                        'companydirectorateid' => $validated['companydirectorateid'],
                        'companydivisionid'    => $validated['companydivisionid']?? null,
                        'companydepartmentid'  => $validated['companydepartmentid']?? null,
                        'companyunitkerjaid'   => $validated['companyunitkerjaid']?? null,
                        'companyjobfamilyid'   => $validated['companyjobfamilyid'],
                        'companysubfamilyid'   => $validated['companysubfamilyid']?? null,
                        'kodeposisi'           => $generatedCode, // Menggunakan kode otomatis
                        'namaposisi'           => $validated['namaposisi'],
                        'reporttoid'           => $validated['reporttoid']?? null,
                        'createdon'            => Carbon::now(),
                        'createdby'            => $data['createdby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'created',
                        'data'   => $newJobrepository
                    ];
                }
            }

            DB::commit(); // Simpan perubahan ke database

            return response()->json([
                'status'  => 'success',
                'count'   => count($results),
                'results' => $results
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack(); // Batalkan semua perubahan jika error DB
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada database: ' . $e->getMessage(),
                'count'   => 0,
                'data'    => [],
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua perubahan jika error umum
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan tak terduga: ' . $e->getMessage(),
                'count'   => 0,
                'data'    => [],
            ], 500);
        }
    }
}