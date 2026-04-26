import { useMemo, useState } from 'react';
import { AppLayout } from '@/Layouts/AppLayout';
import { SummaryBar } from '@/Components/SummaryBar';
import { MiniProgressBar } from '@/Components/MiniProgressBar';
import { TaskStepper } from '@/Components/TaskStepper';
import { InlineSelect } from '@/Components/InlineSelect';
import type { TaskState } from '@/Types';

/**
 * 3.1 事件詳細画面。3層構造ヘッダー + 2.3.5 3カラムレイアウト。
 *  - 第1層: SummaryBar
 *  - 第2層: TaskStepper (sticky)
 *  - 第3層: MiniProgressBar (右上 fixed)
 *  - 左サイド: 工程ナビ（クリックで該当工程にジャンプ）
 *  - 中央: 選択中の工程の入力エリア
 *  - 右サイド: 当事者・物件・コメント
 */

type Party = {
    id: number;
    role_code: number;
    role_label: string;
    name: string;
    name_kana?: string;
    address?: string;
    tel?: string;
    entity_type: string;
};

type Property = {
    id: number;
    location: string;
    parcel_number?: string;
    land_category?: string;
    area?: string;
};

type Task = {
    id: number;
    task_code: number;
    role_code: number;
    role_label: string;
    name: string;
    status: string;
    state: TaskState;
    planned_date: string | null;
    days_left: number | null;
    completed_at: string | null;
    assignee: { id: number; name: string } | null;
    assigned_by: string | null;
    comments_count: number;
};

type Matter = {
    id: number;
    matter_number: string;
    job_type: number | null;
    job_type_label: string | null;
    settlement_at: string | null;
    received_at: string | null;
    progress: { done: number; total: number; percent: number };
    main_user: { id: number; name: string } | null;
    drive_folder_id: string | null;
    next_task: {
        id: number;
        name: string;
        assignee: string | null;
        planned_date: string | null;
        days_left: number | null;
    } | null;
    parties: Party[];
    properties: Property[];
    tasks: Task[];
};

type Props = {
    matter: Matter;
    unassigned: Record<string, number>;
    staff: { id: number; name: string }[];
};

export default function MatterShow({ matter, staff }: Props) {
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(
        matter.next_task?.id ?? matter.tasks[0]?.id ?? null,
    );
    const selected = useMemo(
        () => matter.tasks.find((t) => t.id === selectedTaskId) ?? null,
        [matter.tasks, selectedTaskId],
    );

    const partiesSummary = useMemo(() => {
        const sellers = matter.parties.filter((p) => p.role_code === 1).map((p) => p.name);
        const buyers = matter.parties.filter((p) => p.role_code === 2).map((p) => p.name);
        return `${sellers.join('・') || '—'} → ${buyers.join('・') || '—'}`;
    }, [matter.parties]);

    return (
        <AppLayout title={`事件 ${matter.matter_number}`}>
            <MiniProgressBar percent={matter.progress.percent} />

            <div className="sticky top-0 z-30 bg-white">
                <SummaryBar
                    matterNumber={matter.matter_number}
                    parties={partiesSummary}
                    settlementAt={matter.settlement_at}
                    jobTypeLabel={matter.job_type_label}
                    progress={matter.progress}
                    nextTask={matter.next_task ?? undefined}
                />
                <div className="border-b bg-white px-4 py-3">
                    <TaskStepper
                        matterId={matter.id}
                        steps={matter.tasks.map((t) => ({
                            id: t.id,
                            name: t.name,
                            state: t.state,
                            assignee: t.assignee?.name,
                            planned_date: t.planned_date,
                            role_code: t.role_code,
                        }))}
                        nextTaskId={matter.next_task?.id ?? null}
                    />
                </div>
            </div>

            <div className="grid grid-cols-[220px_1fr_300px] gap-0">
                {/* 左サイド: 工程ナビ */}
                <aside className="border-r bg-white p-2 text-sm">
                    <div className="mb-2 px-2 text-xs font-semibold text-gray-500">工程</div>
                    <ul>
                        {matter.tasks.map((t) => (
                            <li key={t.id}>
                                <button
                                    onClick={() => setSelectedTaskId(t.id)}
                                    className={`flex w-full items-center justify-between rounded px-2 py-1.5 text-left hover:bg-gray-50 ${
                                        selectedTaskId === t.id ? 'bg-blue-50 font-medium' : ''
                                    }`}
                                >
                                    <span className="truncate">{t.name}</span>
                                    <span className="ml-2 text-xs text-gray-400">{t.role_label}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </aside>

                {/* 中央: 選択中工程の入力エリア */}
                <section className="p-6">
                    {selected ? (
                        <article id={`task-${selected.id}`}>
                            <header className="mb-4 flex items-center justify-between">
                                <h2 className="text-xl font-semibold">{selected.name}</h2>
                                <span className="text-xs text-gray-500">task #{selected.id}</span>
                            </header>

                            <div className="mb-6 grid grid-cols-2 gap-4 text-sm">
                                <Field label="担当者">
                                    <InlineSelect
                                        url={`/tasks/${selected.id}`}
                                        field="assignee_user_id"
                                        value={selected.assignee?.id ?? ''}
                                        placeholder="未割当"
                                        options={staff.map((s) => ({ value: s.id, label: s.name }))}
                                    />
                                </Field>
                                <Field label="進行状況">
                                    <InlineSelect
                                        url={`/tasks/${selected.id}`}
                                        field="status"
                                        value={selected.status}
                                        options={[
                                            { value: 'not_started', label: '未着手' },
                                            { value: 'in_progress', label: '進行中' },
                                            { value: 'completed', label: '完了' },
                                            { value: 'awaiting_review', label: '確認待ち' },
                                        ]}
                                    />
                                </Field>
                                <Field label="期日">
                                    <span className="font-mono">{selected.planned_date ?? '—'}</span>
                                </Field>
                                <Field label="残り">
                                    {selected.days_left === null ? (
                                        '—'
                                    ) : selected.days_left < 0 ? (
                                        <span className="font-bold text-red-600">{Math.abs(selected.days_left)}日超過</span>
                                    ) : (
                                        <span>{selected.days_left}日</span>
                                    )}
                                </Field>
                            </div>

                            <details className="mb-4 rounded border bg-gray-50 p-3">
                                <summary className="cursor-pointer text-sm font-medium">書類生成</summary>
                                <p className="mt-2 text-xs text-gray-600">
                                    工程に紐付くテンプレ群を表示。/matters/{matter.id}/documents/[template]/form に遷移してチェックボックス UI で条件指定。
                                </p>
                            </details>

                            <details className="rounded border bg-gray-50 p-3">
                                <summary className="cursor-pointer text-sm font-medium">
                                    コメント ({selected.comments_count})
                                </summary>
                                <p className="mt-2 text-xs text-gray-600">
                                    タスク単位コメントは task_comments テーブル。投稿で関係者全員に通知。
                                </p>
                            </details>
                        </article>
                    ) : (
                        <p className="text-gray-500">工程を選択してください。</p>
                    )}
                </section>

                {/* 右サイド: 事件メタ */}
                <aside className="border-l bg-white p-4 text-sm">
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
                            <div key={p.id} className="mb-2 text-xs text-gray-700">
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

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <div className="text-xs text-gray-500">{label}</div>
            <div className="mt-0.5">{children}</div>
        </div>
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
