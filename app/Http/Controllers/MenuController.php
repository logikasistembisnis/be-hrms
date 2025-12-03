<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\Grouprole;
use Carbon\Carbon;

class MenuController extends Controller
{
    /**
     * GET /menu
     * Mengambil semua menu
     */
    public function index(): JsonResponse
    {
        try {
            $menus = Menu::all();

            $data = $menus->map(function ($m) {
                return array_merge($m->toArray(), ['grouprole_ids' => $m->getGrouproleIds()]);
            });

            return response()->json([
                'status' => 'success',
                'count' => $menus->count(),
                'data' => $data
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

    /**
     * PUT /menu
     * Bulk upsert menu. Menerima array of objects.
     * Contoh body: [ { menuid: 1, menuname: 'X', grouprole: '#1#2#3#' }, { menuname: 'New', grouprole: [1,2] } ]
     */
    public function upsert(Request $request): JsonResponse
    {
        try {
            // Ambil semua data dari body request
            $menuData = $request->json()->all();

            // Pastikan request body berupa array (karena dikirim dalam bentuk [])
            if (!is_array($menuData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request body harus berupa array data menu'
                ], 400);
            }

            $results = [];

            foreach ($menuData as $data) {
                // Validasi tiap item menu
                $validator = validator($data, [
                    'menuid'        => 'nullable|integer|exists:menu,menuid',
                    'companyid' => 'required|integer|exists:company,companyid',
                    'menuname'      => 'required|string|max:255',
                    'parentmenuid'  => 'nullable|integer', 
                    'ordersequence' => 'nullable|integer',
                    'menutype'      => 'nullable|string|max:50',
                    'hrgroup'       => 'nullable|boolean',
                    'grouprole'     => 'nullable', // Bisa array [1,2] atau string "#1#2#"
                    'active'        => 'required|boolean',
                    'createdby'     => 'nullable|string',
                    'updatedby'     => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    $results[] = [
                        'status' => 'failed',
                        'message' => $validator->errors()->all(),
                        'input' => $data // Opsional: untuk tracking data mana yang gagal
                    ];
                    continue;
                }

                $validated = $validator->validated();

                // LOGIKA KHUSUS GROUPROLE (Concatenation #ID#)
                // Jika input array [1, 2] -> ubah jadi string "#1#2#"
                $grouproleFormatted = null;
                if (isset($validated['grouprole'])) {
                    if (is_array($validated['grouprole'])) {
                        // Pastikan array tidak kosong
                        if (count($validated['grouprole']) > 0) {
                            $grouproleFormatted = '#' . implode('#', $validated['grouprole']) . '#';
                        }
                    } else {
                        // Jika input string, asumsi format sudah benar atau single ID
                        // Jika user kirim "1", kita ubah jadi "#1#". Jika sudah "#1#", biarkan.
                        $rawRole = (string) $validated['grouprole'];
                        if (strpos($rawRole, '#') === false) {
                            $grouproleFormatted = '#' . $rawRole . '#';
                        } else {
                            $grouproleFormatted = $rawRole;
                        }
                    }
                }

                $finalSequence = $validated['ordersequence'] ?? null;

                if ($finalSequence === null) {
                    // Cari urutan terakhir berdasarkan parent yang sama
                    $lastOrder = Menu::where('parentmenuid', $validated['parentmenuid'])
                                    ->max('ordersequence');
                    
                    // Jika belum ada data, mulai dari 1. Jika ada, tambah 1.
                    $finalSequence = $lastOrder ? $lastOrder + 1 : 1;
                }

                // Jika ada menuid -> update data
                if (isset($validated['menuid'])) {
                    $menu = Menu::find($validated['menuid']);
                    if ($menu) {
                        $updateData = [
                            'companyid'     => $validated['companyid'],
                            'menuname'      => $validated['menuname'],
                            'parentmenuid'  => $validated['parentmenuid'],
                            'menutype'      => $validated['menutype'] ?? null,
                            'hrgroup'       => $validated['hrgroup'] ?? false,
                            'active'        => $validated['active'],
                            'updatedon'     => Carbon::now(),
                            'updatedby'     => $data['updatedby'] ?? null,
                        ];

                        // Hanya update grouprole jika dikirim di payload
                        if (array_key_exists('grouprole', $validated)) {
                            $updateData['grouprole'] = $grouproleFormatted;
                        }

                        $menu->update($updateData);

                        $results[] = [
                            'status' => 'updated',
                            'data' => $menu
                        ];
                    } else {
                        $results[] = [
                            'status' => 'failed',
                            'message' => "Menu ID {$validated['menuid']} not found"
                        ];
                    }
                } 
                // Jika tidak ada menuid -> insert baru
                else {
                    $createData = [
                        'companyid' => $validated['companyid'],
                        'menuname'      => $validated['menuname'],
                        'parentmenuid'  => $validated['parentmenuid'],
                        'ordersequence' => $finalSequence,
                        'menutype'      => $validated['menutype'] ?? null,
                        'hrgroup'       => $validated['hrgroup'] ?? false,
                        'active'        => $validated['active'],
                        'createdon'     => Carbon::now(),
                        'createdby'     => $data['createdby'] ?? null,
                    ];

                    // Tambahkan grouprole ke data create
                    if ($grouproleFormatted) {
                        $createData['grouprole'] = $grouproleFormatted;
                    }

                    $newMenu = Menu::create($createData);

                    $results[] = [
                        'status' => 'created',
                        'data' => $newMenu
                    ];
                }
            }

            // Kembalikan hasil akhir semua proses (update/insert)
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
                'message' => 'Terjadi kesalahan tak terduga',
                'count' => 0,
                'data' => [],
            ], 500);
        }
    }

    /**
     * DELETE /menu/{id}
     * Hapus menu berdasarkan id. 
     */
    public function destroy($id): JsonResponse
    {
        try {
            $menu = Menu::find($id);
            if (!$menu) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Menu not found'
                ], 404);
            }

            $menu->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil dihapus'
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
