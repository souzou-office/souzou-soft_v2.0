import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * 2.3.3 状態変更はインライン。
 * 編集ボタン → 編集画面 → 保存 のフローを排除し、その場で <select> 切替 → PATCH 自動保存。
 */
type Option = { value: string | number; label: string };

type Props = {
    value: string | number | null | undefined;
    options: Option[];
    url: string;
    field: string;
    placeholder?: string;
    className?: string;
};

export function InlineSelect({ value, options, url, field, placeholder, className }: Props) {
    const [current, setCurrent] = useState<string | number>(value ?? '');
    const [saving, setSaving] = useState(false);

    return (
        <select
            value={current}
            disabled={saving}
            className={`rounded border-gray-200 bg-transparent text-sm focus:ring-1 focus:ring-brand-blue ${className ?? ''}`}
            onChange={(e) => {
                const next = e.target.value;
                setCurrent(next);
                setSaving(true);
                router.patch(
                    url,
                    { [field]: next === '' ? null : next },
                    {
                        preserveScroll: true,
                        onFinish: () => setSaving(false),
                    },
                );
            }}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {options.map((opt) => (
                <option key={opt.value} value={opt.value}>
                    {opt.label}
                </option>
            ))}
        </select>
    );
}
