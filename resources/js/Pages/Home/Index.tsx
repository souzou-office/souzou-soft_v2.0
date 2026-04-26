import { Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { TaskStepper } from '@/Components/TaskStepper';
import type { MatterRow } from '@/Types';

/**
 * 3.2 ホーム画面の事件一覧。
 * 1行 36px、進捗ステッパー埋め込みで 1画面 20〜30件を視認できる密度を実現。
 */
type Props = {
    matters: {
        data: MatterRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export default function HomeIndex({ matters }: Props) {
    return (
        <AppLayout title="ホーム — 事件一覧">
            <div className="p-4">
                <table className="table-dense w-full border-collapse">
                    <thead className="bg-gray-50 text-xs text-gray-500">
                        <tr className="border-b">
                            <th className="text-left">事件番号</th>
                            <th className="text-left">当事者</th>
                            <th className="text-left">決済日</th>
                            <th className="text-left">業務</th>
                            <th className="text-left">進捗</th>
                            <th className="text-left">%</th>
                            <th className="text-left">次工程</th>
                            <th className="text-left">主担当</th>
                        </tr>
                    </thead>
                    <tbody>
                        {matters.data.map((m) => (
                            <tr key={m.id} className="border-b hover:bg-gray-50">
                                <td className="font-mono">
                                    <Link href={`/matters/${m.id}`} className="text-brand-blue hover:underline">
                                        {m.matter_number}
                                    </Link>
                                </td>
                                <td className="text-gray-700">{m.parties_summary}</td>
                                <td className="text-gray-700">
                                    {m.settlement_at
                                        ? new Date(m.settlement_at).toLocaleString('ja-JP', {
                                              month: '2-digit',
                                              day: '2-digit',
                                              weekday: 'short',
                                              hour: '2-digit',
                                              minute: '2-digit',
                                          })
                                        : '—'}
                                </td>
                                <td className="text-xs text-gray-600">{m.job_type_label ?? '—'}</td>
                                <td>
                                    <TaskStepper
                                        matterId={m.id}
                                        steps={m.stepper}
                                        compact
                                        nextTaskId={m.next_task?.id}
                                    />
                                </td>
                                <td className="text-right tabular-nums">{m.progress.percent}%</td>
                                <td className="max-w-[220px] truncate text-gray-700">
                                    {m.next_task ? (
                                        <span>
                                            {m.next_task.name}
                                            {m.next_task.assignee && (
                                                <span className="ml-1 text-gray-500">({m.next_task.assignee})</span>
                                            )}
                                        </span>
                                    ) : (
                                        <span className="text-gray-400">—</span>
                                    )}
                                </td>
                                <td className="text-gray-700">{m.main_user?.name ?? '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
