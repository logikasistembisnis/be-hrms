<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyBaseOrgStruc;
use Carbon\Carbon;

class CompanyBaseOrgStrucController extends Controller
{
    /**
     * GET /companybaseorgstruc
     * Menampilkan semua perusahaan dan base organisasi
    */
    public function index()
    {
        // Ambil semua data perusahaan beserta relasi 
        $companybaseorgstruc = CompanyBaseOrgStruc::with(['company', 'baseorgstructure'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'count' => $companybaseorgstruc->count(),
            'data' => $companybaseorgstruc
        ]);
    }

    /**
     * PUT /companybaseorgstruc
     * Insert atau update data perusahaan dengan base organisasi
    */
    public function upsertCompanyBaseOrgStruc(Request $request)
    {
        // Ambil semua data dari body request
        $companybaseorgstrucData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companybaseorgstrucData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companybaseorgstrucData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'id'   => 'nullable|integer|exists:companybaseorgstruc,id',
                'companyid'   => 'required|integer|exists:company,companyid',
                'baseorgstructureid'   => 'required|integer|exists:baseorgstructure,baseorgstructureid',
                'selected' => 'required|boolean',
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
                $companybaseorgstruc = CompanyBaseOrgStruc::find($validated['id']);
                if ($companybaseorgstruc) {
                    $companybaseorgstruc->update([
                        'companyid'  => $validated['companyid'],
                        'baseorgstructureid'  => $validated['baseorgstructureid'],
                        'selected'       => $validated['selected'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companybaseorgstruc
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "CompanyBaseOrgStruc ID {$validated['id']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyBaseOrgStruc = CompanyBaseOrgStruc::create([
                    'companyid'  => $validated['companyid'],
                    'baseorgstructureid'  => $validated['baseorgstructureid'],
                    'selected'       => $validated['selected'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyBaseOrgStruc
                ];
            }
        }

        // Kembalikan hasil akhir semua proses (update/insert)
        return response()->json([
            'status' => 'success',
            'count' => count($results),
            'results' => $results
        ]);
    }
}