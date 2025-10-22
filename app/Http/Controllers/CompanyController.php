<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminat\Support\Facades\Storage;
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

        $logoName = $company->logo;
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($logoName && Storage::exists('public/logos/' . $logoName)) {
                Storage::delete('public/logos/' . $logoName);
            }
            $file = $request->file('logo');
            $logoName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/logos', $logoName);
        }

        $company->update(array_merge($request->except('logo'), [
            'logo' => $logoName,
            'updatedby' => $request->updatedby ?? 'system',
            'updatedon' => Carbon::now(),
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Company updated successfully',
            'data' => $company
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