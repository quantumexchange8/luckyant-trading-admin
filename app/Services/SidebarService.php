<?php

namespace App\Services;

use App\Models\Transaction;

class SidebarService {
    public function getPendingTransactionCount(): int
    {
        return Transaction::query()
            ->where('category', 'wallet')
            ->where('status', 'Processing')
            ->count();
    }
}
