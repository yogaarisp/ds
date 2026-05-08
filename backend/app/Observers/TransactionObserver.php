<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Table;
use App\Services\TelegramNotificationService;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     * When a new order is created with a table_id, mark the table as occupied.
     * Also sends notification to n8n webhook (Telegram).
     */
    public function created(Transaction $transaction): void
    {
        $this->updateTableStatus($transaction);

        // Send notification to n8n webhook (dijalankan setelah response selesai agar items sudah tersimpan)
        dispatch(function () use ($transaction) {
            try {
                TelegramNotificationService::sendNewOrderNotification($transaction);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Notification dispatch error: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    /**
     * Handle the Transaction "updated" event.
     * When a transaction status changes (completed, cancelled), update the table.
     */
    public function updated(Transaction $transaction): void
    {
        // Only act if status changed or table_id changed
        if ($transaction->wasChanged(['status', 'table_id'])) {
            $this->updateTableStatus($transaction);

            // If table_id was changed (moved to different table), also update the OLD table
            if ($transaction->wasChanged('table_id')) {
                $oldTableId = $transaction->getOriginal('table_id');
                if ($oldTableId) {
                    $oldTable = Table::find($oldTableId);
                    if ($oldTable) {
                        $oldTable->refreshStatus();
                    }
                }
            }
        }
    }

    /**
     * Update the table status based on the transaction state.
     */
    private function updateTableStatus(Transaction $transaction): void
    {
        if (!$transaction->table_id) {
            return;
        }

        $table = Table::find($transaction->table_id);
        if (!$table) {
            return;
        }

        $table->refreshStatus();
    }
}
