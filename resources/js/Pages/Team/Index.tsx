import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AppLayout } from '@/Layouts/AppLayout';
import { cn } from '@/Lib/cn';

/**
 * 4.2 チームビュー（管理者専用）。
 *  - モードA: 担当者列ビュー（横軸=担当者、縦軸=期日帯）
 *  - モードB: 作業マトリクスビュー（縦軸=案件、横軸=担当者）
 */
type Load = {
    overdue: number;
    today: number;
    this_week: number;
    next_week: number;
    total: number;
};

type Props = {
    users: { id: number; name: string }[];
    load_by_user: Record<number, Load>;
    matrix_rows: {
        matter_id: number;
        matter_number: string;
        settlement_at: string | null;
        cells: Record<number, 'overdue' | 'in_progress' | 'completed' | 'none'>;
        unassigned_count: number;
    }[];
};

const cellSymbol: Record<string, string> = {
    overdue: '◎',
    in_progress: '◎',
    completed: '●',
    none: '−',
};

const cellTone: Record<string, string> = {
    overdue: 'text-red-600',
    in_progress: 'text-blue-600',
    completed: 'text-emerald-600',
    none: 'text-gray-300',
};

function loadCellTone(value: number, kind: keyof Load) {
    if (kind === 'overdue' && value >= 1) return 'bg-red-100';
    if (kind === 'today' && value > 5) return 'bg-amber-100';
    if (kind === 'this_week' && value > 12) return 'bg-amber-100';
    if (kind === 'total' && value > 30) return 'bg-amber-100';
    return '';
}

export default function TeamIndex({ users, load_by_user, matrix_rows }: Props) {
    const [mode, setMode] = useState<'A' | 'B'>('A');

    return (
        <AppLayout title="チームビュー">
            <div className="p-4">
                <nav className="mb-3 flex gap-2 text-sm">
                    <button
                        onClick={() => setMode('A')}
                        className={cn('rounded border px-3 py-1', mode === 'A' && 'bg-brand-blue text-white')}
                    >
                        モードA: 担当者×期日帯
                    </button>
                    <button
                        onClick={() => setMode('B')}
                        className={cn('rounded border px-3 py-1', mode === 'B' && 'bg-brand-blue text-white')}
                    >
                        モードB: 案件×担当者
                    </button>
                </nav>

                {mode === 'A' && (
                    <table className="table-dense w-full border-collapse text-sm">
                        <thead className="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th className="text-left">期日帯</th>
                                {users.map((u) => (
                                    <th key={u.id} className="text-center">{u.name}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {(['overdue', 'today', 'this_week', 'next_week', 'total'] as const).map((bucket) => (
                                <tr key={bucket} className="border-b">
                                    <td className="font-semibold">
                                        {bucket === 'overdue'  && '期日超過'}
                                        {bucket === 'today'    && '今日'}
                                        {bucket === 'this_week'&& '今週'}
                                        {bucket === 'next_week'&& '来週'}
                                        {bucket === 'total'    && '合計'}
                                    </td>
                                    {users.map((u) => {
                                        const v = load_by_user[u.id]?.[bucket] ?? 0;
                                        return (
                                            <td
                                                key={u.id}
                                                className={cn('text-center tabular-nums', loadCellTone(v, bucket))}
                                            >
                                                {v > 0 ? (
                                                    <Link
                                                        href={`/users/${u.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {v}
                                                    </Link>
                                                ) : (
                                                    <span className="text-gray-300">0</span>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}

                {mode === 'B' && (
                    <table className="table-dense w-full border-collapse text-sm">
                        <thead className="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th className="text-left">事件</th>
                                <th className="text-left">決済日</th>
                                {users.map((u) => (
                                    <th key={u.id} className="text-center">{u.name}</th>
                                ))}
                                <th className="text-center text-red-500">未割当</th>
                            </tr>
                        </thead>
                        <tbody>
                            {matrix_rows.map((r) => (
                                <tr key={r.matter_id} className="border-b">
                                    <td className="font-mono">
                                        <Link href={`/matters/${r.matter_id}`} className="text-brand-blue hover:underline">
                                            {r.matter_number}
                                        </Link>
                                    </td>
                                    <td className="text-gray-500">{r.settlement_at ?? '—'}</td>
                                    {users.map((u) => {
                                        const cell = r.cells[u.id] ?? 'none';
                                        return (
                                            <td
                                                key={u.id}
                                                className={cn('text-center text-base', cellTone[cell])}
                                                title={cell}
                                            >
                                                {cellSymbol[cell]}
                                            </td>
                                        );
                                    })}
                                    <td className={cn('text-center font-bold', r.unassigned_count > 0 && 'text-red-600')}>
                                        {r.unassigned_count}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </AppLayout>
    );
}
