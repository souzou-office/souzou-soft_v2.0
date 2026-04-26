import { Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { TaskStepper } from '@/Components/TaskStepper';
import { InlineSelect } from '@/Components/InlineSelect';
import type { MatterRow, PhaseDef, StaffOption } from '@/Types';

/**
 * 3.2 ホーム画面 — 進捗中心レイアウト（フェーズ可視化版）。
 *
 * 列: 事件番号 / 依頼元 / 売主→買主 / 決済日 /
 *      フェーズバッジ / 進捗ステッパー(フェーズ区切り) / 次工程+期日 / 主担当(select)
 *
 * フェーズバッジで「いまどこ」が一目で分かり、ステッパーは
 * 「フェーズ内のどの工程か」「次にやるべきか」を表現する。
 */
type Props = {
    matters: {
        data: MatterRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    phases: PhaseDef[];
    staff: StaffOption[];
};

const phaseColors: Record<string, string> = {
    reception:      'bg-gray-100 text-gray-700',
    preparation:    'bg-blue-100 text-blue-700',
    pre_settlement: 'bg-amber-100 text-amber-800',
    settlement:     'bg-red-100 text-red-700',
    post:           'bg-emerald-100 text-emerald-700',
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

export default function HomeIndex({ matters, phases, staff }: Props) {
    const staffOptions = staff.map((s) => ({ value: s.id, label: s.name }));
    const phaseLabel = (key: string | null) =>
        phases.find((p) => p.key === key)?.label ?? '—';

    return (
        <AppLayout title="ホーム — 事件一覧">
            <div className="p-4">
                <p className="mb-3 text-xs text-gray-500">
                    フェーズバッジで「いまどのフェーズか」を即把握。ステッパーは受任→書類準備→決済前→決済→後処理で区切られ、
                    オレンジで強調された円が「今やるべき工程」。
                </p>

                <table className="w-full border-collapse text-sm">
                    <thead className="bg-gray-50 text-xs text-gray-500">
                        <tr className="border-b">
                            <th className="px-2 py-2 text-left">事件番号</th>
                            <th className="px-2 py-2 text-left">依頼元</th>
                            <th className="px-2 py-2 text-left">売主→買主</th>
                            <th className="px-2 py-2 text-left">決済日</th>
                            <th className="px-2 py-2 text-left">いま</th>
                            <th className="px-2 py-2 text-left">進捗</th>
                            <th className="px-2 py-2 text-right">%</th>
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
                                    <span
                                        className={`inline-block rounded px-2 py-0.5 text-xs font-semibold ${
                                            m.current_phase
                                                ? phaseColors[m.current_phase] ?? 'bg-gray-100 text-gray-700'
                                                : 'bg-gray-100 text-gray-400'
                                        }`}
                                    >
                                        {phaseLabel(m.current_phase)}
                                    </span>
                                </td>
                                <td className="px-2 py-2">
                                    <TaskStepper
                                        matterId={m.id}
                                        steps={m.stepper}
                                        phases={phases}
                                        compact
                                        nextTaskId={m.next_task?.id}
                                    />
                                </td>
                                <td className="px-2 py-2 text-right tabular-nums text-gray-700">
                                    {m.progress.percent}%
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
