<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['type' => 'user', 'name' => 'pending_verification', 'description' => 'Belum verifikasi'],
            ['type' => 'user', 'name' => 'verified', 'description' => 'Sudah verifikasi'],
            ['type' => 'user', 'name' => 'blacklisted', 'description' => 'Akun diblokir'],

            ['type' => 'librarian_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi pustakawan'],
            ['type' => 'librarian_application', 'name' => 'approved', 'description' => 'Pengajuan disetujui'],
            ['type' => 'librarian_application', 'name' => 'rejected', 'description' => 'Pengajuan ditolak'],

            ['type' => 'membership_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi member'],
            ['type' => 'membership_application', 'name' => 'approved', 'description' => 'Pengajuan disetujui'],
            ['type' => 'membership_application', 'name' => 'rejected', 'description' => 'Pengajuan ditolak'],

            ['type' => 'loan', 'name' => 'pending', 'description' => 'Menunggu persetujuan'],
            ['type' => 'loan', 'name' => 'approved', 'description' => 'Peminjaman disetujui'],
            ['type' => 'loan', 'name' => 'rejected', 'description' => 'Peminjaman ditolak'],
            ['type' => 'loan', 'name' => 'returned', 'description' => 'Buku dikembalikan'],
            ['type' => 'loan', 'name' => 'overdue', 'description' => 'Terlambat mengembalikan'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate([
                'type' => $status['type'],
                'name' => $status['name'],
            ], ['description' => $status['description']]);
        }
    }
}
