import { Link } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { cn } from '@/Lib/cn';

/**
 * 3.3 進捗マトリクス画面（/matrix）。
 * 縦軸=案件、横軸=task-code、セル=各タスクの状態。
 *
 * 業務種別ごとに発生しない工程は「対象外」セル（白抜き斜線）として描画される。
 * 仕様書 3.3.1〜3.3.4 を実装。
 */
type Cell = {
    task_id?: number;
    state: 'completed' | 'in_progress' | 'next' | 'overdue' | 'not_started' | 'not_applicable';
    assignee?: string | null;
    planned_date?: string | null;
};

type Row = {
    matter_id: number;
    matter_number: string;
    job_type: number;
    job_type_label: string;
    settlement_at: string | null;
    main_user: string | null;
    cells: Record<number, Cell>;
};

type Props = {
    task_codes: Record<number, string>;
    rows: Row[];
};

const cellClass: Record<Cell['state'], string> = {
    completed:    'bg-cell-completed-bg',
    in_progress:  'bg-cell-in-progress-bg',
    next:         'bg-cell-next-bg',
    overdue:      'bg-cell-overdue-bg',
    not_started:  'bg-white',
    not_applicable: 'bg-cell-na-bg',
};

export default function MatrixIndex({ task_codes, rows }: Props) {
    const codeEntries = Object.entries(task_codes).map(([k, v]) => [Number(k), v] as const);

    return (
        <AppLayout title="進捗マトリクス">
            <div className="overflow-auto p-4">
                <table className="border-collapse text-xs">
                    <thead>
                        <tr>
                            <th className="sticky left-0 z-10 bg-white px-2 py-1 text-left">事件</th>
                            <th className="px-2 py-1 text-left">決済日</th>
                            <th className="px-2 py-1 text-left">業務</th>
                            <th className="px-2 py-1 text-left">主担当</th>
                            {codeEntries.map(([code, name]) => (
                                <th
                                    key={code}
                                    className="w-8 border-b px-1 py-1 text-center"
                                    title={name}
                                >
                                    {code}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.matter_id} className="border-b">
                                <td className="sticky left-0 z-10 bg-white px-2 py-1 font-mono">
                                    <Link
                                        href={`/matters/${row.matter_id}`}
                                        className="text-brand-blue hover:underline"
                                    >
                                        {row.matter_number}
                                    </Link>
                                </td>
                                <td className="px-2 py-1 text-gray-600">{row.settlement_at ?? '—'}</td>
                                <td className="px-2 py-1 text-gray-600">{row.job_type_label}</td>
                                <td className="px-2 py-1 text-gray-600">{row.main_user ?? '—'}</td>
                                {codeEntries.map(([code]) => {
                                    const cell = row.cells[code] ?? { state: 'not_applicable' as const };
                                    const tooltip = [
                                        task_codes[code],
                                        cell.assignee && `担当: ${cell.assignee}`,
                                        cell.planned_date && `期日: ${cell.planned_date}`,
                                    ]
                                        .filter(Boolean)
                                        .join('\n');
                                    return (
                                        <td
                                            key={code}
                                            title={tooltip}
                                            className={cn(
                                                'h-8 w-8 border border-gray-100 text-center align-middle',
                                                cellClass[cell.state],
                                                cell.state === 'not_applicable' &&
                                                    'bg-[repeating-linear-gradient(45deg,#E5E7EB_0_2px,transparent_2px_6px)]',
                                            )}
                                        >
                                            {cell.assignee && (
                                                <span className="text-[9px]">{cell.assignee.slice(0, 1)}</span>
                                            )}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
