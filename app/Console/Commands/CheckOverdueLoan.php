<?php

namespace App\Console\Commands;

use App\Models\LibraryMember;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Console\Command;

class CheckOverdueLoan extends Command
{
    protected $signature = 'loans:check-overdue-loan';
    protected $description = 'Cek due_date pada loans dan mengubah status menjadi overdue';
    public function handle()
    {
        $overdueLoans = Loan::where('due_date', '<', now())
            ->loanStatus('approved')
            ->get();
        $status = Status::where('type', 'loan')->where('name', 'overdue')->first();
        foreach ($overdueLoans as $loan) {
            $loan->status_id = $status->id;
            $member = LibraryMember::where('user_id', $loan->borrower_id)
                ->where('library_id', $loan->library_id)
                ->first();
            if ($member) {
                $member->user->assignRole('Blacklist');
            }
            $loan->save();
        }
    }
}
