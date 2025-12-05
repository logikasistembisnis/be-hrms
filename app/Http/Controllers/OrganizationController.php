<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CompanyDirectorate;
use App\Models\CompanyDivision;
use App\Models\CompanyDepartment;
use App\Models\CompanyUnitKerja;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    /**
     * GET Index: Mengambil Data Hirarki
     * Struktur: Directorate -> Division -> Department -> Unit Kerja
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Validasi companyid wajib dikirim (bisa via query param ?companyid=1)
        $this->validate($request, [
            'companyid' => 'required|integer'
        ]);

        $companyId = $request->input('companyid');

        try {
            // 2. Ambil data mulai dari Parent teratas (Directorate)
            // Gunakan 'with' untuk Eager Loading (mengambil data anak sekaligus)
            $data = CompanyDirectorate::with([
                'divisions' => function($query) {
                    $query->where('active', true); // Filter anak yang aktif saja
                },
                'divisions.departments' => function($query) {
                    $query->where('active', true);
                },
                'divisions.departments.unitKerjas' => function($query) {
                    $query->where('active', true);
                }
            ])
            ->where('companyid', $companyId)
            ->where('active', true)
            ->get()
            ->map(function ($directorate) {
                if (!empty($directorate->divisions)) {
                    foreach ($directorate->divisions as $div) {
                        if (!empty($div->departments)) {
                            foreach ($div->departments as $dep) {
                                if (!empty($dep->unitKerjas)) {
                                    foreach ($dep->unitKerjas as $unit) {
                                        if (!empty($unit->dokumenfilename)) {
                                            $unit->dokumen_url = url('/storage/unitkerja/' . $unit->dokumenfilename);
                                        } else {
                                            $unit->dokumen_url = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $directorate;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data hirarki berhasil diambil',
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

    /**
     * POST Insert: Simpan Hirarki + Upload File
     */
    public function insertHierarchy(Request $request): JsonResponse
    {
        // 1. Validasi Input Dasar
        $this->validate($request, [
            'companyid'       => 'required|integer',
            'directoratename' => 'nullable|string',
            'divisionname'    => 'nullable|string',
            'departmentname'  => 'nullable|string',
            'unitkerjaname'   => 'nullable|string',
            'dokumenfile'     => 'nullable|file|max:2048'
        ]);

        $companyId = $request->input('companyid');

        DB::beginTransaction();

        try {
            // --- LEVEL 1 : DIREKTORAT ---
            if ($request->filled('companydirectorateid')) {
                $directorate = CompanyDirectorate::where('companydirectorateid', $request->companydirectorateid)
                    ->where('companyid', $companyId)
                    ->firstOrFail();
            } else {
                // Validasi: Jika buat baru, nama wajib ada
                if (!$request->directoratename) {
                    throw new \Exception("Nama Direktorat wajib diisi jika membuat baru.");
                }
                $directorate = CompanyDirectorate::create([
                    'companyid'       => $companyId,
                    'directoratename' => $request->directoratename,
                    'active'          => true
                ]);
            }
            
            // --- LEVEL 2 : DIVISI ---
            $division = null;
            // Cek jika ID dikirim
            if ($request->filled('companydivisionid')) {
                $division = CompanyDivision::where('companydivisionid', $request->companydivisionid)
                    ->where('companyid', $companyId)
                    ->firstOrFail();
            
            // Jika tidak ada ID, tapi ada Nama -> Buat Baru
            } elseif ($request->filled('divisionname')) {
                $division = CompanyDivision::create([
                    'companydirectorateid' => $directorate->companydirectorateid,
                    'companyid'            => $companyId,
                    'divisionname'         => $request->divisionname,
                    'active'               => true
                ]);
            }

            // --- LEVEL 3 : DEPARTMENT ---
            $department = null;
            if ($request->filled('companydepartmentid')) {
                $department = CompanyDepartment::where('companydepartmentid', $request->companydepartmentid)
                    ->where('companyid', $companyId)
                    ->firstOrFail();
            } elseif ($division && $request->filled('departmentname')) {
                $department = CompanyDepartment::create([
                    'companydivisionid' => $division->companydivisionid,
                    'companyid'         => $companyId,
                    'departmentname'    => $request->departmentname,
                    'active'            => true
                ]);
            }

            // --- LEVEL 4 : UNIT KERJA ---
            $unitKerja = null;
            if ($department && $request->filled('unitkerjaname')) {

                $docFilename = null;

                if ($request->hasFile('dokumenfile')) {
                    $file        = $request->file('dokumenfile');
                    $cleanName   = str_replace(' ', '_', $file->getClientOriginalName());
                    $docFilename = time() . '_' . $cleanName;
                    $file->storeAs('unitkerja', $docFilename, 'public'); 
                }

                $unitKerja = CompanyUnitKerja::create([
                    'companydepartmentid' => $department->companydepartmentid,
                    'companyid'           => $companyId,
                    'unitkerjaname'       => $request->unitkerjaname,
                    'dokumenname'         => $request->dokumenname,
                    'dokumenfilename'     => $docFilename,
                    'active'              => true
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Hirarki organisasi berhasil disimpan',
                'data'    => [
                    'directorate' => $directorate,
                    'division'    => $division,
                    'department'  => $department,
                    'unitkerja'   => $unitKerja
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Data induk (Direktorat/Divisi/Dept) tidak ditemukan. Cek ID yang dikirim.',
                'debug'   => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(), // Tampilkan pesan error agar mudah debug
            ], 500);
        }
    }

    /**
     * Soft delete (set active = false)
     * POST /organization/nonactive
     */
    public function nonactiveHierarchy(Request $request): JsonResponse
    {
        $this->validate($request, [
            'companyid'      => 'sometimes|integer',
            'directorateid' => 'sometimes|integer',
            'divisionid'    => 'sometimes|integer',
            'departmentid'  => 'sometimes|integer',
            'unitid'        => 'sometimes|integer',
            'active'        => 'sometimes|boolean',
        ]);

        $companyId = $request->input('companyid');
        $dirId     = $request->input('companydirectorateid');
        $divId     = $request->input('companydivisionid');
        $deptId    = $request->input('companydepartmentid');
        $unitId    = $request->input('companyunitkerjaid');
        $active   = $request->boolean('active', false);

        try {
            DB::beginTransaction();

            // Prioritaskan level paling spesifik
            if ($unitId) {
                $unit = CompanyUnitKerja::where('companyunitkerjaid', $unitId)
                    ->when($companyId, fn($q) => $q->where('companyid', $companyId))
                    ->firstOrFail();

                $unit->active = false;
                $unit->updatedon = Carbon::now();
                $unit->save();

                DB::commit();
                return response()->json([
                    'status'=>'success',
                    'message'=>'Unit berhasil dinonaktifkan',
                    'count'=>1,
                    'data'=>[]
                ],200);
            }

            if ($deptId) {
                $department = CompanyDepartment::where('companydepartmentid', $deptId)
                    ->when($companyId, fn($q) => $q->where('companyid', $companyId))
                    ->with('unitKerjas') // pastikan relasi ada
                    ->firstOrFail();

                if ($department->unitKerjas()->where('active', true)->exists() && ! $active) {
                    DB::rollBack();
                    return response()->json([
                        'status'=>'error',
                        'message'=>'Department masih memiliki Unit aktif',
                        'count'=>0,
                        'data'=>[]
                    ], 400);
                }

                // nonaktifkan units (jika active)
                if ($active) {
                    $department->unitKerjas()->where('active', true)
                        ->update(['active' => false, 'updatedon' => Carbon::now()]);
                }

                // nonaktifkan department
                $department->active = false;
                $department->updatedon = Carbon::now();
                $department->save();

                DB::commit();
                return response()->json([
                    'status'=>'success',
                    'message'=>'Department berhasil dinonaktifkan',
                    'count'=>1,
                    'data'=>[]
                ],200);
            }

            if ($divId) {
                $division = CompanyDivision::where('companydivisionid', $divId)
                    ->when($companyId, fn($q) => $q->where('companyid', $companyId))
                    ->with(['departments' => fn($q) => $q->where('active', true)])
                    ->firstOrFail();

                if ($division->departments()->where('active', true)->exists() && ! $active) {
                    DB::rollBack();
                    return response()->json([
                        'status'=>'error',
                        'message'=>'Division masih memiliki Department aktif',
                        'count'=>0,'data'=>[]
                    ], 400);
                }

                if ($active) {
                    // nonaktifkan semua departments dan unitnya
                    foreach ($division->departments as $dept) {
                        $dept->unitKerjas()->where('active', true)
                            ->update(['active' => false, 'updatedon' => Carbon::now()]);

                        $dept->active = false;
                        $dept->updatedon = Carbon::now();
                        $dept->save();
                    }
                }

                $division->active = false;
                $division->updatedon = Carbon::now();
                $division->save();

                DB::commit();
                    return response()->json(['
                    status'=>'success',
                    'message'=>'Division berhasil dinonaktifkan',
                    'count'=>1,
                    'data'=>[]
                ],200);
            }

            if ($dirId) {
                $directorate = CompanyDirectorate::where('companydirectorateid', $dirId)
                    ->when($companyId, fn($q) => $q->where('companyid', $companyId))
                    ->with(['divisions.departments.unitKerjas'])
                    ->firstOrFail();

                // cek apakah ada anak aktif
                $hasActiveChildren = $directorate->divisions()->where('active', true)->exists();
                if ($hasActiveChildren && ! $active) {
                    DB::rollBack();
                    return response()->json([
                        'status'=>'error',
                        'message'=>'Directorate masih memiliki Division aktif.',
                        'count'=>0,
                        'data'=>[]
                    ], 400);
                }

                if ($active) {
                    foreach ($directorate->divisions as $div) {
                        foreach ($div->departments as $dept) {
                            $dept->unitKerjas()->where('active', true)
                                ->update(['active' => false, 'updatedon' => Carbon::now()]);

                            $dept->active = false;
                            $dept->updatedon = Carbon::now();
                            $dept->save();
                        }

                        $div->active = false;
                        $div->updatedon = Carbon::now();
                        $div->save();
                    }
                }

                $directorate->active = false;
                $directorate->updatedon = Carbon::now();
                $directorate->save();

                DB::commit();
                return response()->json(['
                    status'=>'success',
                    'message'=>'Directorate berhasil dinonaktifkan',
                    'count'=>1,
                    'data'=>[]
                ],200);
            }

            DB::rollBack();
            return response()->json([
                'status'=>'error',
                'message'=>'Tidak ada target yang diberikan. Kirim salah satu id (unitid / departmentid / divisionid / directorateid).',
                'count'=>0,
                'data'=>[]
            ], 400);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status'=>'error',
                'message'=>'Terjadi kesalahan pada database',
                'count'=>0,
                'data'=>[]
            ],500);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'=>'error',
                'message'=>'Terjadi kesalahan tak terduga: ',
                'count'=>0,
                'data'=>[]
            ],500);
        }
    }
}