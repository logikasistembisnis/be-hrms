<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Models\CompLiburNasional;
use Carbon\Carbon;

class CompLiburNasionalController extends Controller
{
    /**
     * GET /CompLiburNasional
     * Menampilkan semua data libur nasional perusahaan beserta relasi
     */
    public function index(): JsonResponse
    {
        try {
            $compliburnasional = CompLiburNasional::with(['company', 'hariliburnasional'])
                ->get()
                ->map(function ($item) {
                    // Tambahkan URL file dokumen agar bisa diakses frontend
                    $item->dokumen_url = $item->dokumenfilename
                        ? url("/compliburnasional/" . $item->dokumenfilename)
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
     * Insert atau update data perusahaan beserta dokumen
     */
    public function upsertCompLiburNasional(Request $request): JsonResponse
    {
        try {
            // Support form-data (bukan JSON)
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
            $fileNameToStore = null;

            // Jika upload dokumen baru
            if ($uploadedFile) {
                $fileNameToStore = time() . '_' . $uploadedFile->getClientOriginalName();
                $tujuanFolder = public_path('compliburnasional');
                $uploadedFile->move($tujuanFolder, $fileNameToStore);
            }

            // Jika ada ID → update
            if (!empty($validated['compliburnasionalid'])) {
                $item = CompLiburNasional::find($validated['compliburnasionalid']);
                if (!$item) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data tidak ditemukan',
                    ], 404);
                }

                // Jika ada file baru → hapus lama
                if ($uploadedFile && $item->dokumenfilename && Storage::exists('compliburnasional/' . $item->dokumenfilename)) {
                    Storage::delete('compliburnasional/' . $item->dokumenfilename);
                }

                $item->update([
                    'companyid' => $validated['companyid'],
                    'hariliburnasid' => $validated['hariliburnasid'],
                    'startdate' => $validated['startdate'],
                    'enddate' => $validated['enddate'] ?? null,
                    'namatanggal' => $validated['namatanggal'],
                    'potongcutitahunan' => $validated['potongcutitahunan'],
                    'dokumenfilename' => $fileNameToStore ?? $item->dokumenfilename,
                    'updatedon' => Carbon::now(),
                    'updatedby' => $data['updatedby'] ?? null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data berhasil diperbarui',
                    'data' => $item->fresh(),
                ], 200);
            }

            // Jika tidak ada ID → insert baru
            $new = CompLiburNasional::create([
                'companyid' => $validated['companyid'],
                'hariliburnasid' => $validated['hariliburnasid'],
                'startdate' => $validated['startdate'],
                'enddate' => $validated['enddate'] ?? null,
                'namatanggal' => $validated['namatanggal'],
                'potongcutitahunan' => $validated['potongcutitahunan'],
                'dokumenfilename' => $fileNameToStore,
                'createdon' => Carbon::now(),
                'createdby' => $data['createdby'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data baru berhasil dibuat',
                'data' => $new,
            ], 201);
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
     * Hapus data dan file dokumennya
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

            // Hapus file dari storage jika ada
            if ($item->dokumenfilename && Storage::exists('compliburnasional/' . $item->dokumenfilename)) {
                Storage::delete('compliburnasional/' . $item->dokumenfilename);
            }

            $item->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data dan dokumen berhasil dihapus'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada database'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan tak terduga'
            ], 500);
        }
    }
}
