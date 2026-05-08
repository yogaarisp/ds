<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Send new order notification to n8n webhook
     */
    public static function sendNewOrderNotification(Transaction $transaction): void
    {
        try {
            $webhookUrl = Setting::get('n8n_webhook_url');
            $enabled = Setting::get('n8n_notification_enabled', '0');

            if (!$webhookUrl || $enabled !== '1') {
                return;
            }

            // Load relationships
            $transaction->load(['customer', 'items.product', 'items.package', 'items.packageCombination', 'table', 'user']);

            // Build items array
            $items = [];
            foreach ($transaction->items as $item) {
                $name = '';
                if ($item->item_type === 'product' && $item->product) {
                    $name = $item->product->name;
                } elseif ($item->item_type === 'package') {
                    $packageName = $item->package ? $item->package->name : 'Paket';
                    $combName = $item->packageCombination ? $item->packageCombination->name : '';
                    $name = $combName ? "{$packageName} - {$combName}" : $packageName;
                    if ($item->notes === 'Frozen') {
                        $name .= ' (Frozen)';
                    }
                }

                $items[] = [
                    'name' => $name,
                    'qty' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ];
            }

            // Determine customer name
            $customerName = 'Walk-in';
            if ($transaction->customer) {
                $customerName = $transaction->customer->name;
            } elseif ($transaction->customer_name) {
                $customerName = $transaction->customer_name;
            }

            // Customer phone
            $customerPhone = '-';
            if ($transaction->customer && $transaction->customer->phone) {
                $customerPhone = $transaction->customer->phone;
            } elseif ($transaction->customer_phone) {
                $customerPhone = $transaction->customer_phone;
            }

            $payload = [
                'event' => 'new_order',
                'invoice_number' => $transaction->invoice_number,
                'queue_number' => $transaction->queue_number,
                'order_type' => $transaction->order_type,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'table' => $transaction->table ? $transaction->table->name : null,
                'items' => $items,
                'subtotal' => $transaction->subtotal,
                'discount' => $transaction->discount,
                'total' => $transaction->total,
                'notes' => $transaction->notes,
                'scheduled_date' => $transaction->scheduled_date ? $transaction->scheduled_date->format('Y-m-d') : null,
                'scheduled_time' => $transaction->scheduled_time,
                'created_at' => $transaction->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                'cashier' => $transaction->user ? $transaction->user->name : '-',
            ];

            // Send to n8n webhook (with timeout to prevent blocking)
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('n8n notification sent successfully', [
                    'invoice' => $transaction->invoice_number,
                    'status' => $response->status()
                ]);
            } else {
                Log::warning('n8n notification failed', [
                    'invoice' => $transaction->invoice_number,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            // Never let notification failure affect the order process
            Log::error('n8n notification error: ' . $e->getMessage(), [
                'invoice' => $transaction->invoice_number ?? 'unknown'
            ]);
        }
    }

    /**
     * Send a test notification to verify n8n webhook connection
     */
    public static function sendTestNotification(?string $webhookUrl = null): array
    {
        $url = $webhookUrl ?: Setting::get('n8n_webhook_url');

        if (!$url) {
            return [
                'success' => false,
                'message' => 'URL webhook n8n belum diatur'
            ];
        }

        try {
            $payload = [
                'event' => 'test',
                'message' => '🧪 Test notifikasi dari Dimsum Sarang POS',
                'invoice_number' => 'TEST-' . now()->format('dmyHi'),
                'queue_number' => 0,
                'order_type' => 'instant',
                'customer_name' => 'Test Customer',
                'customer_phone' => '08123456789',
                'table' => null,
                'items' => [
                    [
                        'name' => 'Dimsum Ayam (Contoh)',
                        'qty' => 2,
                        'unit_price' => 25000,
                        'subtotal' => 50000,
                    ],
                    [
                        'name' => 'Paket Hemat A (Contoh)',
                        'qty' => 1,
                        'unit_price' => 45000,
                        'subtotal' => 45000,
                    ]
                ],
                'subtotal' => 95000,
                'discount' => 0,
                'total' => 95000,
                'notes' => 'Ini adalah test notifikasi',
                'scheduled_date' => null,
                'scheduled_time' => null,
                'created_at' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i'),
                'cashier' => 'System Test',
            ];

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Test notifikasi berhasil dikirim! Cek Telegram Anda.'
                ];
            }

            return [
                'success' => false,
                'message' => 'n8n merespon dengan status ' . $response->status() . ': ' . $response->body()
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Tidak bisa terhubung ke n8n. Pastikan URL webhook benar dan server n8n aktif.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
