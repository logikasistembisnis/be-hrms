<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // Pastikan ini di-import
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompLiburNasional;
use Carbon\Carbon;

class CompLiburNasionalController extends Controller
{
    /**
     * GET /CompLiburNasional
     */
    public function index(): JsonResponse
    {
        try {
            $compliburnasional = CompLiburNasional::with(['company', 'hariliburnasional'])
                ->get()
                ->map(function ($item) {
                    $item->dokumen_url = $item->dokumenfilename
                        ? url("/storage/compliburnasional/" . $item->dokumenfilename) 
                        : null;
                    return $item;
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diambil',
                'count' => $compliburnasional->count(),
                'data' => $compliburnasional
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
     * POST /CompLiburNasional
     */
    public function upsertCompLiburNasional(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'compliburnasionalid' => 'nullable|integer|exists:compliburnasional,compliburnasionalid',
                'companyid' => 'required|integer|exists:company,companyid',
                'hariliburnasid' => 'required|integer|min:0',
                'startdate' => 'required|date_format:Y-m-d|before_or_equal:enddate',
                'enddate' => 'nullable|date_format:Y-m-d|after_or_equal:startdate',
                'namatanggal' => 'required|string|max:255',
                'potongcutitahunan' => 'required|boolean',
                'dokumenfilename' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // max 5MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            $uploadedFile = $request->file('dokumenfilename');
            $fileNameToStore = null; // Default

            // Jika ada ID (update) → ambil nama file lama
            if (!empty($validated['compliburnasionalid'])) {
                $item = CompLiburNasional::find($validated['compliburnasionalid']);
                if (!$item) {
                     return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
                }
                // Nama file default adalah nama file yg sudah ada
                $fileNameToStore = $item->dokumenfilename;
            }

            // Jika upload dokumen baru
            if ($uploadedFile) {
                $fileNameToStore = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->storeAs('compliburnasional', $fileNameToStore);

                // Jika ini adalah update DAN ada file lama, hapus file lama
                if (isset($item) && $item->dokumenfilename) {
                    if (Storage::exists('compliburnasional/' . $item->dokumenfilename)) {
                        Storage::delete('compliburnasional/' . $item->dokumenfilename);
                    }
                }
            }

            $compLibur = CompLiburNasional::updateOrCreate(
                [
                    // Cari berdasarkan ID ini
                    'compliburnasionalid' => $validated['compliburnasionalid'] ?? null
                ],
                [
                    // Update atau Buat dengan data ini
                    'companyid' => $validated['companyid'],
                    'hariliburnasid' => $validated['hariliburnasid'],
                    'startdate' => $validated['startdate'],
                    'enddate' => $validated['enddate'] ?? null,
                    'namatanggal' => $validated['namatanggal'],
                    'potongcutitahunan' => $validated['potongcutitahunan'],
                    'dokumenfilename' => $fileNameToStore, // Nama file baru (atau lama jika tidak diubah)
                    'updatedon' => Carbon::now(),
                    'updatedby' => $data['updatedby'] ?? null,
                    // 'createdon' dan 'createdby' akan diisi otomatis jika ini 'create'
                    'createdon' => Carbon::now(), 
                    'createdby' => $data['createdby'] ?? null,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'data' => $compLibur,
            ], 200); // 200 OK (untuk create atau update)

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /CompLiburNasional/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $item = CompLiburNasional::find($id);
            if (!$item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }
            
            if ($item->dokumenfilename && Storage::exists('public/compliburnasional/' . $item->dokumenfilename)) {
                Storage::delete('public/compliburnasional/' . $item->dokumenfilename);
            }

            $item->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data dan dokumen berhasil dihapus'
            ], 200);
        } catch (QueryException $e) {
             return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada database'], 500);
        } catch (\Exception $e) {
             return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan tak terduga'], 500);
        }
    }
}