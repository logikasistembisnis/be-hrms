<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TenantController extends Controller
{
    /**
     * GET /tenant
     * Menampilkan semua tenant beserta relasinya
     */
    public function index()
    {
        $tenants = Tenant::with(['holdingCompany'])->get();
        return response()->json(['status' => 'success', 'data' => $tenants]);
    }

    /**
     * GET /tenant/{id}
     * Menampilkan detail satu tenant
     */
    public function show($id)
    {
        $tenant = Tenant::with(['holdingCompany'])->find($id);

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $tenant]);
    }

    /**
     * PUT /tenant/{id}
     * Update data tenant yang sudah ada
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);
        }

        // Validasi input
        $this->validate($request, [
            'holdingflag' => 'nullable|boolean',
            'holdingcompanyid' => 'nullable|integer|exists:company,companyid',
            'updatedby' => 'nullable|string|max:225'
        ]);

        // Update field yang diizinkan
        $tenant->holdingflag = $request->input('holdingflag', $tenant->holdingflag);
        $tenant->holdingcompanyid = $request->input('holdingcompanyid', $tenant->holdingcompanyid);
        $tenant->updatedby = $request->input('updatedby');
        $tenant->updatedon = Carbon::now();

        $tenant->save();

        return response()->json(['status' => 'success', 'message' => 'Tenant updated successfully', 'data' => $tenant]);
    }
}
