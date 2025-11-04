<?php

namespace App\Http\Controllers;

use App\Models\BaseOrgStructure;

class BaseOrgStructureController extends Controller
{
    public function index()
    {
        try {
            $data = BaseOrgStructure::all();
            return response()->json([
                'status' => 'success',
                'count' => count($data),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}