import { cn } from '@/Lib/cn';
import type { TaskState } from '@/Types';

/**
 * 3.1.2 / 3.3.2 「色＋形状」の二重表現を担う基本部品。
 *
 *  - completed:    塗りつぶし円・緑
 *  - in_progress:  塗りつぶし円・青（細枠）
 *  - next:         二重円・オレンジ
 *  - overdue:      二重円・赤
 *  - not_started:  中空円・グレー
 *  - not_applicable: 斜線パターン
 *
 * 色覚アクセシビリティ（2.3.2）のため、色だけでなく形でも区別できる。
 */
type Props = {
    state: TaskState;
    size?: number;
    title?: string;
};

export function StatusDot({ state, size = 14, title }: Props) {
    const base = 'inline-block rounded-full';

    if (state === 'not_applicable') {
        return (
            <span
                title={title}
                className="inline-block"
                style={{
                    width: size,
                    height: size,
                    backgroundImage:
                        'repeating-linear-gradient(45deg, #E5E7EB 0 2px, transparent 2px 6px)',
                }}
                aria-label="対象外"
            />
        );
    }

    return (
        <span
            title={title}
            className={cn(
                base,
                state === 'completed' && 'bg-status-completed',
                state === 'in_progress' && 'bg-status-in-progress ring-1 ring-blue-700',
                state === 'next' && 'bg-status-warning ring-2 ring-amber-700 ring-offset-1',
                state === 'overdue' && 'bg-status-danger ring-2 ring-red-800 ring-offset-1',
                state === 'not_started' && 'border-2 border-status-not-started bg-white',
            )}
            style={{ width: size, height: size }}
            aria-label={state}
        />
    );
}
