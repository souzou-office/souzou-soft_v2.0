<?php

namespace App\Services\Drive;

use App\Models\Matter;
use Google\Client as GoogleClient;
use Google\Service\Drive as DriveService;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

/**
 * 第7章 Google Drive 連携。
 *
 * - サービスアカウント認証（7.2）
 * - 7.4 ファイル命名規則は呼び出し側で組み立て、ここでは受領するだけ
 * - 7.5 エラーハンドリング: API 失敗時は local fallback（呼び出し側で
 *        既に local ファイルは保存済みなので、ここでは Drive 失敗を吸収）
 */
class DriveUploader
{
    private ?DriveService $service = null;

    public function safeUpload(string $localPath, Matter $matter, string $filename): ?string
    {
        if (! $matter->drive_folder_id) {
            return null;
        }

        try {
            return $this->upload($localPath, $matter->drive_folder_id, $filename);
        } catch (\Throwable $e) {
            Log::warning('Drive upload failed, fallback to local only', [
                'matter_id' => $matter->id,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function upload(string $localPath, string $folderId, string $filename): string
    {
        $service = $this->service();

        $metadata = new DriveFile([
            'name'    => $filename,
            'parents' => [$folderId],
        ]);

        $file = $service->files->create($metadata, [
            'data'       => file_get_contents($localPath),
            'mimeType'   => $this->guessMime($filename),
            'uploadType' => 'multipart',
            'fields'     => 'id',
            'supportsAllDrives' => true,
        ]);

        return $file->id;
    }

    private function service(): DriveService
    {
        if ($this->service) {
            return $this->service;
        }

        $credPath = base_path(config('services.google.service_account_path'));
        if (! file_exists($credPath)) {
            throw new \RuntimeException("Google service account credentials not found: $credPath");
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credPath);
        $client->addScope(DriveService::DRIVE_FILE);

        return $this->service = new DriveService($client);
    }

    private function guessMime(string $filename): string
    {
        return match (true) {
            str_ends_with($filename, '.docx') => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            str_ends_with($filename, '.xlsx') => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            str_ends_with($filename, '.pdf')  => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
