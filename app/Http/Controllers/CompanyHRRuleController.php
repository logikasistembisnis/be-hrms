<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompanyHRRule;
use Carbon\Carbon;

class CompanyHRRuleController extends Controller
{
    /**
     * GET /companyhrrule
     * Menampilkan semua perusahaan dan base organisasi
    */
    public function index() : JsonResponse
    {
        try {
        // Ambil semua data perusahaan beserta relasi 
        $companyhrrule = CompanyHRRule::with(['company', 'hrbaserule'])->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companyhrrule->count(),
            'data' => $companyhrrule
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
     * PUT /companyhrrule
     * Insert atau update data perusahaan dengan base organisasi
    */
    public function upsertCompanyHRRule(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companyhrruleData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companyhrruleData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companyhrruleData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'companyhrruleid'   => 'nullable|integer|exists:companyhrrule,companyhrruleid',
                'companyid'   => 'required|integer|exists:company,companyid',
                'hrbaseruleid' => 'required|integer|min:0',
                'grouprule' => 'required|string|max:255',
                'rulename' => 'required|string|max:255',
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

            // Validasi jika hrbaseruleid > 0, pastikan ada di tabel hrbaserule
            if ($validated['hrbaseruleid'] > 0 && !\DB::table('hrbaserule')->where('hrbaseruleid', $validated['hrbaseruleid'])->exists()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => ["The selected hrbaseruleid ({$validated['hrbaseruleid']}) is invalid."]
                ];
                continue;
            }
            
            // Jika ada id → update data
            if (isset($validated['companyhrruleid'])) {
                $companyhrrule = CompanyHRRule::find($validated['companyhrruleid']);
                if ($companyhrrule) {
                    $companyhrrule->update([
                        'companyid'  => $validated['companyid'],
                        'hrbaseruleid'  => $validated['hrbaseruleid'],
                        'grouprule'       => $validated['grouprule'],
                        'rulename'       => $validated['rulename'],
                        'selected'       => $validated['selected'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $companyhrrule
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "CompanyHRRule ID {$validated['companyhrruleid']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompanyHRRule = CompanyHRRule::create([
                    'companyid'  => $validated['companyid'],
                    'hrbaseruleid'  => $validated['hrbaseruleid'],
                    'grouprule'       => $validated['grouprule'],
                    'rulename'       => $validated['rulename'],
                    'selected'       => $validated['selected'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompanyHRRule
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