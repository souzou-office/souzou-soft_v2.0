import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { AppLayout } from '@/Layouts/AppLayout';
import { MilestoneBar } from '@/Components/MilestoneBar';
import { cn } from '@/Lib/cn';

/**
 * 事件詳細画面（3カラム + クリックで詳細）。
 *
 * 並列処理は次の3要素で表現:
 *  1. 上部「並走バナー」: 進行中タスクが横カードで並ぶ（クリックで該当に飛ぶ）
 *  2. 左ナビ: ロール別グルーピング、ヘッダーに「●N並走」表示
 *  3. 中央: 選択中タスクの詳細 + 含まれる書類リスト
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
    role_label: string | null;
    milestone_key: string | null;
    name: string;
    status: string;
    state: string;
    planned_date: string | null;
    days_left: number | null;
    completed_at: string | null;
    assignee: { id: number; name: string } | null;
    assigned_by: string | null;
    comments_count: number;
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
    matter: {
        id: number;
        matter_number: string;
        job_type_label: string | null;
        settlement_at: string | null;
        progress: { done: number; total: number; percent: number };
        main_user: { name: string } | null;
        parties: { id: number; role_code: number; role_label: string; name: string; address?: string }[];
        properties: { id: number; location: string; parcel_number?: string }[];
        tasks: TaskItem[];
        milestones: any[];
        lane_summary: Lane[];
        drive_folder_id: string | null;
    };
    staff: { id: number; name: string }[];
};

const roleDot: Record<number, string> = {
    0: 'bg-gray-400', 1: 'bg-orange-400', 2: 'bg-blue-400',
    3: 'bg-rose-400', 4: 'bg-violet-400', 5: 'bg-emerald-400',
};

function stateDot(state: string): string {
    switch (state) {
        case 'completed':
        case 'confirmed':  return 'bg-emerald-500';
        case 'in_progress':
        case 'requested':  return 'bg-blue-500';
        case 'received':   return 'bg-cyan-500';
        case 'drafted':    return 'bg-amber-500';
        case 'overdue':    return 'bg-red-500';
        default:           return 'border border-gray-300';
    }
}

export default function MatterShow({ matter, staff }: Props) {
    const [selectedId, setSelectedId] = useState<number>(
        matter.tasks.find((t) => t.state === 'in_progress')?.id ?? matter.tasks[0]?.id ?? 0,
    );

    const selected = useMemo(
        () => matter.tasks.find((t) => t.id === selectedId) ?? null,
        [matter.tasks, selectedId],
    );

    const inProgressTasks = matter.tasks.filter((t) => t.state === 'in_progress');
    const overdueTasks = matter.tasks.filter((t) => t.state === 'overdue');

    const partiesSummary = useMemo(() => {
        const sellers = matter.parties.filter((p) => p.role_code === 1).map((p) => p.name);
        const buyers = matter.parties.filter((p) => p.role_code === 2).map((p) => p.name);
        return `${sellers.join('・') || '—'} → ${buyers.join('・') || '—'}`;
    }, [matter.parties]);

    return (
        <AppLayout title={`事件 ${matter.matter_number}`}>
            {/* サマリー + マイルストーン */}
            <div className="border-b bg-white">
                <div className="flex h-10 items-center gap-4 px-4 text-sm">
                    <span className="font-mono font-semibold text-brand-navy">{matter.matter_number}</span>
                    <span className="text-gray-700">{partiesSummary}</span>
                    {matter.settlement_at && (
                        <span className="text-gray-500">
                            {new Date(matter.settlement_at).toLocaleString('ja-JP', {
                                month: '2-digit', day: '2-digit', weekday: 'short',
                                hour: '2-digit', minute: '2-digit',
                            })} 決済
                        </span>
                    )}
                    <span className="text-gray-500">{matter.job_type_label}</span>
                    {matter.main_user && (
                        <span className="ml-auto text-gray-500">主担当: {matter.main_user.name}</span>
                    )}
                </div>
                <MilestoneBar milestones={matter.milestones} />
            </div>

            {/* 並走バナー */}
            {(inProgressTasks.length > 0 || overdueTasks.length > 0) && (
                <div className="border-b bg-blue-50/50 px-4 py-2 text-xs">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-semibold text-gray-700">いま並走中:</span>
                        {inProgressTasks.map((t) => (
                            <button
                                key={t.id}
                                onClick={() => setSelectedId(t.id)}
                                className="inline-flex items-center gap-1 rounded bg-white px-2 py-1 ring-1 ring-blue-200 hover:ring-blue-400"
                            >
                                <span className="size-1.5 rounded-full bg-blue-500" />
                                <span className="text-blue-700">{t.name}</span>
                                <span className="text-gray-400">·</span>
                                <span className="text-gray-500">{t.assignee?.name ?? '未割'}</span>
                                <span className="text-gray-400">/{t.planned_date}</span>
                            </button>
                        ))}
                        {overdueTasks.length > 0 && (
                            <>
                                <span className="ml-2 text-red-600">⚠ 超過:</span>
                                {overdueTasks.map((t) => (
                                    <button
                                        key={t.id}
                                        onClick={() => setSelectedId(t.id)}
                                        className="inline-flex items-center gap-1 rounded bg-white px-2 py-1 ring-1 ring-red-200 hover:ring-red-400"
                                    >
                                        <span className="size-1.5 rounded-full bg-red-500" />
                                        <span className="text-red-700">{t.name}</span>
                                        <span className="text-gray-400">·</span>
                                        <span className="text-gray-500">{t.assignee?.name}</span>
                                        {t.days_left !== null && t.days_left < 0 && (
                                            <span className="font-bold text-red-600">{t.days_left}日</span>
                                        )}
                                    </button>
                                ))}
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* 3カラム */}
            <div className="flex h-[calc(100vh-13rem)] overflow-hidden">

                {/* 左: タスクナビ */}
                <aside className="w-72 shrink-0 overflow-y-auto border-r bg-white">
                    {matter.lane_summary.map((lane) => {
                        const tasks = matter.tasks.filter((t) => t.role_code === lane.role);
                        const inProg = tasks.filter((t) => t.state === 'in_progress').length;
                        return (
                            <div key={lane.role} className="border-b">
                                <div className="flex items-center gap-2 bg-gray-50 px-3 py-1.5 text-xs">
                                    {lane.role !== 0 && (
                                        <span className={cn('size-2 rounded-full', roleDot[lane.role])} />
                                    )}
                                    <span className="font-semibold text-gray-700">{lane.role_label}</span>
                                    <span className="text-gray-400">{lane.task_done}/{lane.task_count}</span>
                                    {inProg > 0 && (
                                        <span className="ml-auto text-[10px] text-blue-600">●{inProg}並走</span>
                                    )}
                                    {lane.task_overdue > 0 && (
                                        <span className="ml-auto text-[10px] font-bold text-red-600">
                                            ⚠{lane.task_overdue}
                                        </span>
                                    )}
                                </div>
                                <ul>
                                    {tasks.map((t) => (
                                        <li key={t.id}>
                                            <button
                                                onClick={() => setSelectedId(t.id)}
                                                className={cn(
                                                    'flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-blue-50',
                                                    t.id === selectedId && 'bg-blue-50 font-medium',
                                                )}
                                            >
                                                <span className={cn('size-2 shrink-0 rounded-full', stateDot(t.state))} />
                                                <span className={cn('flex-1 truncate', t.state === 'not_started' && 'text-gray-600')}>
                                                    {t.name}
                                                </span>
                                                {t.documents_total > 0 && (
                                                    <span className="text-[10px] text-gray-400">
                                                        📎{t.documents_done}/{t.documents_total}
                                                    </span>
                                                )}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        );
                    })}
                </aside>

                {/* 中央: 詳細 */}
                <section className="flex-1 overflow-y-auto bg-gray-50 p-6">
                    <div className="mx-auto max-w-2xl">
                        {selected ? <TaskDetail task={selected} staff={staff} /> : (
                            <p className="text-gray-500">タスクを選択してください</p>
                        )}
                    </div>
                </section>

                {/* 右: メタ */}
                <aside className="w-64 shrink-0 overflow-y-auto border-l bg-white p-4 text-sm">
                    <Section title="当事者">
                        {matter.parties.map((p) => (
                            <div key={p.id} className="mb-2 border-b pb-2">
                                <div className="text-xs text-gray-500">{p.role_label}</div>
                                <div className="font-medium">{p.name}</div>
                                {p.address && <div className="text-xs text-gray-500">{p.address}</div>}
                            </div>
                        ))}
                    </Section>
                    <Section title="物件">
                        {matter.properties.map((p) => (
                            <div key={p.id} className="text-xs text-gray-700">
                                {p.location} {p.parcel_number}
                            </div>
                        ))}
                    </Section>
                    {matter.drive_folder_id && (
                        <Section title="Driveフォルダ">
                            <a
                                href={`https://drive.google.com/drive/folders/${matter.drive_folder_id}`}
                                target="_blank"
                                rel="noreferrer"
                                className="text-xs text-brand-blue hover:underline"
                            >
                                Drive で開く ↗
                            </a>
                        </Section>
                    )}
                </aside>
            </div>
        </AppLayout>
    );
}

function TaskDetail({ task, staff }: { task: TaskItem; staff: { id: number; name: string }[] }) {
    return (
        <article>
            <header className="mb-4 flex items-center justify-between">
                <div>
                    <div className="text-xs text-gray-500">{task.role_label}</div>
                    <h2 className="text-xl font-semibold">{task.name}</h2>
                </div>
                <button
                    onClick={() => router.post(`/tasks/${task.id}/complete`, {}, { preserveScroll: true })}
                    className="rounded border bg-white px-3 py-1 text-xs hover:bg-gray-50"
                >
                    完了にする
                </button>
            </header>

            <div className="mb-4 grid grid-cols-2 gap-4 rounded border bg-white p-4 text-sm">
                <div>
                    <div className="text-xs text-gray-500">担当者</div>
                    <select
                        value={task.assignee?.id ?? ''}
                        onChange={(e) =>
                            router.patch(
                                `/tasks/${task.id}`,
                                { assignee_user_id: e.target.value || null },
                                { preserveScroll: true },
                            )
                        }
                        className="mt-0.5 rounded border-gray-200 bg-transparent text-sm"
                    >
                        <option value="">未割当</option>
                        {staff.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <div className="text-xs text-gray-500">進行状況</div>
                    <select
                        value={task.status}
                        onChange={(e) =>
                            router.patch(
                                `/tasks/${task.id}`,
                                { status: e.target.value },
                                { preserveScroll: true },
                            )
                        }
                        className="mt-0.5 rounded border-gray-200 bg-transparent text-sm"
                    >
                        <option value="not_started">未着手</option>
                        <option value="in_progress">進行中</option>
                        <option value="completed">完了</option>
                        <option value="awaiting_review">確認待ち</option>
                    </select>
                </div>
                <div>
                    <div className="text-xs text-gray-500">期日</div>
                    <div className={cn(
                        'mt-0.5 font-mono',
                        task.days_left !== null && task.days_left < 0 && 'font-bold text-red-600',
                    )}>
                        {task.planned_date ?? '—'}
                        {task.days_left !== null && task.days_left < 0 && ` (${task.days_left}日超過)`}
                    </div>
                </div>
                <div>
                    <div className="text-xs text-gray-500">マイルストーン</div>
                    <div className="mt-0.5 text-gray-700">{task.milestone_key ?? '—'}</div>
                </div>
            </div>

            {task.documents.length > 0 ? (
                <div className="mb-4 rounded border bg-white">
                    <header className="flex items-center justify-between border-b px-4 py-2">
                        <div className="text-sm font-semibold">
                            含まれる書類 <span className="ml-1 text-xs text-gray-400">{task.documents.length}件</span>
                        </div>
                        <span className="text-xs text-gray-500">
                            📎 {task.documents_done}/{task.documents_total} 完了
                        </span>
                    </header>
                    <ul className="divide-y divide-gray-100 text-sm">
                        {task.documents.map((d) => (
                            <li key={d.id} className="flex items-center gap-3 px-4 py-1.5">
                                <span className={cn('size-2 shrink-0 rounded-full', stateDot(d.state))} />
                                <span className="flex-1 truncate">
                                    {d.name}
                                    {d.is_held && <span className="ml-1 text-xs text-emerald-600">●預</span>}
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
                                    className="w-24 rounded border-0 bg-transparent text-xs text-gray-600 focus:ring-1 focus:ring-brand-blue"
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
                                <span className="w-16 text-right font-mono text-xs text-gray-400">
                                    {d.deadline ?? ''}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : (
                <div className="rounded border bg-white p-4 text-xs text-gray-400">
                    紐付く書類はありません（独立タスク）
                </div>
            )}

            <div className="mt-4 rounded border bg-white">
                <header className="border-b px-4 py-2 text-sm font-semibold">
                    コメント
                    {task.comments_count > 0 && <span className="ml-1 text-xs text-gray-400">{task.comments_count}件</span>}
                </header>
                <div className="p-4 text-xs text-gray-400">コメントなし</div>
            </div>
        </article>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="mb-4">
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{title}</div>
            {children}
        </div>
    );
}
