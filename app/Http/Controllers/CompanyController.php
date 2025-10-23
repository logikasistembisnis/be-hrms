<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\Country;
use Carbon\Carbon;

class CompanyController extends Controller
{
    // GET /company
    public function index()
    {
        $companies = Company::with('country')->get()->map(function ($c) {
            $c->logo_url = $c->logo 
                ? url("/storage/logos/" . $c->logo)
                : null;
            return $c;
        });

        return response()->json([
            'status' => 'success',
            'count' => count($companies),
            'data' => $companies
        ]);
    }

    // PUT /company
    public function upsert(Request $request)
    {
        $companiesData = $request->all();

        // Pastikan data berupa array
        if (!is_array($companiesData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request body harus berupa array data perusahaan'
            ], 400);
        }

        $results = [];

        foreach ($companiesData as $data) {
            // Validasi tiap item
            $validated = validator($data, [
                'companyid' => 'nullable|integer|exists:company,companyid',
                'name' => 'required|string|max:255',
                'countryid' => 'integer|exists:country,countryid',
                'companydesignid' => 'integer|exists:companydesign,companydesignid',
                'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            ])->validate();

            $logoName = null;

            // Tangani upload logo (jika dikirim via multipart)
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['logo'];
                $logoName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/logos', $logoName);
            }

            // Jika ada ID → Update
            if (isset($data['companyid'])) {
                $company = Company::find($data['companyid']);
                if (!$company) {
                    $results[] = [
                        'status' => 'failed',
                        'message' => "Company with ID {$data['companyid']} not found"
                    ];
                    continue;
                }

                // Update hanya field yang dikirim (dan tidak null)
                $updateData = collect($data)->only([
                    'name',
                    'brandname',
                    'entitytype',
                    'noindukberusaha',
                    'npwp',
                    'address',
                    'telpno',
                    'companyemail',
                    'holdingflag',
                    'countryid',
                    'companydesignid',
                ])->filter(fn($v) => !is_null($v))->toArray();

                if ($logoName) {
                    // Hapus logo lama
                    if ($company->logo && Storage::exists('public/logos/' . $company->logo)) {
                        Storage::delete('public/logos/' . $company->logo);
                    }
                    $updateData['logo'] = $logoName;
                }

                $updateData['updatedby'] = $data['updatedby'] ?? null;
                $updateData['updatedon'] = Carbon::now();

                $company->update($updateData);
                $results[] = [
                    'status' => 'updated',
                    'data' => $company->fresh()
                ];
            } 
            // Jika tidak ada ID → Insert
            else {
                $newCompany = Company::create([
                    'name' => $data['name'],
                    'brandname' => $data['brandname'] ?? null,
                    'entitytype' => $data['entitytype'] ?? null,
                    'noindukberusaha' => $data['noindukberusaha'] ?? null,
                    'npwp' => $data['npwp'] ?? null,
                    'address' => $data['address'] ?? null,
                    'telpno' => $data['telpno'] ?? null,
                    'companyemail' => $data['companyemail'] ?? null,
                    'logo' => $logoName,
                    'holdingflag' => $data['holdingflag'] ?? false,
                    'createdby' => $data['createdby'] ?? null,
                    'createdon' => Carbon::now(),
                    'countryid' => $data['countryid'] ?? null,
                    'companydesignid' => $data['companydesignid'] ?? null,
                ]);

                $results[] = [
                    'status' => 'created',
                    'data' => $newCompany
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'count' => count($results),
            'results' => $results
        ]);
    }

    // DELETE /company/{id}
    public function destroy($id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Hapus logo jika ada
        if ($company->logo && Storage::exists('public/logos/' . $company->logo)) {
            Storage::delete('public/logos/' . $company->logo);
        }

        $company->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Company deleted successfully'
        ]);
    }
}