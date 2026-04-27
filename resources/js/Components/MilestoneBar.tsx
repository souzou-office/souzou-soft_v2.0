import { cn } from '@/Lib/cn';

/**
 * 4 マイルストーンの締切（軽量版）。
 * サマリー直下に薄く 1 行で並べる。決済日は別管理。
 */
type Milestone = {
    id: number;
    key: string;
    name: string;
    deadline: string | null;
    days_left: number | null;
    completed_at: string | null;
    is_overdue: boolean;
};

type Props = { milestones: Milestone[] };

function tone(m: Milestone): { dot: string; text: string } {
    if (m.completed_at) return { dot: 'bg-emerald-500', text: 'text-emerald-700' };
    if (m.is_overdue)   return { dot: 'bg-red-500',     text: 'text-red-700' };
    if (m.days_left !== null && m.days_left <= 2)
                        return { dot: 'bg-amber-500',   text: 'text-amber-700' };
    return { dot: 'bg-gray-300', text: 'text-gray-600' };
}

function suffix(m: Milestone): string {
    if (m.completed_at) return '';
    if (m.days_left === null) return '';
    if (m.days_left < 0) return ` (${m.days_left}日)`;
    if (m.days_left === 0) return ' (本日)';
    return ` (あと${m.days_left}日)`;
}

export function MilestoneBar({ milestones }: Props) {
    return (
        <div className="flex flex-wrap items-center gap-1 border-t px-4 py-1.5 text-xs">
            <span className="text-gray-400">締切:</span>
            {milestones.map((m, idx) => {
                const t = tone(m);
                return (
                    <span key={m.id} className="contents">
                        <span className={cn('inline-flex items-center gap-1 px-2 py-0.5', t.text)}>
                            <span className={cn('size-1.5 rounded-full', t.dot)} />
                            {m.name}
                            {m.deadline && <span className="font-mono">{m.deadline.slice(5)}</span>}
                            {suffix(m) && <span className="text-gray-500">{suffix(m)}</span>}
                        </span>
                        {idx < milestones.length - 1 && <span className="text-gray-300">·</span>}
                    </span>
                );
            })}
        </div>
    );
}
