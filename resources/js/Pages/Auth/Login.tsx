import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50">
            <form
                onSubmit={submit}
                className="w-80 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h1 className="mb-6 text-center text-xl font-bold text-brand-navy">
                    souzou-soft <span className="text-sm font-normal">v2.0</span>
                </h1>

                <label className="mb-3 block text-sm">
                    <span className="text-gray-600">メールアドレス</span>
                    <input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="email"
                        className="mt-1 w-full rounded border-gray-300 px-2 py-1.5 text-sm focus:ring-1 focus:ring-brand-blue"
                    />
                    {errors.email && <span className="text-xs text-red-500">{errors.email}</span>}
                </label>

                <label className="mb-3 block text-sm">
                    <span className="text-gray-600">パスワード</span>
                    <input
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="current-password"
                        className="mt-1 w-full rounded border-gray-300 px-2 py-1.5 text-sm focus:ring-1 focus:ring-brand-blue"
                    />
                </label>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded bg-brand-blue py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    ログイン
                </button>
            </form>
        </div>
    );
}
