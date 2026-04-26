/**
 * 3.1.3 第3層: ミニマップ・パンくず。
 * 画面右上隅にコンパクト表示。常時固定。
 */
export function MiniProgressBar({ percent }: { percent: number }) {
    return (
        <div className="fixed right-4 top-4 z-40 rounded bg-white/90 px-2 py-1 text-xs shadow ring-1 ring-gray-200">
            <div className="flex items-center gap-2">
                <span className="text-gray-500">進捗</span>
                <div className="h-1.5 w-24 overflow-hidden rounded-full bg-gray-200">
                    <div
                        className="h-full bg-brand-blue"
                        style={{ width: `${percent}%` }}
                    />
                </div>
                <span className="font-mono text-gray-700">{percent}%</span>
            </div>
        </div>
    );
}
