<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Carbon\Carbon;

class DatabaseController extends Controller
{
    /**
     * Check if the authenticated user is an owner
     */
    private function checkOwnerRole()
    {
        if (!auth()->user() || auth()->user()->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Owner role required.'
            ], 403);
        }
        return null;
    }
    /**
     * Export database to SQL file
     */
    public function export()
    {
        $roleCheck = $this->checkOwnerRole();
        if ($roleCheck) return $roleCheck;

        try {
            $filename = 'database_backup_' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
            $filepath = storage_path('app/backups/' . $filename);
            
            // Create backups directory if it doesn't exist
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            // Get database configuration
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            // Execute mysqldump
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // Fallback to PHP-based export if mysqldump fails
                $this->exportWithPHP($filepath);
            }

            // Check if file was created and has content
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create database backup'
                ], 500);
            }

            // Return file download response with proper headers
            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PHP-based database export as fallback
     */
    private function exportWithPHP($filepath)
    {
        $database = config('database.connections.mysql.database');
        
        $sql = "-- MySQL Database Export\n";
        $sql .= "-- Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$database}\n";
        $sql .= "-- Host: " . config('database.connections.mysql.host') . "\n\n";
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "-- Table structure for table `{$tableName}`\n";
            $sql .= "-- --------------------------------------------------------\n\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable->{'Create Table'} . ";\n\n";
            
            // Get table data
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                $sql .= "-- --------------------------------------------------------\n";
                $sql .= "-- Dumping data for table `{$tableName}`\n";
                $sql .= "-- --------------------------------------------------------\n\n";
                
                // Get column names
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowData = [];
                    foreach ($row as $value) {
                        if (is_null($value)) {
                            $rowData[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $rowData[] = $value;
                        } else {
                            $rowData[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $rowData) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql .= "COMMIT;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "\n-- Export completed on " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        
        file_put_contents($filepath, $sql);
    }

    /**
     * Import database from SQL file
     */
    public function import(Request $request)
    {
        $roleCheck = $this->checkOwnerRole();
        if ($roleCheck) return $roleCheck;

        $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt|max:51200' // Max 50MB
        ]);

        try {
            $file = $request->file('sql_file');
            $filename = 'import_' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
            $filepath = storage_path('app/imports/' . $filename);
            
            // Create imports directory if it doesn't exist
            if (!file_exists(storage_path('app/imports'))) {
                mkdir(storage_path('app/imports'), 0755, true);
            }

            // Move uploaded file
            $file->move(storage_path('app/imports'), $filename);

            // Get database configuration
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            // Build mysql import command
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            // Execute mysql import
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // Fallback to PHP-based import if mysql command fails
                $this->importWithPHP($filepath);
            }

            // Clean up uploaded file
            unlink($filepath);

            return response()->json([
                'success' => true,
                'message' => 'Database imported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PHP-based database import as fallback
     */
    private function importWithPHP($filepath)
    {
        $sql = file_get_contents($filepath);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($statement) {
                return !empty($statement) && !preg_match('/^--/', $statement);
            }
        );

        DB::beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    DB::unprepared($statement);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get database info
     */
    public function info()
    {
        $roleCheck = $this->checkOwnerRole();
        if ($roleCheck) return $roleCheck;

        try {
            $database = config('database.connections.mysql.database');
            
            // Get database size
            $sizeQuery = "SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?";
            
            $size = DB::select($sizeQuery, [$database])[0]->size_mb ?? 0;
            
            // Get table count
            $tableCount = count(DB::select('SHOW TABLES'));
            
            // Get record counts for main tables
            $tables = [
                'products' => 'Products',
                'packages' => 'Packages', 
                'customers' => 'Customers',
                'transactions' => 'Transactions',
                'inventory_items' => 'Inventory Items',
                'users' => 'Users'
            ];
            
            $tableCounts = [];
            foreach ($tables as $table => $label) {
                try {
                    $count = DB::table($table)->count();
                    $tableCounts[] = [
                        'table' => $table,
                        'label' => $label,
                        'count' => $count
                    ];
                } catch (\Exception $e) {
                    // Table might not exist
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'database_name' => $database,
                    'size_mb' => $size,
                    'table_count' => $tableCount,
                    'table_counts' => $tableCounts,
                    'last_backup' => $this->getLastBackupInfo()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get database info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get last backup information
     */
    private function getLastBackupInfo()
    {
        $backupDir = storage_path('app/backups');
        
        if (!is_dir($backupDir)) {
            return null;
        }

        $files = glob($backupDir . '/database_backup_*.sql');
        
        if (empty($files)) {
            return null;
        }

        // Get the most recent backup file
        $latestFile = '';
        $latestTime = 0;
        
        foreach ($files as $file) {
            $time = filemtime($file);
            if ($time > $latestTime) {
                $latestTime = $time;
                $latestFile = $file;
            }
        }

        if ($latestFile) {
            return [
                'filename' => basename($latestFile),
                'date' => Carbon::createFromTimestamp($latestTime)->format('Y-m-d H:i:s'),
                'size_mb' => round(filesize($latestFile) / 1024 / 1024, 2)
            ];
        }

        return null;
    }
}