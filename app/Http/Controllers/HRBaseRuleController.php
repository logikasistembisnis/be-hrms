<?php

namespace App\Http\Controllers;

use App\Models\HRBaseRule;

class HRBaseRuleController extends Controller
{
    public function index()
    {
        try {
            $data = HRBaseRule::all();
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