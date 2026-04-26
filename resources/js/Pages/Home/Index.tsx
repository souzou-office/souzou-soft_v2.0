import { Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { TaskGrid } from '@/Components/TaskGrid';
import { TaskCounters } from '@/Components/TaskCounters';
import { InlineSelect } from '@/Components/InlineSelect';
import type { MatterRow, StaffOption } from '@/Types';

/**
 * 3.2 ホーム画面 — タスク管理視点版。
 *
 * 列: 事件番号 / 依頼元 / 売主→買主 / 決済日 /
 *      集計バッジ(超過/未割当/進行中/完了/全) / タスクグリッド(色=状態 文字=担当者) /
 *      次工程+期日 / 主担当(InlineSelect)
 *
 * フェーズ抽象を撤廃。タスクは前後する運用なので、平坦な per-task 視覚化に。
 */
type Props = {
    matters: {
        data: MatterRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    staff: StaffOption[];
};

function dueClass(daysLeft: number | null | undefined): string {
    if (daysLeft === null || daysLeft === undefined) return 'text-gray-500';
    if (daysLeft < 0) return 'font-bold text-red-600';
    if (daysLeft <= 2) return 'text-red-600';
    if (daysLeft <= 5) return 'text-amber-600';
    return 'text-gray-500';
}

function dueLabel(daysLeft: number | null | undefined): string {
    if (daysLeft === null || daysLeft === undefined) return '';
    if (daysLeft < 0) return ` ${Math.abs(daysLeft)}日超過`;
    if (daysLeft === 0) return ' 本日';
    return ` あと${daysLeft}日`;
}

export default function HomeIndex({ matters, staff }: Props) {
    const staffOptions = staff.map((s) => ({ value: s.id, label: s.name }));

    return (
        <AppLayout title="ホーム — 事件一覧">
            <div className="p-4">
                <div className="mb-3 rounded border bg-white p-2 text-xs text-gray-600">
                    <p>
                        <span className="font-semibold text-gray-800">タスク管理視点。</span>
                        各セル = 1タスク。色は状態（緑=完了 青=進行中 赤=超過 黄=未割当 白=未着手）、
                        中の文字は担当者の頭文字。行頭バッジで「超過 / 未割当 / 進行中 / 完了 / 全件」を即視。
                    </p>
                </div>

                <table className="w-full border-collapse text-sm">
                    <thead className="bg-gray-50 text-xs text-gray-500">
                        <tr className="border-b">
                            <th className="px-2 py-2 text-left">事件番号</th>
                            <th className="px-2 py-2 text-left">依頼元</th>
                            <th className="px-2 py-2 text-left">売主→買主</th>
                            <th className="px-2 py-2 text-left">決済日</th>
                            <th className="px-2 py-2 text-left">集計</th>
                            <th className="px-2 py-2 text-left">タスク</th>
                            <th className="px-2 py-2 text-left">次工程</th>
                            <th className="px-2 py-2 text-left">主担当</th>
                        </tr>
                    </thead>
                    <tbody>
                        {matters.data.map((m) => (
                            <tr key={m.id} className="border-b align-middle hover:bg-gray-50">
                                <td className="px-2 py-2 font-mono">
                                    <Link href={`/matters/${m.id}`} className="text-brand-blue hover:underline">
                                        {m.matter_number}
                                    </Link>
                                </td>
                                <td className="max-w-[160px] truncate px-2 py-2 text-gray-700">
                                    {m.broker_summary ?? <span className="text-gray-300">—</span>}
                                </td>
                                <td className="max-w-[180px] truncate px-2 py-2 text-gray-700">{m.parties_summary}</td>
                                <td className="px-2 py-2 text-gray-700">
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
                                <td className="px-2 py-2">
                                    <TaskCounters counters={m.task_counters} />
                                </td>
                                <td className="px-2 py-2">
                                    <TaskGrid matterId={m.id} tasks={m.tasks} />
                                </td>
                                <td className="max-w-[260px] truncate px-2 py-2">
                                    {m.next_task ? (
                                        <span className="text-gray-700">
                                            {m.next_task.name}
                                            <span className={`ml-2 text-xs ${dueClass(m.next_task.days_left)}`}>
                                                {m.next_task.planned_date ?? ''}
                                                {dueLabel(m.next_task.days_left)}
                                            </span>
                                        </span>
                                    ) : (
                                        <span className="text-gray-400">—</span>
                                    )}
                                </td>
                                <td className="px-2 py-2">
                                    <InlineSelect
                                        url={`/matters/${m.id}`}
                                        field="main_user_id"
                                        value={m.main_user?.id ?? ''}
                                        placeholder="未割当"
                                        options={staffOptions}
                                        className="w-28"
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
