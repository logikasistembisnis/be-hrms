<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyIzin;
use Carbon\Carbon;

class CompanyIzinController extends Controller
{
    /**
     * GET /companyizin
     * Menampilkan semua perusahaan dengan jenis izin
    */
    public function index() : JsonResponse
    {
        try {
        // Ambil semua data perusahaan beserta relasi 
        $companyizin = CompanyIzin::with(['company', 'daftarizin'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companyizin->count(),
            'data' => $companyizin
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
     * PUT /companyizin
     * Insert atau update data perusahaan dengan jenis izin
    */
    public function upsertCompanyIzin(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companyizinData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companyizinData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companyizinData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'companyizinid'   => 'nullable|integer|exists:companyizin,companyizinid',
                'companyid'   => 'required|integer|exists:company,companyid',
                'daftarizinid' => 'required|integer|min:0',
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

            // Validasi jika daftarizinid > 0, pastikan ada di tabel daftarizin
            if ($validated['daftarizinid'] > 0 && !\DB::table('daftarizin')->where('daftarizinid', $validated['daftarizinid'])->exists()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => ["The selected daftarizinid ({$validated['daftarizinid']}) is invalid."]
                ];
                continue;
            }
            
            // Jika ada id → update data
            if (isset($validated['companyizinid'])) {
                $companyizin = CompanyIzin::find($validated['companyizinid']);
                if ($companyizin) {
                    $companyizin->update([
                        'companyid'  => $validated['companyid'],
                        'daftarizinid'  => $validated['daftarizinid'],
                        'deskripsi'       => $validated['deskripsi'],
                        'jumlahhari'       => $validated['jumlahhari'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companyizin
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "Companyizin ID {$validated['companyizinid']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyIzin = CompanyIzin::create([
                    'companyid'  => $validated['companyid'],
                    'daftarizinid'  => $validated['daftarizinid'],
                    'deskripsi'       => $validated['deskripsi'],
                    'jumlahhari'       => $validated['jumlahhari'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyIzin
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