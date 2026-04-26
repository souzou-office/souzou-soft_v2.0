import { Link } from '@inertiajs/react';
import type { PhaseDef, TaskStepData } from '@/Types';
import { StatusDot } from './StatusDot';

/**
 * 3.1.2 第2層 工程ステッパー（フェーズ区切り版）。
 *
 *  - フェーズ（受任 / 書類準備 / 決済前 / 決済 / 後処理）で縦線区切り
 *  - 「次にやるべき」task は他より大きい円 + 真下に「今」キャレット
 *  - フェーズラベルを上に小さく表示
 *
 * 「いまどこのフェーズか」が一目で分かるよう、進捗一覧を能動的に活かす設計。
 */
type Props = {
    matterId: number;
    steps: TaskStepData[];
    phases: PhaseDef[];
    compact?: boolean;
    nextTaskId?: number | null;
    showPhaseLabels?: boolean;
};

export function TaskStepper({
    matterId,
    steps,
    phases,
    compact = false,
    nextTaskId,
    showPhaseLabels = true,
}: Props) {
    const baseDot = compact ? 12 : 18;
    const currentDot = compact ? 18 : 26;

    // task_code -> step のマップ
    const byCode = new Map<number, TaskStepData>();
    steps.forEach((s) => byCode.set(s.task_code, s));

    return (
        <div className="inline-flex items-stretch gap-2">
            {phases.map((phase, phaseIdx) => {
                const phaseSteps = phase.codes
                    .map((c) => byCode.get(c))
                    .filter((s): s is TaskStepData => s !== undefined);

                if (phaseSteps.length === 0) {
                    // 業務種別で対象外のフェーズは斜線でグレーアウト
                    return (
                        <div key={phase.key} className="flex flex-col items-center justify-center px-1">
                            {showPhaseLabels && (
                                <span className="mb-0.5 text-[9px] text-gray-300">{phase.label}</span>
                            )}
                            <span
                                className="inline-block h-3 w-6 rounded-sm"
                                style={{
                                    backgroundImage:
                                        'repeating-linear-gradient(45deg,#E5E7EB 0 2px,transparent 2px 6px)',
                                }}
                                title={`${phase.label}: 対象外`}
                            />
                        </div>
                    );
                }

                const doneCount = phaseSteps.filter((s) => s.state === 'completed').length;
                const isCurrent = phaseSteps.some((s) => s.id === nextTaskId);

                return (
                    <div key={phase.key} className="flex flex-col items-center">
                        {showPhaseLabels && (
                            <span
                                className={`mb-0.5 whitespace-nowrap text-[10px] tabular-nums ${
                                    isCurrent ? 'font-bold text-brand-blue' : 'text-gray-400'
                                }`}
                            >
                                {phase.label} {doneCount}/{phaseSteps.length}
                            </span>
                        )}
                        <div
                            className={`flex items-center gap-1 rounded px-1.5 py-1 ${
                                isCurrent ? 'bg-amber-50 ring-1 ring-amber-300' : ''
                            } ${phaseIdx > 0 ? 'border-l border-l-gray-200' : ''}`}
                        >
                            {phaseSteps.map((step) => {
                                const isNext = step.id === nextTaskId;
                                const state = isNext && step.state === 'not_started' ? 'next' : step.state;
                                const tooltip = [
                                    step.name,
                                    step.assignee && `担当: ${step.assignee}`,
                                    step.planned_date && `期日: ${step.planned_date}`,
                                ]
                                    .filter(Boolean)
                                    .join('\n');

                                return (
                                    <Link
                                        key={step.id}
                                        href={`/matters/${matterId}#task-${step.id}`}
                                        className="group flex flex-col items-center"
                                        title={tooltip}
                                    >
                                        <StatusDot state={state} size={isNext ? currentDot : baseDot} />
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
