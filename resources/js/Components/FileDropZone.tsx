import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { cn } from '@/Lib/cn';

/**
 * 事件詳細の右パネル下部に置く、ドラッグ&ドロップ対応のファイル投入欄。
 *
 *  - Drive 連携あり: matter.drive_folder_id にアップロード
 *  - Drive 連携なし or 失敗時: local fallback
 *  - 複数ファイル対応
 *  - クリックでファイル選択ダイアログも開く
 */

type UploadedFile = {
    id: number;
    original_name: string;
    human_size: string;
    drive_url: string | null;
    is_local_only: boolean;
    uploader: string | null;
    created_at: string;
};

type Props = {
    matterId: number;
    files: UploadedFile[];
    driveFolderId: string | null;
};

export function FileDropZone({ matterId, files, driveFolderId }: Props) {
    const [hover, setHover] = useState(false);
    const [uploading, setUploading] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const upload = (selected: FileList | null) => {
        if (!selected || selected.length === 0) return;
        const fd = new FormData();
        Array.from(selected).forEach((f) => fd.append('files[]', f));

        setUploading(true);
        router.post(`/matters/${matterId}/uploads`, fd, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => setUploading(false),
        });
    };

    return (
        <div className="border-t bg-white p-4">
            <div className="mb-2 flex items-center justify-between">
                <div className="text-xs font-semibold text-gray-700">
                    📁 事件のファイル
                    {files.length > 0 && (
                        <span className="ml-2 text-gray-400">({files.length})</span>
                    )}
                </div>
                {driveFolderId ? (
                    <a
                        href={`https://drive.google.com/drive/folders/${driveFolderId}`}
                        target="_blank"
                        rel="noreferrer"
                        className="text-[10px] text-brand-blue hover:underline"
                    >
                        Drive で開く ↗
                    </a>
                ) : (
                    <span className="text-[10px] text-amber-600">Drive 未連携</span>
                )}
            </div>

            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setHover(true);
                }}
                onDragLeave={() => setHover(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setHover(false);
                    upload(e.dataTransfer.files);
                }}
                disabled={uploading}
                className={cn(
                    'flex w-full flex-col items-center justify-center rounded-lg border-2 border-dashed py-6 text-sm transition',
                    hover ? 'border-blue-400 bg-blue-50' : 'border-gray-300 bg-gray-50',
                    uploading && 'cursor-wait opacity-60',
                )}
            >
                <div className="text-2xl">{uploading ? '⏳' : '📥'}</div>
                <div className="mt-1 text-gray-700">
                    {uploading ? 'アップロード中…' : 'ファイルをドロップ'}
                </div>
                <div className="text-[10px] text-gray-400">クリックでファイル選択</div>
            </button>
            <input
                ref={inputRef}
                type="file"
                multiple
                hidden
                onChange={(e) => upload(e.target.files)}
            />

            {files.length > 0 && (
                <ul className="mt-3 max-h-48 space-y-1 overflow-y-auto text-xs">
                    {files.slice(0, 8).map((f) => (
                        <li
                            key={f.id}
                            className="flex items-center gap-2 rounded px-2 py-1 hover:bg-gray-50"
                        >
                            <span className="shrink-0 text-base">{iconFor(f.original_name)}</span>
                            <a
                                href={f.drive_url ?? '#'}
                                target={f.drive_url ? '_blank' : undefined}
                                rel="noreferrer"
                                className={cn(
                                    'flex-1 truncate',
                                    f.drive_url ? 'text-brand-blue hover:underline' : 'text-gray-500',
                                )}
                                title={f.original_name}
                            >
                                {f.original_name}
                            </a>
                            <span className="text-gray-400">{f.human_size}</span>
                            {f.is_local_only && (
                                <span className="rounded bg-amber-100 px-1 text-[9px] text-amber-700">local</span>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function iconFor(name: string): string {
    const ext = name.split('.').pop()?.toLowerCase() ?? '';
    if (['pdf'].includes(ext)) return '📕';
    if (['doc', 'docx'].includes(ext)) return '📄';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return '📊';
    if (['png', 'jpg', 'jpeg', 'gif', 'heic'].includes(ext)) return '🖼️';
    if (['zip', 'rar'].includes(ext)) return '📦';
    return '📎';
}
