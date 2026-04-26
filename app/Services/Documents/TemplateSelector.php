<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;
use App\Models\Matter;

/**
 * 6.2.1 メタデータ駆動のテンプレ選択。
 *
 * 各テンプレに付随する applies_when（YAML）を評価し、合致するものを
 * priority 降順で選ぶ。同 priority の場合は ID 昇順（先登録優先）。
 *
 * 旧 v1 のハードコード if-else 連鎖をここに置換することで、新規テンプレ
 * 追加時のコード変更を不要化する。
 */
class TemplateSelector
{
    /**
     * @param Matter $matter 評価対象の事件
     * @param int    $roleCode 出力対象ロール（買主=2 等）
     * @return DocumentTemplate[]
     */
    public function candidates(Matter $matter, int $roleCode): array
    {
        $templates = DocumentTemplate::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        return $templates->filter(function (DocumentTemplate $tpl) use ($matter, $roleCode) {
            return $this->matches($tpl, $matter, $roleCode);
        })->values()->all();
    }

    public function bestFor(Matter $matter, int $roleCode): ?DocumentTemplate
    {
        $candidates = $this->candidates($matter, $roleCode);
        return $candidates[0] ?? null;
    }

    /**
     * applies_when の各条件を評価する。
     * 想定される条件:
     *   job_type: [int, ...]
     *   role: int
     *   customer.name_contains: [string, ...]   (該当ロールの当事者名に含まれるか)
     */
    private function matches(DocumentTemplate $tpl, Matter $matter, int $roleCode): bool
    {
        $cond = $tpl->appliesWhen();
        if (empty($cond)) {
            return false;
        }

        if (isset($cond['job_type'])) {
            $jobTypes = (array) $cond['job_type'];
            if (! in_array($matter->job_type?->value, $jobTypes, true)) {
                return false;
            }
        }

        if (isset($cond['role']) && (int) $cond['role'] !== $roleCode) {
            return false;
        }

        if (isset($cond['customer.name_contains'])) {
            $needles = (array) $cond['customer.name_contains'];
            $names   = $matter->parties
                ->where('role_code.value', $roleCode)
                ->pluck('name')
                ->all();

            $hit = false;
            foreach ($names as $name) {
                foreach ($needles as $needle) {
                    if (mb_stripos($name, $needle) !== false) {
                        $hit = true;
                        break 2;
                    }
                }
            }
            if (! $hit) return false;
        }

        return true;
    }
}
