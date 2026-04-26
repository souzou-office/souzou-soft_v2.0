import { Link } from '@inertiajs/react';
import { cn } from '@/Lib/cn';
import type { TaskStepData } from '@/Types';

/**
 * タスク密度グリッド。事件一覧で「タスクが埋まっているか」を確認するための部品。
 *
 * 1セル = 1タスク。色 = 状態、文字 = 担当者頭文字。
 *  - 完了:    緑塗り + 担当者頭文字（白抜き）
 *  - 進行中:  青塗り + 担当者頭文字（白抜き）
 *  - 期日超過: 赤塗り + 担当者頭文字
 *  - 未割当:  黄背景 + "?"
 *  - 未着手:  白背景 + 担当者頭文字（薄字）
 *
 * フェーズで束ねず平坦に並べることで、「現実は前後する」運用を阻害しない。
 * Hover でフルネーム・期日のツールチップ。クリックで該当 task に遷移。
 */
type Props = {
    matterId: number;
    tasks: TaskStepData[];
    cellSize?: number;
};

const cellBg = (task: TaskStepData): string => {
    if (task.state === 'completed') return 'bg-emerald-500 text-white';
    if (task.state === 'overdue')   return 'bg-red-500 text-white ring-1 ring-red-700';
    if (task.state === 'in_progress') return 'bg-blue-500 text-white';
    if (task.is_unassigned)         return 'bg-amber-100 text-amber-800 ring-1 ring-amber-400';
    return 'bg-white text-gray-400 ring-1 ring-gray-300';
};

export function TaskGrid({ matterId, tasks, cellSize = 22 }: Props) {
    return (
        <div className="inline-flex flex-wrap gap-0.5">
            {tasks.map((t) => {
                const initial = t.is_unassigned ? '?' : (t.assignee_initial ?? '');
                const tooltip = [
                    `#${t.task_code} ${t.name}`,
                    `担当: ${t.assignee ?? '未割当'}`,
                    t.planned_date && `期日: ${t.planned_date}`,
                ]
                    .filter(Boolean)
                    .join('\n');

                return (
                    <Link
                        key={t.id}
                        href={`/matters/${matterId}#task-${t.id}`}
                        title={tooltip}
                        className={cn(
                            'flex items-center justify-center rounded text-[10px] font-bold leading-none',
                            cellBg(t),
                        )}
                        style={{ width: cellSize, height: cellSize }}
                    >
                        {initial}
                    </Link>
                );
            })}
        </div>
    );
}
