import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { AppLayout } from '@/Layouts/AppLayout';
import { cn } from '@/Lib/cn';

/**
 * 事件詳細画面（視覚優先・カード型）。
 *
 * ユーザーフィードバック「文字が多い、視覚的にわかりにくい」「今日やる > 担当 >
 * 締切 > 件数 > 全体% の優先順」を反映。
 *
 * 構成:
 *  - ヘッダー (1行): 事件番号・当事者・決済日・残り日数バッジ・進捗
 *  - 左メイン: 「超過 / 今日 / 今週 / 待ち / 完了」のセクション縦並び
 *      各タスクはカード。担当者は色付き丸+頭文字
 *  - 右パネル (固定): クリックしたタスクの詳細（書類リスト・操作）
 */

type DocItem = {
    id: number;
    name: string;
    kind: string;
    state: string;
    state_label: string;
    is_held: boolean;
};

type TaskItem = {
    id: number;
    role_label: string | null;
    name: string;
    state: string;
    planned_date: string | null;
    days_left: number | null;
    assignee: { id: number; name: string } | null;
    is_blocked: boolean;
    blocked_by: string[];
    documents: DocItem[];
    documents_total: number;
    documents_done: number;
};

type Props = {
    matter: {
        id: number;
        matter_number: string;
        settlement_at: string | null;
        progress: { done: number; total: number; percent: number };
        parties: { id: number; role_code: number; name: string }[];
        tasks: TaskItem[];
    };
    staff: { id: number; name: string }[];
};

const STAFF_TONE: Record<string, string> = {
    '田中': 'bg-blue-500',
    '佐藤': 'bg-emerald-500',
    '鈴木': 'bg-violet-500',
    '伊藤': 'bg-amber-500',
    '高橋': 'bg-rose-500',
};

function avatarTone(name: string): string {
    return STAFF_TONE[name] ?? 'bg-gray-400';
}

function daysLeftFromNow(settlementAt: string | null): number | null {
    if (!settlementAt) return null;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const s = new Date(settlementAt);
    s.setHours(0, 0, 0, 0);
    return Math.round((s.getTime() - now.getTime()) / 86400000);
}

const STATE_DOT: Record<string, string> = {
    completed: 'bg-emerald-500', confirmed: 'bg-emerald-500',
    in_progress: 'bg-blue-500', requested: 'bg-blue-500',
    received: 'bg-cyan-500', drafted: 'bg-amber-500',
    overdue: 'bg-red-500', not_started: 'border border-gray-300',
};
const STATE_LABEL: Record<string, string> = {
    completed: '完了', confirmed: '確定',
    in_progress: '進行中', requested: '依頼済',
    received: '受領', drafted: 'ドラフト',
    overdue: '超過', not_started: '未着手',
};

