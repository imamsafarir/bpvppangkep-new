<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Definisi role-role yang tersedia di sistem
     */
    public const AVAILABLE_ROLES = [
        [
            'id'          => 'super_admin',
            'label'       => 'Super Admin',
            'desc'        => 'Akses penuh ke seluruh modul, konfigurasi, dan manajemen pengguna',
            'badge_color' => 'default',
        ],
        [
            'id'          => 'admin_website',
            'label'       => 'Admin Website',
            'desc'        => 'Kelola website balai, profil balai, berita, dan informasi publik',
            'badge_color' => 'info',
        ],
        [
            'id'          => 'admin_shortlink',
            'label'       => 'Admin Shortlink',
            'desc'        => 'Pengelola pemendek tautan resmi balai dan form penangkapan lead',
            'badge_color' => 'success',
        ],
        [
            'id'          => 'medsos_instruktur',
            'label'       => 'Medsos Instruktur',
            'desc'        => 'Pengusul materi pelatihan kejuruan dan narasumber konten',
            'badge_color' => 'secondary',
        ],
        [
            'id'          => 'medsos_planner',
            'label'       => 'Medsos Planner',
            'desc'        => 'Perencanaan ide, brief liputan, topik pelatihan, dan kalender medsos',
            'badge_color' => 'primary',
        ],
        [
            'id'          => 'medsos_editor',
            'label'       => 'Medsos Editor',
            'desc'        => 'Produksi video, editing visual grafis, respon revisi, dan upload hasil render',
            'badge_color' => 'warning',
        ],
        [
            'id'          => 'medsos_admin_platform',
            'label'       => 'Medsos Admin Platform',
            'desc'        => 'Publisher akun media sosial (IG/TikTok/FB/YT) dan review tayang',
            'badge_color' => 'outline',
        ],
        [
            'id'          => 'user',
            'label'       => 'Pengguna Biasa',
            'desc'        => 'Akses standar pengguna / pegawai umum balai',
            'badge_color' => 'zinc',
        ],
    ];

    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $role = $request->query('role');
        $status = $request->query('status'); // 'all', 'active', 'inactive'
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = User::query()
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('username', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('room_or_desk', 'like', "%{$s}%");
                });
            })
            ->when($role, function ($q, $r) {
                $q->where(function ($sub) use ($r) {
                    $sub->whereRaw("FIND_IN_SET(?, REPLACE(role, ' ', ''))", [$r])
                        ->orWhere('role', 'like', "%{$r}%");
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                if ($status === 'active') {
                    $q->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $allowedColumns = ['id', 'name', 'username', 'email', 'role', 'room_or_desk', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedColumns, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        $users = $query->paginate($perPage)->withQueryString();

        // Statistik Pengguna
        $stats = [
            'total'    => User::count(),
            'active'   => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'admins'   => User::where(function ($q) {
                $q->where('role', 'like', '%super_admin%')
                    ->orWhere('role', 'like', '%admin%');
            })->count(),
        ];

        return Inertia::render('Admin/User/Index', [
            'users'          => $users,
            'stats'          => $stats,
            'availableRoles' => self::AVAILABLE_ROLES,
            'filters'        => [
                'search'   => $search ?? '',
                'role'     => $role ?? '',
                'status'   => $status ?? '',
                'per_page' => $perPage,
                'sort_by'  => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'username'     => 'required|string|max:255|unique:users,username',
            'email'        => 'required|email|max:255|unique:users,email',
            'password'     => 'required|string|min:8',
            'roles'        => 'nullable|array',
            'roles.*'      => 'string|max:100',
            'role'         => 'nullable|string|max:255',
            'room_or_desk' => 'nullable|string|max:255',
            'is_active'    => 'boolean',
        ]);

        $passwordHash = Hash::make($validated['password']);

        // Handle multiple roles
        if ($request->has('roles') && is_array($request->roles) && count($request->roles) > 0) {
            $roles = array_values(array_unique(array_filter(array_map('trim', $request->roles))));
            $roleStr = implode(',', $roles);
        } else {
            $roleStr = !empty($validated['role']) ? trim($validated['role']) : 'user';
        }

        $user = new User();
        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->password = $passwordHash;
        $user->role = $roleStr;
        $user->room_or_desk = $validated['room_or_desk'] ?? null;
        $user->is_active = $request->boolean('is_active', true);
        $user->save();

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'username'     => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'roles'        => 'nullable|array',
            'roles.*'      => 'string|max:100',
            'role'         => 'nullable|string|max:255',
            'room_or_desk' => 'nullable|string|max:255',
            'is_active'    => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $user->password = Hash::make($request->password);
        }

        // Handle multiple roles
        if ($request->has('roles') && is_array($request->roles) && count($request->roles) > 0) {
            $roles = array_values(array_unique(array_filter(array_map('trim', $request->roles))));
            $user->role = implode(',', $roles);
        } elseif ($request->filled('role')) {
            $user->role = trim($validated['role']);
        }

        $user->room_or_desk = $validated['room_or_desk'] ?? null;
        $user->is_active = $request->boolean('is_active', true);
        $user->save();

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->name} berhasil {$statusStr}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', "Password untuk akun {$user->name} berhasil diubah.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'ids'    => 'required|array',
            'ids.*'  => 'integer|exists:users,id',
        ]);

        $currentUserId = auth()->id();
        // Jangan eksekusi akun yang sedang login untuk tindakan deaktif / hapus
        $targetIds = array_values(array_filter($validated['ids'], fn($id) => (int) $id !== (int) $currentUserId));

        if (empty($targetIds)) {
            return back()->with('error', 'Tidak ada akun valid yang dapat diproses.');
        }

        switch ($validated['action']) {
            case 'activate':
                User::whereIn('id', $targetIds)->update(['is_active' => true]);
                $message = count($targetIds) . ' akun berhasil diaktifkan.';
                break;
            case 'deactivate':
                User::whereIn('id', $targetIds)->update(['is_active' => false]);
                $message = count($targetIds) . ' akun berhasil dinonaktifkan.';
                break;
            case 'delete':
                User::whereIn('id', $targetIds)->delete();
                $message = count($targetIds) . ' akun berhasil dihapus.';
                break;
            default:
                return back()->with('error', 'Aksi tidak dikenali.');
        }

        return back()->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $ids = $request->query('ids');
        $query = User::query();

        if ($ids) {
            $idArray = array_filter(explode(',', (string) $ids));
            $query->whereIn('id', $idArray);
        } else {
            $search = $request->query('search');
            $role = $request->query('role');
            $status = $request->query('status');

            $query
                ->when($search, function ($q, $s) {
                    $q->where(function ($sub) use ($s) {
                        $sub->where('name', 'like', "%{$s}%")
                            ->orWhere('username', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('room_or_desk', 'like', "%{$s}%");
                    });
                })
                ->when($role, function ($q, $r) {
                    $q->where(function ($sub) use ($r) {
                        $sub->whereRaw("FIND_IN_SET(?, REPLACE(role, ' ', ''))", [$r])
                            ->orWhere('role', 'like', "%{$r}%");
                    });
                })
                ->when($status !== null && $status !== '', function ($q) use ($status) {
                    if ($status === 'active') {
                        $q->where('is_active', true);
                    } elseif ($status === 'inactive') {
                        $q->where('is_active', false);
                    }
                });
        }

        $users = $query->latest('id')->get();
        $filename = 'daftar-pengguna-bpvp-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Nama Lengkap',
                'Username',
                'Email',
                'Role Akses',
                'Ruangan / Meja',
                'Status Akun',
                'Tanggal Dibuat',
            ]);

            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->username,
                    $u->email,
                    implode(', ', $u->roles),
                    $u->room_or_desk ?? '-',
                    $u->is_active ? 'Aktif' : 'Nonaktif',
                    $u->created_at ? $u->created_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download format template CSV untuk impor data pengguna
     */
    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'template-import-pengguna-bpvp.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header Kolom
            fputcsv($handle, [
                'Nama Lengkap',
                'Username',
                'Email',
                'Password',
                'Role Akses',
                'Ruangan / Meja',
            ]);

            // Baris Contoh dengan 8 Role Standar
            fputcsv($handle, ['Super Admin BPVP', 'superadmin_bpvp', 'superadmin@bpvppangkep.go.id', 'Password123!', 'super_admin', 'Gedung Utama Lt. 2']);
            fputcsv($handle, ['Admin Website Balai', 'adminweb_bpvp', 'adminweb@bpvppangkep.go.id', 'Password123!', 'admin_website', 'Gedung A - Ruang IT']);
            fputcsv($handle, ['Admin Pemendek Link', 'adminshort_bpvp', 'adminshortlink@bpvppangkep.go.id', 'Password123!', 'admin_shortlink', 'Gedung B - Ruang Humas']);
            fputcsv($handle, ['Planner Konten Kreatif', 'planner_bpvp', 'planner@bpvppangkep.go.id', 'Password123!', 'medsos_planner', 'Ruang Kreatif & Konten']);
            fputcsv($handle, ['Editor Video & Grafis', 'editor_bpvp', 'editor@bpvppangkep.go.id', 'Password123!', 'medsos_editor', 'Studio Multimedia']);
            fputcsv($handle, ['Admin Publikasi Medsos', 'publisher_bpvp', 'adminplatform@bpvppangkep.go.id', 'Password123!', 'medsos_admin_platform', 'Ruang Publikasi']);
            fputcsv($handle, ['Instruktur Pelatihan', 'instruktur_las', 'instruktur@bpvppangkep.go.id', 'Password123!', 'medsos_instruktur', 'Workshop Kejuruan']);
            fputcsv($handle, ['Pegawai Umum Balai', 'pegawai_balai', 'pegawai@bpvppangkep.go.id', 'Password123!', 'user', 'Bagian Tata Usaha']);

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
