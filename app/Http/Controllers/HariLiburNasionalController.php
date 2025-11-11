<?php

namespace App\Http\Controllers;

use App\Models\HariLiburNasional;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class HariLiburNasionalController extends Controller
{
    // GET /hariliburnasional
    public function index(): JsonResponse
    {
        try {
            $data = HariLiburNasional::all();

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

    public function syncHariLiburData(?int $year = null): array
    {
        try {
            $year = $year ?? date('Y');

            $today = date('Y-m-d');
            $currentYear = date('Y');

            if ($year === null && $today >= "{$currentYear}-12-28") {
                $year = date('Y', strtotime('+1 year'));
            }

            $response = Http::get("https://dayoffapi.vercel.app/api?year={$year}");
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API publik',
                    'status_code' => 500
                ];
            }

            $holidays = $response->json();
            $merged = [];

            foreach ($holidays as $item) {
                $nama = $item['keterangan'];
                $tanggal = $item['tanggal'];
                $isCuti = $item['is_cuti'];

                $tema = preg_replace('/^Cuti Bersama\s+/i', '', $nama);

                if (!isset($merged[$tema])) {
                    $merged[$tema] = [
                        'namatanggal' => "Libur {$tema}",
                        'startdate' => $tanggal,
                        'enddate' => $tanggal,
                        'active' => true,
                    ];
                } else {
                    if ($tanggal > $merged[$tema]['enddate']) {
                        $merged[$tema]['enddate'] = $tanggal;
                    }
                    if ($tanggal < $merged[$tema]['startdate']) {
                        $merged[$tema]['startdate'] = $tanggal;
                    }
                }
            }

            DB::beginTransaction();
            DB::table('hariliburnasional')->truncate();

            foreach ($merged as $item) {
                $endDate = ($item['startdate'] === $item['enddate']) ? null : $item['enddate'];
                DB::insert("
                    INSERT INTO hariliburnasional (startdate, enddate, namatanggal, active, createdby)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $item['startdate'],
                    $endDate,
                    $item['namatanggal'],
                    $item['active'],
                    'system'
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Data hari libur tahun {$year} berhasil disinkronisasi",
                'count' => count($merged),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    /**
     * Endpoint HTTP untuk trigger sinkronisasi via Postman / API
     */
    public function fetchHariLibur(Request $request): JsonResponse
    {
        $year = $request->query('year');
        $result = $this->syncHariLiburData($year);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'count' => $result['count'] ?? 0,
        ]);
    }
}