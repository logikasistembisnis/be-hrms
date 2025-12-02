<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyWorkingBreaktime;
use Carbon\Carbon;

class CompanyWorkingBreaktimeController extends Controller
{
    // GET /companyworkingbreaktime
    public function index() : JsonResponse
    {
        try {
        $companyworkingbreaktime = CompanyWorkingBreaktime::with(['companyworkinghours'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companyworkingbreaktime->count(),
            'data' => $companyworkingbreaktime
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
     * PUT /companyworkingbreaktime
     * Insert atau update data 
    */
    public function upsertCompanyWorkingBreaktime(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companyworkingbreaktimeData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companyworkingbreaktimeData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companyworkingbreaktimeData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'id'   => 'nullable|integer|exists:companyworkingbreaktime,id',
                'companyworkinghoursid'   => 'required|integer|exists:companyworkinghours,companyworkinghoursid',
                'starttime' => 'required|date_format:H:i',
                'endtime' => 'required|date_format:H:i',
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
            if (isset($validated['id'])) {
                $companyworkingbreaktime = CompanyWorkingBreaktime::find($validated['id']);
                if ($companyworkingbreaktime) {
                    $companyworkingbreaktime->update([
                        'companyworkinghoursid'  => $validated['companyworkinghoursid'],
                        'starttime'  => $validated['starttime'],
                        'endtime'       => $validated['endtime'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companyworkingbreaktime
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "CompanyWorkingBreaktime ID {$validated['id']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyWorkingBreaktime = CompanyWorkingBreaktime::create([
                    'companyworkinghoursid'  => $validated['companyworkinghoursid'],
                    'starttime'  => $validated['starttime'],
                    'endtime'       => $validated['endtime'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyWorkingBreaktime
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