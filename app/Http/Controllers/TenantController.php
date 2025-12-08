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
     * PUT /tenant
     * Insert atau Update data tenant yang sudah ada
     */
    public function upsert(Request $request) : JsonResponse
    {
        try {
            $tenantData = $request->json()->all();

            if (!is_array($tenantData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request body harus berupa array data perusahaan'
                ], 400);
            }

            $results = [];

            foreach ($tenantData as $data) {
                $validator = validator($data, [
                    'tenantid'          => 'nullable|integer|exists:tenant,tenantid',
                    'name'              => 'nullable|string|max:255',
                    'description'       => 'nullable|string|max:255',
                    'holdingflag'       => 'nullable|boolean',
                    'holdingcompanyid'  => 'nullable|integer|exists:company,companyid',
                    'active'            => 'nullable|boolean',
                ]);

                if ($validator->fails()) {
                    $results[] = [
                        'status' => 'failed',
                        'message' => $validator->errors()->all()
                    ];
                    continue;
                }

                $validated = $validator->validated();

                $payload = [
                    'name'              => $validated['name'] ?? null,
                    'description'       => $validated['description'] ?? null,
                    'holdingflag'       => $validated['holdingflag'] ?? null, 
                    'holdingcompanyid'  => $validated['holdingcompanyid'] ?? null,
                    'active'            => $validated['active'] ?? true, // Default true
                ];
                
                if (isset($validated['tenantid'])) {
                    // --- UPDATE ---
                    $tenant = Tenant::find($validated['tenantid']);
                    
                    if ($tenant) {
                        $payload['updatedon'] = Carbon::now();
                        $payload['updatedby'] = $data['updatedby'] ?? null;

                        $tenant->update($payload);

                        $results[] = [
                            'status' => 'updated',
                            'data' => $tenant
                        ];
                    } else {
                        $results[] = [
                            'status' => 'failed',
                            'message' => "Tenant ID {$validated['tenantid']} not found"
                        ];
                    }
                } else {
                    // --- INSERT (CREATE) ---
                    $payload['createdon'] = Carbon::now();
                    $payload['createdby'] = $data['createdby'] ?? null;

                    $newTenant = Tenant::create($payload);

                    $results[] = [
                        'status' => 'created',
                        'data' => $newTenant
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'count' => count($results),
                'results' => $results
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database: ' . $e->getMessage(),
                'count' => 0,
                'data' => [],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga: ' . $e->getMessage(),
                'count' => 0,
                'data' => [],
            ], 500);
        }
    }

    /**
     * DELETE /tenant/{id}
     * Hapus data tenant
     */
    public function destroy($id) : JsonResponse
    {
        try {
            $break = Tenant::find($id);
            if (!$break) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Tenant ID {$id} not found"
                ], 404);
            }

            $break->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Tenant berhasil dihapus',
                'data' => ['id' => (int) $id]
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga',
            ], 500);
        }
    }
}
