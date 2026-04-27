import { router } from '@inertiajs/react';
import { cn } from '@/Lib/cn';

/**
 * ロール別レーンビュー（軽量版）。
 *
 * 各ロールに 1 つの統合リスト（タスクと書類が同じ行型で並ぶ）。
 * 余計な ring/badge を排してテーブルライクに密度を上げる。
 *  - 状態ドット | 名前 | 種別 | (担当者|state select) | 期日
 */

type Doc = {
    id: number;
    code: string;
    name: string;
    kind: string;             // collection | creation
    requested_from_role: number | null;
    state: string;            // not_started | requested | received | drafted | confirmed
    is_held: boolean;
    deadline: string | null;
    days_left: number | null;
    is_overdue: boolean;
};

type TaskLite = {
    id: number;
    role_code: number | null;
    name: string;
    state: string;            // completed | in_progress | overdue | not_started
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

const roleDot: Record<number, string> = {
    0: 'bg-gray-400',
    1: 'bg-orange-400',
    2: 'bg-blue-400',
    3: 'bg-rose-400',
    4: 'bg-violet-400',
    5: 'bg-emerald-400',
};

function dueClass(d: number | null | undefined): string {
    if (d === null || d === undefined) return 'text-gray-400';
    if (d < 0) return 'font-bold text-red-600';
    if (d <= 2) return 'text-red-600';
    if (d <= 5) return 'text-amber-600';
    return 'text-gray-400';
}

function dueLabel(d: number | null | undefined, deadline: string | null): string {
    const base = deadline ?? '';
    if (d === null || d === undefined) return base;
    if (d < 0) return `${base} ${d}`;
    return base;
}

function stateDot(state: string): string {
    switch (state) {
        case 'completed':
        case 'confirmed':
            return 'bg-emerald-500';
        case 'in_progress':
        case 'requested':
            return 'bg-blue-500';
        case 'received':
            return 'bg-cyan-500';
        case 'drafted':
            return 'bg-amber-500';
        case 'overdue':
            return 'bg-red-500';
        default:
            return 'border border-gray-300';
    }
}

export function RoleLanes({ lanes, documents, tasks }: Props) {
    return (
        <div className="mx-auto max-w-5xl space-y-2 p-4">
            {lanes.map((lane) => {
                const laneDocs = documents.filter((d) => d.requested_from_role === lane.role);
                const laneTasks = tasks.filter((t) => t.role_code === lane.role);
                const hasOverdue = lane.doc_overdue > 0;

                return (
                    <section
                        key={lane.role}
                        className={cn('rounded bg-white', hasOverdue && 'ring-1 ring-red-100')}
                    >
                        <header className="flex items-baseline gap-3 border-b px-4 py-2">
                            {lane.role !== 0 && (
                                <span className={cn('size-2 rounded-full', roleDot[lane.role])} />
                            )}
                            <h3 className="text-sm font-semibold">{lane.role_label}</h3>
                            <span className="text-xs text-gray-400">
                                {lane.task_count > 0 && `${lane.task_done}/${lane.task_count} タスク`}
                                {lane.task_count > 0 && lane.doc_count > 0 && ' · '}
                                {lane.doc_count > 0 && `${lane.doc_done}/${lane.doc_count} 書類`}
                            </span>
                            {hasOverdue && (
                                <span className="ml-auto text-xs font-semibold text-red-600">
                                    ⚠ 超過 {lane.doc_overdue}
                                </span>
                            )}
                        </header>

                        <ul className="divide-y divide-gray-100">
                            {laneTasks.map((t) => (
                                <li key={`t-${t.id}`} className="flex items-center gap-3 px-4 py-1.5 text-sm">
                                    <span className={cn('size-2 shrink-0 rounded-full', stateDot(t.state))} />
                                    <span className={cn('flex-1 truncate', t.state === 'not_started' && 'text-gray-600')}>
                                        {t.name}
                                    </span>
                                    <span className="w-12 text-xs text-gray-400">タスク</span>
                                    <span className="w-16 text-xs text-gray-500">
                                        {t.assignee?.name ?? '未割'}
                                    </span>
                                    <span className={cn('w-24 text-right font-mono text-xs', dueClass(t.days_left))}>
                                        {dueLabel(t.days_left, t.planned_date)}
                                    </span>
                                </li>
                            ))}

                            {laneDocs.map((d) => (
                                <li key={`d-${d.id}`} className="flex items-center gap-3 px-4 py-1.5 text-sm">
                                    <span className={cn('size-2 shrink-0 rounded-full', stateDot(d.state))} />
                                    <span className={cn('flex-1 truncate', d.state === 'not_started' && 'text-gray-600')}>
                                        {d.name}
                                        {d.is_held && (
                                            <span className="ml-1 text-[10px] text-emerald-600">●預</span>
                                        )}
                                    </span>
                                    <span className="w-12 text-xs text-gray-400">
                                        {d.kind === 'collection' ? '収集' : '作成'}
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
                                        className="w-16 rounded border-0 bg-transparent text-xs text-gray-500 focus:ring-1 focus:ring-brand-blue"
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
                                    <span className={cn('w-24 text-right font-mono text-xs', dueClass(d.days_left))}>
                                        {dueLabel(d.days_left, d.deadline)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                );
            })}
        </div>
    );
}
