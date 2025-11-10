<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\Company;
use App\Models\Country;
use Carbon\Carbon;

class CompanyController extends Controller
{
    /**
     * GET /company
     * Menampilkan semua perusahaan beserta country dan URL logo
    */
    public function index(): JsonResponse
    {
        try {
        // Ambil semua data perusahaan beserta relasi 
        $companies = Company::with([
            'country',
            'tenant',
            'companydesign',
            'reporttocompany'
        ])->get()->map(function ($company) {
            // Tambahkan field baru: logo_url agar bisa diakses dari frontend
            $company->logo_url = $company->logo
                ? url("/storage/logos/" . $company->logo)
                : null;
            return $company;
        });

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $companies->count(),
            'data' => $companies
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
     * PUT /company
     * Insert atau update data perusahaan (name, countryid)
    */
    public function upsertCompany(Request $request) : JsonResponse
    {
        try {
        // Ambil semua data dari body request
        $companiesData = $request->json()->all();

        // Pastikan request body berupa array (karena dikirim dalam bentuk [])
        if (!is_array($companiesData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companiesData as $data) {
            // Validasi tiap item perusahaan
            $validator = validator($data, [
                'companyid'   => 'nullable|integer|exists:company,companyid',
                'name'        => 'required|string|max:255',
                'countryid'   => 'required|integer|exists:country,countryid',
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'status' => 'failed',
                    'message' => $validator->errors()->all()
                ];
                continue;
            }

            $validated = $validator->validated();
            // Jika ada companyid → update data
            if (isset($validated['companyid'])) {
                $company = Company::find($validated['companyid']);
                if ($company) {
                    $company->update([
                        'name'       => $validated['name'],
                        'countryid'  => $validated['countryid'],
                        'updatedon'  => Carbon::now(),
                        'updatedby'  => $data['updatedby'] ?? null,
                    ]);

                    $results[] = [
                        'status' => 'updated',
                        'data' => $company
                    ];
                } else {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "Company ID {$validated['companyid']} not found"
                    ];
                }
            } 
            // Jika tidak ada ID → insert baru
            else {
                $newCompany = Company::create([
                    'name'       => $validated['name'],
                    'countryid'  => $validated['countryid'],
                    'createdon'  => Carbon::now(),
                    'createdby'  => $data['createdby'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompany
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

    /**
     * PUT /company/{id}/details
     * Update detail perusahaan (brandname, npwp, logo, dll)
    */
    public function updateDetails(Request $request, $id) : JsonResponse
    {
        try {
        // Cek apakah company dengan ID tsb ada
        $company = Company::find($id);
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found'
            ], 404);
        }

        // Validasi data detail
        $validator = Validator::make($request->all(), [
            'brandname'        => 'nullable|string|max:255',
            'entitytype'       => 'nullable|string|max:100',
            'noindukberusaha'  => 'nullable|string|max:50',
            'npwp'             => 'nullable|string|max:50',
            'address'          => 'nullable|string',
            'telpno'           => 'nullable|string|max:50',
            'companyemail'     => 'nullable|string|max:255',
            'logo'             => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // maksimal 2MB
        ]);

        // Kalau validasi gagal → kembalikan error ke frontend
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ambil data hasil validasi yang sudah bersih
        $validated = $validator->validated();

        // Default logo tetap pakai yang lama
        $logoName = $company->logo;

        // Kalau ada file logo baru dikirim → upload & ganti
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Buat nama file unik (timestamp + nama asli)
            $logoName = time() . '_' . $file->getClientOriginalName();

            // Simpan ke storage/app/public/logos/
            $file->storeAs('public/logos', $logoName);

            // Hapus logo lama (jika ada di storage)
            if ($company->logo && Storage::exists('public/logos/' . $company->logo)) {
                Storage::delete('public/logos/' . $company->logo);
            }
        }

        // Update semua detail perusahaan
        $company->update([
            'brandname'       => $validated['brandname'] ?? $company->brandname,
            'entitytype'      => $validated['entitytype'] ?? $company->entitytype,
            'noindukberusaha' => $validated['noindukberusaha'] ?? $company->noindukberusaha,
            'npwp'            => $validated['npwp'] ?? $company->npwp,
            'address'         => $validated['address'] ?? $company->address,
            'telpno'          => $validated['telpno'] ?? $company->telpno,
            'companyemail'    => $validated['companyemail'] ?? $company->companyemail,
            'logo'            => $logoName,
            'updatedon'       => Carbon::now(),
            'updatedby'       => $request->input('updatedby'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail Perusahaan berhasil diperbarui',
            'data' => $company->fresh() // ambil data terbaru setelah update
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
     * PUT /company/companydesign
     * Update companydesignid dan reporttocompanyid untuk beberapa perusahaan sekaligus
     */
    public function updateDesignAndReportTo(Request $request) : JsonResponse
    {
        try {
        $companiesData = $request->all();

        // Pastikan request berupa array
        if (!is_array($companiesData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companiesData as $data) {
            // Validasi setiap item perusahaan
            $validator = Validator::make($data, [
                'companyid'        => 'required|integer|exists:company,companyid',
                'companydesignid'  => 'nullable|integer|exists:companydesign,companydesignid',
                'reporttocompanyid' => 'nullable|integer|exists:company,companyid|different:companyid',
            ]);

            if ($validator->fails()) {
                $results[] = [
                    'status'  => 'failed',
                    'message' => $validator->errors()->all()
                ];
                continue;
            }

            $validated = $validator->validated();
            $company = Company::find($validated['companyid']);

            // Update company design & reporttocompany
            $company->update([
                'companydesignid'  => $validated['companydesignid'] ?? $company->companydesignid,
                'reporttocompanyid' => $validated['reporttocompanyid'] ?? $company->reporttocompanyid,
                'updatedon'        => Carbon::now(),
                'updatedby'        => $validated['updatedby'] ?? null,
            ]);

            $results[] = [
                'status' => 'updated',
                'data'   => $company->fresh()
            ];
        }

        return response()->json([
            'status'  => 'success',
            'count'   => count($results),
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

    /**
     * DELETE /company/{id}
     * Menghapus perusahaan + file logo-nya
     */
    public function destroyCompany($id) : JsonResponse
    {
        try {
        // Cek apakah perusahaan ada
        $company = Company::find($id);
        if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], 404);
            }

        // Hapus file logo dari storage jika ada
        if ($company->logo && Storage::exists('public/logos/' . $company->logo)) {
            Storage::delete('public/logos/' . $company->logo);
        }

        // Hapus data perusahaan dari database
        $company->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Company deleted successfully'
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