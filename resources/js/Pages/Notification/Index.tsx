import { Link, router } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';
import { cn } from '@/Lib/cn';

type Notification = {
    id: number;
    type: string;
    title: string;
    body: string | null;
    is_read: boolean;
    created_at: string;
    matter_id: number | null;
    task_id: number | null;
};

type Props = { notifications: Notification[] };

export default function NotificationIndex({ notifications }: Props) {
    return (
        <AppLayout title="通知">
            <div className="p-4">
                <div className="mb-2 flex items-center justify-between">
                    <p className="text-sm text-gray-500">最新 100件まで表示</p>
                    <button
                        onClick={() => router.post('/notifications/read-all')}
                        className="text-sm text-brand-blue hover:underline"
                    >
                        すべて既読にする
                    </button>
                </div>
                <ul className="divide-y border bg-white">
                    {notifications.map((n) => (
                        <li
                            key={n.id}
                            className={cn(
                                'flex items-start gap-3 px-3 py-2 text-sm',
                                !n.is_read && 'bg-blue-50',
                            )}
                        >
                            <span className="mt-1 size-2 shrink-0 rounded-full bg-brand-blue" hidden={n.is_read} />
                            <div className="flex-1">
                                <div className="font-medium">{n.title}</div>
                                {n.body && <div className="text-xs text-gray-600">{n.body}</div>}
                                <div className="mt-0.5 text-xs text-gray-400">
                                    {new Date(n.created_at).toLocaleString('ja-JP')}
                                    {' · '}
                                    {n.matter_id && (
                                        <Link
                                            href={`/matters/${n.matter_id}${n.task_id ? `#task-${n.task_id}` : ''}`}
                                            className="text-brand-blue hover:underline"
                                        >
                                            事件を開く
                                        </Link>
                                    )}
                                </div>
                            </div>
                            {!n.is_read && (
                                <button
                                    onClick={() => router.post(`/notifications/${n.id}/read`)}
                                    className="text-xs text-gray-500 hover:text-brand-blue"
                                >
                                    既読
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            </div>
        </AppLayout>
    );
}
