<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\UploadedFile;
use App\Services\Drive\DriveUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * 事件詳細画面のドラッグ&ドロップで来たファイルを受けて、
 * 事件の Drive フォルダに上げる。Drive 失敗時はローカルストレージに
 * フォールバックして、メタを uploaded_files に記録する。
 */
class MatterUploadController extends Controller
{
    public function __construct(private readonly DriveUploader $drive) {}

    public function store(Request $request, Matter $matter): RedirectResponse
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|max:51200', // 50MB / file
        ]);

        foreach ($request->file('files', []) as $upload) {
            $localRel  = $upload->store("matters/{$matter->id}");
            $localFull = Storage::path($localRel);
            $name      = $upload->getClientOriginalName();

            $driveId = $this->drive->safeUpload($localFull, $matter, $name);

            UploadedFile::create([
                'matter_id'           => $matter->id,
                'original_name'       => $name,
                'mime_type'           => $upload->getClientMimeType(),
                'size'                => $upload->getSize(),
                'drive_file_id'       => $driveId,
                'local_path'          => $driveId ? null : $localRel,
                'uploaded_by_user_id' => $request->user()?->id,
            ]);
        }

        return back()->with('success', 'アップロード完了');
    }

    public function destroy(Matter $matter, UploadedFile $file): RedirectResponse
    {
        if ($file->matter_id !== $matter->id) {
            abort(403);
        }

        if ($file->local_path) {
            Storage::delete($file->local_path);
        }
        // Drive 側のファイル削除は所員の運用に委ねる（本ソフトは DB レコードのみ削除）
        $file->delete();

        return back()->with('success', '削除しました');
    }
}
