import { useState } from 'react';
import { router } from '@inertiajs/react';
import { cn } from '@/Lib/cn';

/**
 * ロール別レーン（task-first）。
 *
 * 各ロール内に「タスク」を並べる。書類は task の sub-artifact なので
 * タスク行に折りたたまれており、▸ クリックで展開して中身が見える。
 *
 * 1 つのタスク = 1 つの作業単位（一括受領・一括作成）。書類が散発
 * タスクに分裂しない。
 */

type DocItem = {
    id: number;
    code: string;
    name: string;
    kind: string;
    state: string;
    state_label: string;
    is_held: boolean;
    deadline: string | null;
};

type TaskItem = {
    id: number;
    task_code: number;
    role_code: number | null;
    name: string;
    state: string;
    planned_date: string | null;
    days_left: number | null;
    assignee: { id: number; name: string } | null;
    documents: DocItem[];
    documents_total: number;
    documents_done: number;
};

type Lane = {
    role: number;
    role_label: string;
    task_count: number;
    task_done: number;
    task_overdue: number;
};

type Props = {
    lanes: Lane[];
    tasks: TaskItem[];
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

export function RoleLanes({ lanes, tasks }: Props) {
    return (
        <div className="mx-auto max-w-5xl space-y-2 p-4">
            {lanes.map((lane) => {
                const laneTasks = tasks.filter((t) => t.role_code === lane.role);
                const hasOverdue = lane.task_overdue > 0;

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
                                {lane.task_done}/{lane.task_count} タスク
                            </span>
                            {hasOverdue && (
                                <span className="ml-auto text-xs font-semibold text-red-600">
                                    ⚠ 超過 {lane.task_overdue}
                                </span>
                            )}
                        </header>

                        <ul className="divide-y divide-gray-100">
                            {laneTasks.map((t) => (
                                <TaskRow key={t.id} task={t} />
                            ))}
                        </ul>
                    </section>
                );
            })}
        </div>
    );
}

function TaskRow({ task }: { task: TaskItem }) {
    const [open, setOpen] = useState(false);
    const hasDocs = task.documents_total > 0;

    return (
        <li>
            <div className="flex items-center gap-3 px-4 py-1.5 text-sm">
                <button
                    type="button"
                    onClick={() => hasDocs && setOpen((v) => !v)}
                    className={cn(
                        'inline-flex size-4 shrink-0 items-center justify-center text-xs text-gray-400',
                        !hasDocs && 'invisible',
                    )}
                    aria-label={open ? '閉じる' : '展開'}
                >
                    {open ? '▾' : '▸'}
                </button>

                <span className={cn('size-2 shrink-0 rounded-full', stateDot(task.state))} />

                <span
                    className={cn(
                        'flex-1 truncate',
                        task.state === 'not_started' && 'text-gray-700',
                    )}
                >
                    {task.name}
                </span>

                {hasDocs && (
                    <span className="w-16 text-right text-xs text-gray-500">
                        📎 {task.documents_done}/{task.documents_total}
                    </span>
                )}

                <span className="w-16 text-xs text-gray-500">
                    {task.assignee?.name ?? '未割'}
                </span>

                <span className={cn('w-24 text-right font-mono text-xs', dueClass(task.days_left))}>
                    {dueLabel(task.days_left, task.planned_date)}
                </span>
            </div>

            {open && hasDocs && (
                <ul className="border-t border-gray-100 bg-gray-50">
                    {task.documents.map((d) => (
                        <li
                            key={d.id}
                            className="flex items-center gap-3 px-4 py-1 pl-12 text-xs text-gray-600"
                        >
                            <span className={cn('size-1.5 shrink-0 rounded-full', stateDot(d.state))} />
                            <span className="flex-1 truncate">
                                {d.name}
                                {d.is_held && <span className="ml-1 text-emerald-600">●預</span>}
                            </span>
                            <span className="w-12 text-gray-400">
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
                                className="w-20 rounded border-0 bg-transparent text-xs text-gray-500 focus:ring-1 focus:ring-brand-blue"
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
                            <span className="w-20 text-right font-mono text-gray-400">
                                {d.deadline ?? ''}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </li>
    );
}
