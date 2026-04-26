import { Link } from '@inertiajs/react';
import type { TaskStepData } from '@/Types';
import { StatusDot } from './StatusDot';

/**
 * 3.1.2 第2層 工程ステッパー。
 *  - 全工程を小さな円で横一列に並べる
 *  - 円下に担当者の頭文字
 *  - ホバーで工程名・期日・担当者・最終更新日時のツールチップ
 *  - クリックで該当工程の入力フォームに遷移
 *
 * compact プロップで 3.2 一覧テーブル行内のミニ版にも切り替えられる。
 */
type Props = {
    matterId: number;
    steps: TaskStepData[];
    compact?: boolean;
    nextTaskId?: number | null;
};

export function TaskStepper({ matterId, steps, compact = false, nextTaskId }: Props) {
    const dotSize = compact ? 10 : 18;
    const labelSize = compact ? 'text-[10px]' : 'text-xs';

    return (
        <div className="flex items-center gap-1.5 overflow-x-auto">
            {steps.map((step) => {
                const state = step.id === nextTaskId && step.state === 'not_started' ? 'next' : step.state;
                const tooltip = [
                    step.name,
                    step.assignee && `担当: ${step.assignee}`,
                    step.planned_date && `期日: ${step.planned_date}`,
                ]
                    .filter(Boolean)
                    .join('\n');

                return (
                    <Link
                        key={step.id}
                        href={`/matters/${matterId}#task-${step.id}`}
                        className="group flex flex-col items-center"
                        title={tooltip}
                    >
                        <StatusDot state={state} size={dotSize} />
                        {!compact && step.assignee && (
                            <span className={`mt-0.5 ${labelSize} text-gray-500`}>
                                {step.assignee.slice(0, 1)}
                            </span>
                        )}
                    </Link>
                );
            })}
        </div>
    );
}
