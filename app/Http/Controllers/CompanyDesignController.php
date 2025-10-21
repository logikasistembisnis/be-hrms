<?php

namespace App\Http\Controllers;

use App\Models\CompanyDesign;

class CompanyDesignController extends Controller
{
    public function index()
    {
        try {
            $data = CompanyDesign::all();
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