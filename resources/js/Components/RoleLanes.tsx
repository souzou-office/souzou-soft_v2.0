import { router } from '@inertiajs/react';
import { cn } from '@/Lib/cn';

/**
 * ロール別レーンビュー。事件詳細のメインコンテンツ。
 *
 * 縦軸 = ロール（共通 / 売主 / 買主 / 抹消銀行 / 設定銀行 / 仲介）
 * 横軸 = タスクと書類が並列に流れる
 *
 * 「売主側はOK、抹消銀行が止まってる」「設定銀行は決済直前だからまだ動かなくていい」
 * が一目で分かるよう、ロール単位で列を切ってその中に並列項目を並べる。
 */

type Doc = {
    id: number;
    code: string;
    name: string;
    kind: string;
    kind_label: string;
    requested_from_role: number | null;
    delivery_method: string;
    delivery_label: string;
    state: string;
    state_label: string;
    is_held: boolean;
    deadline: string | null;
    days_left: number | null;
    is_overdue: boolean;
};

type TaskLite = {
    id: number;
    role_code: number | null;
    name: string;
    state: string;
    planned_date: string | null;
    days_left: number | null;
    assignee: { id: number; name: string } | null;
};

type Lane = {
    role: number;
    role_label: string;
    task_count: number;
    task_done: number;
    doc_count: number;
    doc_done: number;
    doc_overdue: number;
};

type Props = {
    lanes: Lane[];
    documents: Doc[];
    tasks: TaskLite[];
};

const stateBadge: Record<string, string> = {
    not_started: 'bg-gray-100 text-gray-600',
    requested:   'bg-blue-100 text-blue-700',
    received:    'bg-cyan-100 text-cyan-700',
    drafted:     'bg-amber-100 text-amber-800',
    confirmed:   'bg-emerald-100 text-emerald-800',
};

const roleAccent: Record<number, string> = {
    0: 'border-l-gray-400',   // 共通
    1: 'border-l-orange-400', // 売主
    2: 'border-l-blue-400',   // 買主
    3: 'border-l-rose-400',   // 抹消銀行
    4: 'border-l-violet-400', // 設定銀行
    5: 'border-l-emerald-400',// 仲介
};

function dueClass(d: number | null): string {
    if (d === null) return 'text-gray-500';
    if (d < 0) return 'font-bold text-red-600';
    if (d <= 2) return 'text-red-600';
    if (d <= 5) return 'text-amber-600';
    return 'text-gray-500';
}

function dueLabel(d: number | null): string {
    if (d === null) return '';
    if (d < 0) return ` ${Math.abs(d)}日超過`;
    if (d === 0) return ' 本日';
    return ` あと${d}日`;
}

export function RoleLanes({ lanes, documents, tasks }: Props) {
    return (
        <div className="space-y-3 p-4">
            {lanes.map((lane) => {
                const laneDocs  = documents.filter((d) => d.requested_from_role === lane.role);
                const laneTasks = tasks.filter((t) => t.role_code === lane.role);

                return (
                    <section
                        key={lane.role}
                        className={cn(
                            'rounded border-l-4 bg-white shadow-sm',
                            roleAccent[lane.role] ?? 'border-l-gray-300',
                        )}
                    >
                        <header className="flex items-center justify-between border-b px-3 py-2">
                            <div className="flex items-center gap-3">
                                <h3 className="text-sm font-semibold">{lane.role_label}</h3>
                                <span className="text-xs text-gray-500">
                                    タスク {lane.task_done}/{lane.task_count}
                                    {' · '}
                                    書類 {lane.doc_done}/{lane.doc_count}
                                    {lane.doc_overdue > 0 && (
                                        <span className="ml-2 text-red-600">⚠超過 {lane.doc_overdue}</span>
                                    )}
                                </span>
                            </div>
                        </header>

                        <div className="grid grid-cols-2 gap-3 p-3">
                            {/* タスク列 */}
                            <div>
                                <div className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                    タスク
                                </div>
                                {laneTasks.length === 0 ? (
                                    <p className="text-xs text-gray-400">—</p>
                                ) : (
                                    <ul className="space-y-1 text-sm">
                                        {laneTasks.map((t) => (
                                            <li key={t.id} className="flex items-center gap-2">
                                                <span
                                                    className={cn(
                                                        'inline-block size-2 shrink-0 rounded-full',
                                                        t.state === 'completed' && 'bg-emerald-500',
                                                        t.state === 'in_progress' && 'bg-blue-500',
                                                        t.state === 'overdue' && 'bg-red-500',
                                                        t.state === 'not_started' && 'border border-gray-300',
                                                    )}
                                                />
                                                <span className="flex-1 truncate">{t.name}</span>
                                                <span className="text-xs text-gray-500">{t.assignee?.name ?? '未割'}</span>
                                                <span className={`w-24 text-right text-xs ${dueClass(t.days_left)}`}>
                                                    {t.planned_date}
                                                    {dueLabel(t.days_left)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            {/* 書類列 */}
                            <div>
                                <div className="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                    書類
                                </div>
                                {laneDocs.length === 0 ? (
                                    <p className="text-xs text-gray-400">—</p>
                                ) : (
                                    <ul className="space-y-1 text-sm">
                                        {laneDocs.map((d) => (
                                            <li key={d.id} className="flex items-center gap-2">
                                                <span className={cn(
                                                    'inline-block w-12 shrink-0 rounded px-1 text-center text-[10px]',
                                                    d.kind === 'collection' ? 'bg-cyan-50 text-cyan-700' : 'bg-violet-50 text-violet-700',
                                                )}>
                                                    {d.kind_label}
                                                </span>
                                                <span className="flex-1 truncate">
                                                    {d.name}
                                                    {d.is_held && (
                                                        <span className="ml-1 rounded bg-emerald-100 px-1 text-[10px] text-emerald-700">預</span>
                                                    )}
                                                </span>
                                                <select
                                                    value={d.state}
                                                    onChange={(e) => {
                                                        router.patch(
                                                            `/documents/${d.id}`,
                                                            { state: e.target.value },
                                                            { preserveScroll: true },
                                                        );
                                                    }}
                                                    className={cn(
                                                        'rounded border-0 px-1 py-0 text-xs ring-1 ring-gray-200',
                                                        stateBadge[d.state],
                                                    )}
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
                                                <span className={`w-24 text-right text-xs ${dueClass(d.days_left)}`}>
                                                    {d.deadline}
                                                    {dueLabel(d.days_left)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
