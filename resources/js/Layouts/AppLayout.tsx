import { Link, usePage } from '@inertiajs/react';
import { Bell, ClipboardList, Grid3x3, Home, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import type { SharedProps } from '@/Types';

/**
 * 2.3.5 3カラムレイアウトのうち、左サイドナビ部分の共通レイアウト。
 * 全画面共通のヘッダー・サイドバー・通知センターをここに置く。
 */
export function AppLayout({ children, title }: { children: ReactNode; title?: string }) {
    const { auth, unread_notifications_count } = usePage<{ props: SharedProps }>().props as unknown as SharedProps;

    return (
        <div className="flex min-h-screen bg-gray-50">
            <aside className="flex w-52 flex-col border-r bg-white">
                <div className="flex h-12 items-center px-4 text-base font-bold text-brand-navy">
                    souzou-soft <span className="ml-1 text-xs font-normal text-gray-500">v2.0</span>
                </div>
                <nav className="flex-1 px-2 py-2 text-sm">
                    <NavItem href="/" icon={<Home className="size-4" />} label="ホーム" shortcut="g h" />
                    <NavItem href="/my" icon={<ClipboardList className="size-4" />} label="マイビュー" shortcut="g m" />
                    {auth?.user?.is_admin && (
                        <NavItem href="/team" icon={<Users className="size-4" />} label="チームビュー" shortcut="g t" />
                    )}
                    <NavItem href="/matrix" icon={<Grid3x3 className="size-4" />} label="進捗マトリクス" />
                </nav>
                <div className="border-t p-3 text-xs">
                    {auth?.user ? (
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="font-semibold">{auth.user.name}</div>
                                <div className="text-gray-500">{auth.user.role}</div>
                            </div>
                            <Link href="/logout" method="post" as="button" className="text-gray-500 hover:text-red-500">
                                ログアウト
                            </Link>
                        </div>
                    ) : (
                        <Link href="/login" className="text-brand-blue">
                            ログイン
                        </Link>
                    )}
                </div>
            </aside>

            <div className="flex flex-1 flex-col">
                <header className="flex h-12 items-center justify-between border-b bg-white px-4">
                    <h1 className="text-base font-semibold text-gray-900">{title}</h1>
                    <Link href="/notifications" className="relative inline-flex" aria-label="通知">
                        <Bell className="size-5 text-gray-600" />
                        {unread_notifications_count > 0 && (
                            <span className="absolute -right-1 -top-1 inline-flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                                {unread_notifications_count}
                            </span>
                        )}
                    </Link>
                </header>
                <main className="flex-1 overflow-x-auto">{children}</main>
            </div>
        </div>
    );
}

function NavItem({
    href,
    icon,
    label,
    shortcut,
}: {
    href: string;
    icon: ReactNode;
    label: string;
    shortcut?: string;
}) {
    return (
        <Link
            href={href}
            className="flex items-center justify-between rounded px-2 py-1.5 hover:bg-gray-100"
        >
            <span className="flex items-center gap-2">
                {icon}
                <span>{label}</span>
            </span>
            {shortcut && <span className="font-mono text-[10px] text-gray-400">{shortcut}</span>}
        </Link>
    );
}
