<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['type' => 'user', 'name' => 'pending_verification', 'description' => 'Akun belum verifikasi'],
            ['type' => 'user', 'name' => 'verified', 'description' => 'Akun sudah verifikasi'],
            ['type' => 'user', 'name' => 'blacklisted', 'description' => 'Akun diblokir'],

            ['type' => 'library_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi'],
            ['type' => 'library_application', 'name' => 'approved', 'description' => 'Pengajuan perpustakaan disetujui'],
            ['type' => 'library_application', 'name' => 'rejected', 'description' => 'Pengajuan perpustakaan ditolak'],

            ['type' => 'librarian_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi'],
            ['type' => 'librarian_application', 'name' => 'approved', 'description' => 'Pengajuan menjadi pustakawan disetujui'],
            ['type' => 'librarian_application', 'name' => 'rejected', 'description' => 'Pengajuan menjadi pustakawan ditolak'],

            ['type' => 'membership_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi oleh pustakawan'],
            ['type' => 'membership_application', 'name' => 'approved', 'description' => 'Pengajuan member disetujui'],
            ['type' => 'membership_application', 'name' => 'rejected', 'description' => 'Pengajuan member ditolak'],

            ['type' => 'loan', 'name' => 'pending', 'description' => 'Menunggu persetujuan oleh pustakawan'],
            ['type' => 'loan', 'name' => 'approved', 'description' => 'Peminjaman buku disetujui'],
            ['type' => 'loan', 'name' => 'rejected', 'description' => 'Peminjaman buku ditolak'],
            ['type' => 'loan', 'name' => 'returned', 'description' => 'Buku dikembalikan'],
            ['type' => 'loan', 'name' => 'overdue', 'description' => 'Terlambat mengembalikan buku'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate([
                'type' => $status['type'],
                'name' => $status['name'],
            ], ['description' => $status['description']]);
        }
    }
}
