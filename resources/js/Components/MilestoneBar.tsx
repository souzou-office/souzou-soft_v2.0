import { cn } from '@/Lib/cn';

/**
 * 4 マイルストーンの締切バー。事件詳細の上部に固定で並べる。
 *
 *  事前郵送(-10) ── 押印返送(-2) ── 融資受領(-2) ── 全確定(-1) ── 決済日
 *
 * 単一の決済日マイルストーンでは現実が表現できないので、4 つに分けて
 * それぞれ独立した締切として可視化する。
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

type Props = {
    milestones: Milestone[];
    settlementAt: string | null;
};

function tone(m: Milestone): { ring: string; text: string; bg: string } {
    if (m.completed_at) return { ring: 'ring-emerald-300', text: 'text-emerald-700', bg: 'bg-emerald-50' };
    if (m.is_overdue)   return { ring: 'ring-red-300',     text: 'text-red-700',     bg: 'bg-red-50' };
    if (m.days_left !== null && m.days_left <= 2)
                        return { ring: 'ring-amber-300',   text: 'text-amber-700',   bg: 'bg-amber-50' };
    return { ring: 'ring-gray-200', text: 'text-gray-600', bg: 'bg-white' };
}

export function MilestoneBar({ milestones, settlementAt }: Props) {
    return (
        <div className="flex items-stretch gap-2 overflow-x-auto border-b bg-white px-4 py-2">
            {milestones.map((m, idx) => {
                const t = tone(m);
                return (
                    <div key={m.id} className="flex items-center gap-2">
                        <div className={cn('rounded px-2 py-1 ring-1', t.ring, t.bg)}>
                            <div className={cn('text-[10px] font-semibold', t.text)}>{m.name}</div>
                            <div className="font-mono text-xs text-gray-700">{m.deadline ?? '—'}</div>
                            <div className="text-[10px] text-gray-500">
                                {m.completed_at && '✓ 完了'}
                                {!m.completed_at && m.days_left !== null && (
                                    m.days_left < 0
                                        ? `${Math.abs(m.days_left)}日超過`
                                        : `あと${m.days_left}日`
                                )}
                            </div>
                        </div>
                        {idx < milestones.length - 1 && (
                            <span className="text-gray-300">→</span>
                        )}
                    </div>
                );
            })}
            {settlementAt && (
                <>
                    <span className="text-gray-300">→</span>
                    <div className="rounded bg-brand-navy px-2 py-1 text-white">
                        <div className="text-[10px] font-semibold">決済日</div>
                        <div className="font-mono text-xs">
                            {new Date(settlementAt).toLocaleDateString('ja-JP', {
                                month: '2-digit', day: '2-digit', weekday: 'short',
                            })}
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
