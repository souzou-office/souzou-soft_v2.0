<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;
use App\Models\Matter;
use App\Models\Task;
use App\Models\TaskFormInput;
use App\Services\Drive\DriveUploader;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * 第6章 書類生成エンジン本体。
 *
 * 流れ:
 *  1. ConditionalPreprocessor で ${IF_xxx} を解決（テンプレ XML を直接書換）
 *  2. phpword TemplateProcessor で ${var} を差込
 *  3. cloneBlock で繰り返しブロック（金融機関数等）を展開
 *  4. 出力ファイルを Drive にアップロード（失敗時は local fallback）
 */
class DocumentGenerator
{
    public function __construct(
        private readonly MatterContext $context,
        private readonly ConditionalPreprocessor $preprocessor,
        private readonly DriveUploader $drive,
    ) {}

    /**
     * @param array<string,mixed> $formData チェックボックスUI（6.2.3）の入力値
     */
    public function generate(
        DocumentTemplate $template,
        Matter $matter,
        Task $task,
        array $formData,
    ): TaskFormInput {
        $sourcePath = storage_path('app/templates/' . $template->file_path);
        if (! file_exists($sourcePath)) {
            throw new \RuntimeException("Template file not found: {$template->file_path}");
        }

        // 1. 条件分岐の事前処理
        $workingPath = $this->applyPreprocessor($sourcePath, $formData);

        // 2. phpword で差込・繰り返し展開
        $processor = new TemplateProcessor($workingPath);
        $variables = $this->context->build($matter);
        foreach ($variables as $key => $value) {
            $processor->setValue($key, (string) ($value ?? ''));
        }
        $this->applyClones($processor, $formData);

        // 3. 出力先ファイル名（7.4 命名規則）
        $filename = $this->buildFilename($template, $matter, $task);
        $outputDir = storage_path('app/generated/' . $matter->id);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }
        $outputPath = $outputDir . '/' . $filename;
        $processor->saveAs($outputPath);

        // 4. Drive アップロード（失敗時は local 保持）
        $driveFileId = $this->drive->safeUpload($outputPath, $matter, $filename);

        // 5. 入力値と出力先を保存
        return TaskFormInput::create([
            'task_id'       => $task->id,
            'template_id'   => $template->id,
            'form_data'     => $formData,
            'generated_at'  => now(),
            'drive_file_id' => $driveFileId,
            'local_path'    => $outputPath,
        ]);
    }

    private function applyPreprocessor(string $sourcePath, array $formData): string
    {
        $flags = (array) ($formData['flags'] ?? []);
        if (empty($flags)) {
            return $sourcePath;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'docgen_') . '.docx';
        copy($sourcePath, $tmpPath);

        $zip = new \ZipArchive();
        $zip->open($tmpPath);
        $xml = $zip->getFromName('word/document.xml');
        if ($xml !== false) {
            $processed = $this->preprocessor->process($xml, $flags);
            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $processed);
        }
        $zip->close();

        return $tmpPath;
    }

    private function applyClones(TemplateProcessor $processor, array $formData): void
    {
        $blocks = (array) ($formData['blocks'] ?? []);
        foreach ($blocks as $name => $rows) {
            if (! is_array($rows)) continue;
            $processor->cloneBlock($name, count($rows), true, false);
            foreach ($rows as $i => $row) {
                if (! is_array($row)) continue;
                foreach ($row as $key => $value) {
                    $processor->setValue("$key#" . ($i + 1), (string) $value);
                }
            }
        }
    }

    private function buildFilename(DocumentTemplate $template, Matter $matter, Task $task): string
    {
        $order = str_pad((string) $task->display_order, 2, '0', STR_PAD_LEFT);
        $desc  = $template->description;
        $num   = $matter->matter_number;
        $ext   = $template->format;
        return "{$order}_{$desc}_{$num}.{$ext}";
    }
}
