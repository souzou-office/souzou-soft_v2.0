import { Link, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { StatusDot } from '@/Components/StatusDot';
import type { TaskState } from '@/Types';

/**
 * 4.1 マイビュー / 4.3 個別担当者ビュー。
 * 4セクション（期日超過 / 今日 / 明日 / 今週）。
 */
type TaskRow = {
    id: number;
    matter_id: number;
    matter_number: string;
    task_name: string;
    planned_date: string | null;
    days_left: number | null;
    state: TaskState;
    assigned_by: string | null;
    comments_count: number;
};

type Props = {
    user: { id: number; name: string; role: string };
    mine: boolean;
    sections: {
        overdue: TaskRow[];
        today: TaskRow[];
        tomorrow: TaskRow[];
        this_week: TaskRow[];
    };
};

const sectionMeta: { key: keyof Props['sections']; label: string; tone: string }[] = [
    { key: 'overdue', label: '期日超過', tone: 'border-red-300 bg-red-50' },
    { key: 'today', label: '今日', tone: 'border-amber-300 bg-amber-50' },
    { key: 'tomorrow', label: '明日', tone: 'border-gray-200 bg-white' },
    { key: 'this_week', label: '今週', tone: 'border-gray-200 bg-white' },
];

export default function UserDashboard({ user, mine, sections }: Props) {
    return (
        <AppLayout title={mine ? `${user.name} のマイビュー` : `${user.name} の状況`}>
            <div className="space-y-4 p-4">
                {sectionMeta.map(({ key, label, tone }) => (
                    <section key={key} className={`rounded border ${tone}`}>
                        <header className="flex items-center justify-between px-3 py-2">
                            <h2 className="text-sm font-semibold">
                                {label}
                                <span className="ml-2 text-xs text-gray-500">{sections[key].length}件</span>
                            </h2>
                        </header>
                        {sections[key].length === 0 ? (
                            <p className="px-3 pb-2 text-xs text-gray-400">なし</p>
                        ) : (
                            <ul className="divide-y bg-white">
                                {sections[key].map((t) => (
                                    <li key={t.id} className="flex items-center gap-3 px-3 py-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={false}
                                            onChange={() =>
                                                router.post(`/tasks/${t.id}/complete`, {}, { preserveScroll: true })
                                            }
                                            className="rounded border-gray-300"
                                        />
                                        <StatusDot state={t.state} />
                                        <Link
                                            href={`/matters/${t.matter_id}#task-${t.id}`}
                                            className="font-mono text-brand-blue hover:underline"
                                        >
                                            {t.matter_number}
                                        </Link>
                                        <span className="text-gray-700">{t.task_name}</span>
                                        <span className="ml-auto text-xs text-gray-500">
                                            期日 {t.planned_date ?? '—'}
                                            {t.days_left !== null && (
                                                <span className="ml-2">
                                                    {t.days_left < 0
                                                        ? `${Math.abs(t.days_left)}日超過`
                                                        : `あと${t.days_left}日`}
                                                </span>
                                            )}
                                        </span>
                                        {t.assigned_by && (
                                            <span className="text-xs text-gray-400">from {t.assigned_by}</span>
                                        )}
                                        {t.comments_count > 0 && (
                                            <span className="text-xs text-gray-500">💬 {t.comments_count}</span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
