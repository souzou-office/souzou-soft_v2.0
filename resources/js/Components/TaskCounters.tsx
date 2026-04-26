import type { TaskCounters as Counters } from '@/Types';

/**
 * タスク集計バッジ。事件一覧の各行頭で「いま何件動いていて、何件問題か」を即視。
 *
 * 表示順は緊急度順: 期日超過 → 未割当 → 進行中 → 完了 → 全件。
 * 該当 0 のものは表示しない（情報密度を上げるため）。
 */
export function TaskCounters({ counters }: { counters: Counters }) {
    return (
        <div className="flex items-center gap-1.5 text-xs tabular-nums">
            {counters.overdue > 0 && (
                <Badge tone="red" symbol="⚠" value={counters.overdue} title="期日超過" />
            )}
            {counters.unassigned > 0 && (
                <Badge tone="amber" symbol="?" value={counters.unassigned} title="未割当" />
            )}
            {counters.in_progress > 0 && (
                <Badge tone="blue" symbol="◐" value={counters.in_progress} title="進行中" />
            )}
            <Badge tone="emerald" symbol="✓" value={counters.completed} title="完了" />
            <span className="text-gray-400">/{counters.total}</span>
        </div>
    );
}

const TONES: Record<string, string> = {
    red:     'bg-red-100 text-red-800 ring-red-300',
    amber:   'bg-amber-100 text-amber-800 ring-amber-300',
    blue:    'bg-blue-100 text-blue-800 ring-blue-300',
    emerald: 'bg-emerald-100 text-emerald-800 ring-emerald-300',
};

function Badge({
    tone,
    symbol,
    value,
    title,
}: {
    tone: keyof typeof TONES;
    symbol: string;
    value: number;
    title: string;
}) {
    return (
        <span
            title={title}
            className={`inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 ring-1 ${TONES[tone]}`}
        >
            <span aria-hidden>{symbol}</span>
            <span className="font-semibold">{value}</span>
        </span>
    );
}
