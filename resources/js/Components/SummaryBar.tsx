import { cn } from '@/Lib/cn';

/**
 * 3.1.1 第1層: 事件サマリーバー（高さ約 40px、常時固定）。
 * 期日警告に応じて背景色が変化:
 *   - 通常時: 白
 *   - 期日超過時: 薄赤
 */
type Props = {
    matterNumber: string;
    parties: string;
    settlementAt?: string | null;
    jobTypeLabel?: string | null;
    progress: { done: number; total: number; percent: number };
    nextTask?: {
        name: string;
        assignee?: string | null;
        planned_date?: string | null;
        days_left?: number | null;
    } | null;
};

function dueWarningClass(daysLeft?: number | null) {
    if (daysLeft === undefined || daysLeft === null) return '';
    if (daysLeft < 0) return 'bg-red-50 border-b-red-300';
    if (daysLeft <= 2) return 'border-b-red-300';
    if (daysLeft <= 5) return 'border-b-amber-300';
    return '';
}

function dueWarningLabel(daysLeft?: number | null) {
    if (daysLeft === undefined || daysLeft === null) return null;
    if (daysLeft < 0) return <span className="font-bold text-red-600">期日超過 {Math.abs(daysLeft)}日</span>;
    if (daysLeft <= 2) return <span className="text-red-600">⚠ あと {daysLeft} 日</span>;
    if (daysLeft <= 5) return <span className="text-amber-600">あと {daysLeft} 日</span>;
    return <span className="text-gray-500">あと {daysLeft} 日</span>;
}

export function SummaryBar({
    matterNumber,
    parties,
    settlementAt,
    jobTypeLabel,
    progress,
    nextTask,
}: Props) {
    return (
        <div
            className={cn(
                'flex h-10 items-center gap-4 border-b bg-white px-4 text-sm',
                dueWarningClass(nextTask?.days_left),
            )}
        >
            <span className="font-mono font-semibold text-brand-navy">{matterNumber}</span>
            <span className="text-gray-700">{parties}</span>
            {settlementAt && (
                <span className="text-gray-600">
                    決済: {new Date(settlementAt).toLocaleString('ja-JP', { month: '2-digit', day: '2-digit', weekday: 'short', hour: '2-digit', minute: '2-digit' })}
                </span>
            )}
            {jobTypeLabel && (
                <span className="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700">{jobTypeLabel}</span>
            )}
            <span className="text-gray-700">
                進捗 <span className="font-semibold">{progress.done}/{progress.total}</span>{' '}
                <span className="text-gray-500">({progress.percent}%)</span>
            </span>
            {nextTask && (
                <span className="ml-auto text-gray-700">
                    次: <span className="font-medium">{nextTask.name}</span>
                    {nextTask.assignee && <span className="text-gray-500"> ({nextTask.assignee})</span>}
                    {nextTask.planned_date && (
                        <span className="ml-2 text-gray-500">期日 {nextTask.planned_date}</span>
                    )}
                    <span className="ml-2">{dueWarningLabel(nextTask.days_left)}</span>
                </span>
            )}
        </div>
    );
}
