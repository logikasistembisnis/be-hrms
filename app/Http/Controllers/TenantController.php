<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TenantController extends Controller
{
    /**
     * GET /tenant
     * Menampilkan semua tenant beserta relasinya
     */
    public function index() : JsonResponse
    {
        try {

        $tenants = Tenant::with(['holdingCompany'])->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'count' => $tenants->count(),
            'data' => $tenants
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
     * GET /tenant/{id}
     * Menampilkan detail satu tenant
     */
    public function show($id) : JsonResponse
    {
        try {
        $tenant = Tenant::with(['holdingCompany'])->find($id);

        if (!$tenant) {
            return response()->json(['status' => 'error', 'message' => 'Tenant not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'data' => $tenant
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
     * PUT /tenant/{id}
     * Update data tenant yang sudah ada
     */
    public function update(Request $request, $id) : JsonResponse
    {
        try {
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

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diupdate',
            'data' => $tenant
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
