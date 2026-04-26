import { router, useForm } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';

/**
 * 6.2.3 チェックボックス方式 UI（自動生成）。
 * UI 定義 YAML のフィールド配列をループしてフォーム要素を描く。
 *
 * NOTE: ラジオ・チェックボックス・数値・プリセットボタンの 4 種類だけを
 *        まずサポート。他の type は future work。
 */

type UiField =
    | { type: 'radio'; name: string; label: string; options: string[]; show_when?: string }
    | { type: 'checkbox'; name: string; label: string }
    | { type: 'number'; name: string; label: string; show_when?: string };

type UiPreset = { name: string; values: Record<string, unknown> };

type Props = {
    matter: { id: number; matter_number: string };
    template: { id: number; description: string; code: string };
    ui_definition: { fields?: UiField[]; presets?: UiPreset[] };
};

export default function DocumentForm({ matter, template, ui_definition }: Props) {
    const { data, setData, processing } = useForm<Record<string, unknown>>({});

    const submit = () => {
        router.post(`/matters/${matter.id}/documents/${template.id}/generate`, {
            task_id: data.task_id,
            form_data: { flags: data, blocks: {} },
        });
    };

    return (
        <AppLayout title={`書類生成 — ${template.description}`}>
            <div className="max-w-xl space-y-4 p-6">
                <header>
                    <h2 className="text-lg font-semibold">{template.description}</h2>
                    <p className="text-xs text-gray-500">事件 {matter.matter_number}</p>
                </header>

                {(ui_definition.presets ?? []).length > 0 && (
                    <section>
                        <div className="mb-2 text-xs font-medium text-gray-600">プリセット</div>
                        <div className="flex flex-wrap gap-2">
                            {ui_definition.presets!.map((p) => (
                                <button
                                    key={p.name}
                                    type="button"
                                    onClick={() => Object.entries(p.values).forEach(([k, v]) => setData(k, v))}
                                    className="rounded border bg-gray-50 px-2 py-1 text-xs hover:bg-gray-100"
                                >
                                    {p.name}
                                </button>
                            ))}
                        </div>
                    </section>
                )}

                <section className="space-y-3">
                    {(ui_definition.fields ?? []).map((field) => {
                        if (field.show_when && !data[field.show_when]) return null;
                        return (
                            <div key={field.name}>
                                <div className="text-sm font-medium">{field.label}</div>
                                {field.type === 'radio' && (
                                    <div className="mt-1 flex gap-3 text-sm">
                                        {field.options.map((opt) => (
                                            <label key={opt} className="flex items-center gap-1">
                                                <input
                                                    type="radio"
                                                    name={field.name}
                                                    checked={data[field.name] === opt}
                                                    onChange={() => setData(field.name, opt)}
                                                />
                                                {opt}
                                            </label>
                                        ))}
                                    </div>
                                )}
                                {field.type === 'checkbox' && (
                                    <label className="mt-1 flex items-center gap-1 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={!!data[field.name]}
                                            onChange={(e) => setData(field.name, e.target.checked)}
                                        />
                                        有効にする
                                    </label>
                                )}
                                {field.type === 'number' && (
                                    <input
                                        type="number"
                                        value={(data[field.name] as number | undefined) ?? ''}
                                        onChange={(e) => setData(field.name, Number(e.target.value))}
                                        className="mt-1 rounded border-gray-300 text-sm"
                                    />
                                )}
                            </div>
                        );
                    })}
                </section>

                <button
                    onClick={submit}
                    disabled={processing}
                    className="rounded bg-brand-blue px-4 py-2 text-sm text-white"
                >
                    生成して Drive に保存
                </button>
            </div>
        </AppLayout>
    );
}