export default function MatterShow({ matter, staff }: Props) {
    // 仕分け
    const buckets = useMemo(() => {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const weekEnd = new Date(today);
        weekEnd.setDate(today.getDate() + 7);

        const overdue: TaskItem[] = [];
        const todayList: TaskItem[] = [];
        const week: TaskItem[] = [];
        const blocked: TaskItem[] = [];
        const done: TaskItem[] = [];

        for (const t of matter.tasks) {
            if (t.state === 'completed') { done.push(t); continue; }
            if (t.is_blocked)             { blocked.push(t); continue; }
            if (t.state === 'overdue')    { overdue.push(t); continue; }
            if (t.days_left !== null) {
                if (t.days_left <= 0)        todayList.push(t);
                else if (t.days_left <= 7)   week.push(t);
                else                         blocked.push(t); // 先のタスクは「待ち」扱い
            }
        }
        return { overdue, today: todayList, week, blocked, done };
    }, [matter.tasks]);

    const [selectedId, setSelectedId] = useState<number>(
        buckets.today[0]?.id ?? buckets.overdue[0]?.id ?? matter.tasks[0]?.id ?? 0,
    );
    const selected = matter.tasks.find((t) => t.id === selectedId) ?? null;

    const partiesSummary = useMemo(() => {
        const sellers = matter.parties.filter((p) => p.role_code === 1).map((p) => p.name);
        const buyers = matter.parties.filter((p) => p.role_code === 2).map((p) => p.name);
        return `${sellers.join('・') || '—'} → ${buyers.join('・') || '—'}`;
    }, [matter.parties]);

    const settlementDays = daysLeftFromNow(matter.settlement_at);

    // 「待ち」を blocking task でグループ化
    const blockedGrouped = useMemo(() => {
        const groups: Record<string, TaskItem[]> = {};
        for (const t of buckets.blocked) {
            const key = t.blocked_by.length > 0 ? t.blocked_by.join('・') : 'その他';
            (groups[key] = groups[key] ?? []).push(t);
        }
        return groups;
    }, [buckets.blocked]);

    return (
        <AppLayout title={`事件 ${matter.matter_number}`}>
            {/* ヘッダー */}
            <header className="flex h-14 items-center gap-4 border-b bg-white px-6">
                <span className="font-mono text-base font-semibold text-brand-navy">
                    {matter.matter_number}
                </span>
                <span className="text-gray-400">·</span>
                <span className="text-base">{partiesSummary}</span>
                <span className="text-gray-400">·</span>
                {matter.settlement_at && (
                    <span className="text-sm text-gray-600">
                        {new Date(matter.settlement_at).toLocaleDateString('ja-JP', {
                            month: '2-digit', day: '2-digit', weekday: 'short',
                        })} 決済
                    </span>
                )}
                {settlementDays !== null && (
                    <span className={cn(
                        'ml-auto inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold',
                        settlementDays < 0 && 'bg-red-100 text-red-800',
                        settlementDays >= 0 && settlementDays <= 3 && 'bg-red-100 text-red-700',
                        settlementDays > 3 && settlementDays <= 7 && 'bg-amber-100 text-amber-800',
                        settlementDays > 7 && 'bg-gray-100 text-gray-700',
                    )}>
                        <span className="text-lg">⏱</span>
                        {settlementDays < 0 ? `${Math.abs(settlementDays)}日経過` : `あと ${settlementDays} 日`}
                    </span>
                )}
                <span className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
                    {matter.progress.done}/{matter.progress.total} 完了 ({matter.progress.percent}%)
                </span>
            </header>

            {/* メイン */}
            <div className="flex flex-1 overflow-hidden">

                {/* 左: タスクセクション */}
                <div className="flex-1 overflow-y-auto p-6">
                    <div className="mx-auto max-w-3xl space-y-6">

                        {buckets.overdue.length > 0 && (
                            <Section title="超過" tone="red" count={buckets.overdue.length} icon="⚠">
                                {buckets.overdue.map((t) => (
                                    <BigCard key={t.id} task={t} accent="red" onClick={() => setSelectedId(t.id)} active={t.id === selectedId} />
                                ))}
                            </Section>
                        )}

                        {buckets.today.length > 0 && (
                            <Section title="今日" tone="amber" count={buckets.today.length} icon="●">
                                {buckets.today.map((t) => (
                                    <BigCard key={t.id} task={t} accent="amber" onClick={() => setSelectedId(t.id)} active={t.id === selectedId} />
                                ))}
                            </Section>
                        )}

                        {buckets.week.length > 0 && (
                            <Section title="今週" tone="gray" count={buckets.week.length} icon="○">
                                {buckets.week.map((t) => (
                                    <SmallCard key={t.id} task={t} onClick={() => setSelectedId(t.id)} active={t.id === selectedId} />
                                ))}
                            </Section>
                        )}

                        {Object.keys(blockedGrouped).length > 0 && (
                            <section>
                                <SectionHeader title="待ち" tone="gray" count={buckets.blocked.length} icon="⏸" />
                                <div className="space-y-3 text-sm text-gray-500">
                                    {Object.entries(blockedGrouped).map(([key, tasks]) => (
                                        <div key={key}>
                                            <div className="mb-1 text-[11px] uppercase tracking-wide text-gray-400">
                                                ↑ {key} 完了待ち
                                            </div>
                                            <div className="ml-4 space-y-0.5">
                                                {tasks.map((t) => (
                                                    <BlockedRow key={t.id} task={t} onClick={() => setSelectedId(t.id)} active={t.id === selectedId} />
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {buckets.done.length > 0 && (
                            <section>
                                <details>
                                    <summary className="flex cursor-pointer list-none items-baseline gap-2 text-sm font-bold text-emerald-700">
                                        <span className="text-base">✓</span> 完了
                                        <span className="text-xs font-normal text-gray-400">{buckets.done.length}件</span>
                                        <span className="text-[10px] text-gray-400">▾</span>
                                    </summary>
                                    <div className="mt-2 space-y-1 opacity-60">
                                        {buckets.done.map((t) => (
                                            <DoneRow key={t.id} task={t} onClick={() => setSelectedId(t.id)} />
                                        ))}
                                    </div>
                                </details>
                            </section>
                        )}
                    </div>
                </div>

                {/* 右パネル: 詳細 */}
                <aside className="w-96 shrink-0 overflow-y-auto border-l bg-white">
                    {selected ? <DetailPanel task={selected} /> : (
                        <div className="p-6 text-sm text-gray-400">タスクを選択</div>
                    )}
                </aside>
            </div>
        </AppLayout>
    );
}

// ====== コンポーネント ======

function Section({ title, tone, count, icon, children }: {
    title: string; tone: 'red' | 'amber' | 'gray';
    count: number; icon: string; children: React.ReactNode;
}) {
    return (
        <section>
            <SectionHeader title={title} tone={tone} count={count} icon={icon} />
            <div className="space-y-2">{children}</div>
        </section>
    );
}

function SectionHeader({ title, tone, count, icon }: {
    title: string; tone: 'red' | 'amber' | 'gray'; count: number; icon: string;
}) {
    const toneCls = tone === 'red' ? 'text-red-700' : tone === 'amber' ? 'text-amber-700' : 'text-gray-600';
    return (
        <h2 className={cn('mb-2 flex items-baseline gap-2 text-sm font-bold', toneCls)}>
            <span className="text-base">{icon}</span> {title}
            <span className="text-xs font-normal text-gray-500">{count}件</span>
        </h2>
    );
}

function Avatar({ name, size = 'md' }: { name: string | null; size?: 'sm' | 'md' | 'lg' }) {
    const sz = size === 'lg' ? 'size-10 text-sm' : size === 'sm' ? 'size-5 text-[10px]' : 'size-9 text-sm';
    return (
        <div className={cn('flex shrink-0 items-center justify-center rounded-full font-bold text-white', sz, avatarTone(name ?? ''))}>
            {(name ?? '?').slice(0, 1)}
        </div>
    );
}

function BigCard({ task, accent, onClick, active }: {
    task: TaskItem; accent: 'red' | 'amber'; onClick: () => void; active: boolean;
}) {
    const border = accent === 'red' ? 'border-l-red-500' : 'border-l-amber-400';
    const ring = accent === 'red' ? 'ring-1 ring-red-100' : '';
    const docPercent = task.documents_total > 0
        ? Math.round((task.documents_done / task.documents_total) * 100) : 0;

    return (
        <button
            onClick={onClick}
            className={cn(
                'block w-full rounded-lg border-l-4 bg-white p-4 text-left shadow-sm hover:shadow',
                border, ring, active && 'ring-2 ring-blue-300',
            )}
        >
            <div className="flex items-center gap-3">
                <Avatar name={task.assignee?.name ?? null} />
                <div className="flex-1 min-w-0">
                    <div className="font-semibold truncate">{task.name}</div>
                    {task.documents_total > 0 && (
                        <div className="mt-0.5 flex items-center gap-1.5">
                            <div className="h-1.5 w-32 overflow-hidden rounded-full bg-gray-200">
                                <div className="h-full bg-emerald-500" style={{ width: `${docPercent}%` }} />
                            </div>
                            <span className="text-[11px] text-gray-500">
                                {task.documents_done}/{task.documents_total}
                            </span>
                        </div>
                    )}
                </div>
                <div className="text-right">
                    {accent === 'red' && task.days_left !== null && (
                        <div className="text-lg font-bold text-red-600">{task.days_left}日</div>
                    )}
                    <div className="text-[10px] text-gray-400">{task.planned_date}</div>
                </div>
            </div>
        </button>
    );
}

function SmallCard({ task, onClick, active }: { task: TaskItem; onClick: () => void; active: boolean }) {
    return (
        <button
            onClick={onClick}
            className={cn(
                'block w-full rounded border-l-2 border-l-gray-300 bg-white p-3 text-left hover:bg-gray-50',
                active && 'ring-2 ring-blue-300',
            )}
        >
            <div className="flex items-center gap-3 text-sm">
                <Avatar name={task.assignee?.name ?? null} size="sm" />
                <span className="flex-1 truncate">{task.name}</span>
                {task.documents_total > 0 && (
                    <span className="text-xs text-gray-500">{task.documents_total}書類</span>
                )}
                <span className="w-12 text-right text-xs text-amber-600">{task.planned_date}</span>
            </div>
        </button>
    );
}

function BlockedRow({ task, onClick, active }: { task: TaskItem; onClick: () => void; active: boolean }) {
    return (
        <button
            onClick={onClick}
            className={cn(
                'flex w-full items-center gap-2 rounded px-2 py-1 text-left hover:bg-white',
                active && 'bg-white ring-1 ring-blue-200',
            )}
        >
            <Avatar name={task.assignee?.name ?? null} size="sm" />
            <span className="flex-1 truncate">{task.name}</span>
            <span className="text-xs">{task.planned_date}</span>
        </button>
    );
}

function DoneRow({ task, onClick }: { task: TaskItem; onClick: () => void }) {
    return (
        <button
            onClick={onClick}
            className="flex w-full items-center gap-3 rounded bg-emerald-50/50 p-2 text-left text-sm hover:bg-emerald-50"
        >
            <Avatar name={task.assignee?.name ?? null} size="sm" />
            <span className="flex-1 truncate line-through">{task.name}</span>
            <span className="text-xs text-gray-500">{task.planned_date}</span>
        </button>
    );
}

function DetailPanel({ task }: { task: TaskItem }) {
    return (
        <div>
            <div className="border-b bg-gray-50 px-6 py-4">
                <div className="text-xs text-gray-500">{task.role_label ?? '—'}</div>
                <h3 className="mt-1 text-lg font-bold">{task.name}</h3>
                {task.is_blocked && (
                    <div className="mt-2 inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">
                        ⏸ {task.blocked_by.join('・')} 完了待ち
                    </div>
                )}
            </div>

            <div className="space-y-4 p-6">
                <div className="flex items-center gap-3">
                    <Avatar name={task.assignee?.name ?? null} size="lg" />
                    <div>
                        <div className="text-xs text-gray-500">担当</div>
                        <div className="font-medium">{task.assignee?.name ?? '未割当'}</div>
                    </div>
                    <div className="ml-auto text-right">
                        <div className="text-xs text-gray-500">期日</div>
                        <div className={cn('font-mono', task.state === 'overdue' && 'font-bold text-red-600')}>
                            {task.planned_date ?? '—'}
                        </div>
                    </div>
                </div>

                {task.documents.length > 0 ? (
                    <div>
                        <div className="mb-2 flex items-center justify-between">
                            <div className="text-xs font-semibold text-gray-600">
                                書類 ({task.documents_done}/{task.documents_total})
                            </div>
                            <div className="h-1.5 w-20 overflow-hidden rounded-full bg-gray-200">
                                <div
                                    className="h-full bg-emerald-500"
                                    style={{ width: `${(task.documents_done / task.documents_total) * 100}%` }}
                                />
                            </div>
                        </div>
                        <ul className="divide-y divide-gray-100 rounded border bg-white text-sm">
                            {task.documents.map((d) => (
                                <li key={d.id} className="flex items-center gap-3 px-3 py-2">
                                    <span className={cn('size-2 shrink-0 rounded-full', STATE_DOT[d.state])} />
                                    <span className="flex-1 truncate">
                                        {d.name}
                                        {d.is_held && <span className="ml-1 text-xs text-emerald-600">●預</span>}
                                    </span>
                                    <select
                                        value={d.state}
                                        onChange={(e) =>
                                            router.patch(
                                                `/documents/${d.id}`,
                                                { state: e.target.value },
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="rounded border-0 bg-transparent text-xs text-gray-600 focus:ring-1 focus:ring-brand-blue"
                                    >
                                        {d.kind === 'collection' && (
                                            <>
                                                <option value="not_started">未着手</option>
                                                <option value="requested">依頼済</option>
                                                <option value="received">受領</option>
                                                <option value="confirmed">確定</option>
                                            </>
                                        )}
                                        {d.kind === 'creation' && (
                                            <>
                                                <option value="not_started">未着手</option>
                                                <option value="drafted">ドラフト</option>
                                                <option value="confirmed">確定</option>
                                            </>
                                        )}
                                    </select>
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <div className="rounded border bg-gray-50 p-3 text-xs text-gray-400">
                        紐付く書類はありません
                    </div>
                )}

                <div className="flex gap-2">
                    <button
                        onClick={() =>
                            router.post(`/tasks/${task.id}/complete`, {}, { preserveScroll: true })
                        }
                        disabled={task.is_blocked}
                        className={cn(
                            'flex-1 rounded bg-emerald-500 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-600',
                            task.is_blocked && 'cursor-not-allowed opacity-40',
                        )}
                    >
                        ✓ 完了にする
                    </button>
                    <button className="rounded border bg-white px-3 py-2 text-sm hover:bg-gray-50">
                        ⋯
                    </button>
                </div>
            </div>
        </div>
    );
}
