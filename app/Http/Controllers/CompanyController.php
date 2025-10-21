<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Country;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('country')->get();

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
        ]);

        $company = Company::create([
            'name' => $request->name,
            'brandname' => $request->brandname,
            'entitytype' => $request->entitytype,
            'noindukberusaha' => $request->noindukberusaha,
            'npwp' => $request->npwp,
            'address' => $request->address,
            'telpno' => $request->telpno,
            'companyemail' => $request->companyemail,
            'logo' => $request->logo,
            'holdingflag' => $request->holdingflag ?? false,
            'desainperusahaan' => $request->desainperusahaan,
            'createdby' => $request->createdby ?? 'system',
            'createdon' => \Carbon\Carbon::now(),
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

        $company->update(array_merge($request->all(), [
            'updatedby' => $request->updatedby ?? 'system',
            'updatedon' => \Carbon\Carbon::now(),
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

        $company->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Company deleted successfully'
        ]);
    }
}