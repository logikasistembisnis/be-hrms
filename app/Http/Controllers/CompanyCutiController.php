<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyCuti;
use Carbon\Carbon;

class CompanyCutiController extends Controller
{
    /**
     * GET /companycuti
     * Menampilkan semua perusahaan dengan jenis cuti
    */
    public function index() : JsonResponse
    {
        try {
        // Ambil semua data perusahaan beserta relasi 
        $companycuti = CompanyCuti::with(['company', 'daftarcuti'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companycuti->count(),
            'data' => $companycuti
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
     * PUT /companycuti
     * Insert atau update data perusahaan dengan jenis cuti
    */
    public function upsertCompanyCuti(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companycutiData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companycutiData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companycutiData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'companycutiid'   => 'nullable|integer|exists:companycuti,companycutiid',
                'companyid'   => 'required|integer|exists:company,companyid',
                'daftarcutiid' => 'required|integer|min:0',
                'deskripsi' => 'required|string|max:255',
                'jumlahhari' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => $validator->errors()->all()
                ];
                continue;
            }

            $validated = $validator->validated();

            // Validasi jika daftarcutiid > 0, pastikan ada di tabel daftarcuti
            if ($validated['daftarcutiid'] > 0 && !\DB::table('daftarcuti')->where('daftarcutiid', $validated['daftarcutiid'])->exists()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => ["The selected daftarcutiid ({$validated['daftarcutiid']}) is invalid."]
                ];
                continue;
            }
            
            // Jika ada id → update data
            if (isset($validated['companycutiid'])) {
                $companycuti = CompanyCuti::find($validated['companycutiid']);
                if ($companycuti) {
                    $companycuti->update([
                        'companyid'  => $validated['companyid'],
                        'daftarcutiid'  => $validated['daftarcutiid'],
                        'deskripsi'       => $validated['deskripsi'],
                        'jumlahhari'       => $validated['jumlahhari'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companycuti
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "CompanyCuti ID {$validated['companycutiid']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyCuti = CompanyCuti::create([
                    'companyid'  => $validated['companyid'],
                    'daftarcutiid'  => $validated['daftarcutiid'],
                    'deskripsi'       => $validated['deskripsi'],
                    'jumlahhari'       => $validated['jumlahhari'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyCuti
                ];
            }
        }

        // Kembalikan hasil akhir semua proses (update/insert)
        return response()->json([
            'status' => 'success',
            'count' => count($results),
            'results' => $results
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
}