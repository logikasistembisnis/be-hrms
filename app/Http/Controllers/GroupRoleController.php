<?php

namespace App\Http\Controllers;

use App\Models\GroupRole;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class GroupRoleController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $data = GroupRole::all();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diambil',
                'count' => $data->count(),
                'data' => $data
            ], 200);
            
        } catch (QueryException $e) {
            // Tangani kesalahan dari database
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database',
                'count' => 0,
                'data' => [],
            ], 500);

        } catch (\Exception $e) {
            // Tangani kesalahan umum lainnya
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga',
                'count' => 0,
                'data' => [],
            ], 500);
        }
    }
}