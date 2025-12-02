<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyWorkingHours;
use Carbon\Carbon;

class CompanyWorkingHoursController extends Controller
{
    /**
     * GET /companyworkinghours
     * Menampilkan semua perusahaan dan base organisasi
    */
    public function index() : JsonResponse
    {
        try {
        // Ambil semua data 
        $companyworkinghours = CompanyWorkingHours::with(['company'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companyworkinghours->count(),
            'data' => $companyworkinghours
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
     * PUT /companyworkinghours
     * Insert atau update data perusahaan dengan base organisasi
    */
    public function upsertCompanyWorkingHours(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companyworkinghoursData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companyworkinghoursData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companyworkinghoursData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'companyworkinghoursid'   => 'nullable|integer|exists:companyworkinghours,companyworkinghoursid',
                'companyid'   => 'required|integer|exists:company,companyid',
                'tipejadwal'   => 'required|string|max:255',
                'kategori'   => 'required|string|max:255',
                'skema'   => 'required|string|max:255',
                'durasi'   => 'required|integer|min:0',
                'durasiistirahat'   => 'required|integer|min:0',
                'jammasuk' => 'required|date_format:H:i',
                'jamkeluar' => 'required|date_format:H:i',
                'kodeshift'   => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => $validator->errors()->all()
                ];
                continue;
            }

            $validated = $validator->validated();
            // Jika ada id → update data
            if (isset($validated['companyworkinghoursid'])) {
                $companyworkinghours = CompanyWorkingHours::find($validated['companyworkinghoursid']);
                if ($companyworkinghours) {
                    $companyworkinghours->update([
                        'companyid'  => $validated['companyid'],
                        'tipejadwal'  => $validated['tipejadwal'],
                        'kategori'  => $validated['kategori'],
                        'skema'       => $validated['skema'],
                        'durasi'  => $validated['durasi'],
                        'durasiistirahat'  => $validated['durasiistirahat'],
                        'jammasuk'       => $validated['jammasuk'],
                        'jamkeluar'  => $validated['jamkeluar'],
                        'kodeshift'       => $validated['kodeshift'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companyworkinghours
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "CompanyWorkingHours ID {$validated['companyworkinghoursid']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyWorkingHours = CompanyWorkingHours::create([
                    'companyid'  => $validated['companyid'],
                    'tipejadwal'  => $validated['tipejadwal'],
                    'kategori'  => $validated['kategori'],
                    'skema'       => $validated['skema'],
                    'durasi'  => $validated['durasi'],
                    'durasiistirahat'  => $validated['durasiistirahat'],
                    'jammasuk'       => $validated['jammasuk'],
                    'jamkeluar'  => $validated['jamkeluar'],
                    'kodeshift'       => $validated['kodeshift'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyWorkingHours
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