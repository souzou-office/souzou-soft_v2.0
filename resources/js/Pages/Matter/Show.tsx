import { useMemo } from 'react';
import { AppLayout } from '@/Layouts/AppLayout';
import { SummaryBar } from '@/Components/SummaryBar';
import { MiniProgressBar } from '@/Components/MiniProgressBar';
import { MilestoneBar } from '@/Components/MilestoneBar';
import { RoleLanes } from '@/Components/RoleLanes';

/**
 * 事件詳細画面（並列処理対応版）。
 *
 * 上から:
 *  1. SummaryBar  事件サマリー（決済日・進捗・次工程）
 *  2. MilestoneBar 4 マイルストーンの締切チェーン
 *  3. RoleLanes   ロール別レーン（売主/買主/抹消銀行/設定銀行/仲介/共通）
 *
 * 各レーン内でタスクと書類が並列に流れる。書類状態はインライン編集できる。
 */

type Props = {
    matter: {
        id: number;
        matter_number: string;
        job_type: number | null;
        job_type_label: string | null;
        settlement_at: string | null;
        progress: { done: number; total: number; percent: number };
        next_task: {
            id: number;
            name: string;
            assignee: string | null;
            planned_date: string | null;
            days_left: number | null;
        } | null;
        parties: {
            id: number;
            role_code: number;
            role_label: string;
            name: string;
            address?: string;
        }[];
        properties: { id: number; location: string; parcel_number?: string }[];
        tasks: any[];
        documents: any[];
        milestones: any[];
        lane_summary: any[];
    };
};

export default function MatterShow({ matter }: Props) {
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
                <MilestoneBar
                    milestones={matter.milestones}
                    settlementAt={matter.settlement_at}
                />
            </div>

            <RoleLanes
                lanes={matter.lane_summary}
                documents={matter.documents}
                tasks={matter.tasks.map((t: any) => ({
                    id: t.id,
                    role_code: t.role_code,
                    name: t.name,
                    state: t.state,
                    planned_date: t.planned_date,
                    days_left: t.days_left,
                    assignee: t.assignee,
                }))}
            />
        </AppLayout>
    );
}
