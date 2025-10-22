<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\Country;
use Carbon\Carbon;

class CompanyController extends Controller
{
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'countryid' => 'required|integer|exists:country,countryid',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $logoName = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $logoName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/logos', $logoName);
        }

        $company = Company::create([
            'name' => $request->name,
            'brandname' => $request->brandname,
            'entitytype' => $request->entitytype,
            'noindukberusaha' => $request->noindukberusaha,
            'npwp' => $request->npwp,
            'address' => $request->address,
            'telpno' => $request->telpno,
            'companyemail' => $request->companyemail,
            'logo' => $logoName,
            'holdingflag' => $request->holdingflag ?? false,
            'desainperusahaan' => $request->desainperusahaan,
            'createdby' => $request->createdby,
            'createdon' => Carbon::now(),
            'countryid' => $request->countryid,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    // PUT /company/{id}
    public function update(Request $request, $id)
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Daftar field yang boleh diupdate
        $allowedFields = [
            'name',
            'brandname',
            'entitytype',
            'noindukberusaha',
            'npwp',
            'address',
            'telpno',
            'companyemail',
            'holdingflag',
            'desainperusahaan',
            'countryid',
        ];

        // Ambil field yang dikirim dan tidak kosong/null
        $data = [];
        foreach ($allowedFields as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->input($field);
            }
        }

        // Tangani upload logo (opsional)
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Hapus logo lama jika ada
            if ($company->logo && Storage::exists('public/logos/' . $company->logo)) {
                Storage::delete('public/logos/' . $company->logo);
            }

            // Simpan logo baru
            $logoName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/logos', $logoName);
            $data['logo'] = $logoName;
        }

        // Tambahkan metadata update
        $data['updatedby'] = $request->updateby;
        $data['updatedon'] = Carbon::now();

        // Update data (hanya field yang dikirim)
        $isUpdated = $company->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Company updated successfully',
            'data' => $company->fresh()
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