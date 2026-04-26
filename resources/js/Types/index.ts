/**
 * 仕様書 8.x の型シェイプ。Inertia 側の props はサーバ controllers が
 * 生成した形に対応する（Laravel と TypeScript の境界）。
 */

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'staff' | 'reviewer';
    is_admin: boolean;
};

export type SharedProps = {
    auth: { user: AuthUser | null };
    unread_notifications_count: number;
    flash: { success?: string | null; error?: string | null };
};

export type TaskState = 'completed' | 'in_progress' | 'overdue' | 'next' | 'not_started' | 'not_applicable';

export type TaskStepData = {
    id: number;
    name: string;
    state: TaskState;
    role_code?: number | null;
    assignee?: string | null;
    planned_date?: string | null;
};

export type MatterRow = {
    id: number;
    matter_number: string;
    job_type: number | null;
    job_type_label: string | null;
    settlement_at: string | null;
    main_user: { id: number; name: string } | null;
    progress: { total: number; done: number; percent: number };
    next_task: {
        id: number;
        name: string;
        assignee: string | null;
        planned_date: string | null;
        days_left: number | null;
        state: TaskState;
    } | null;
    stepper: TaskStepData[];
    parties_summary: string;
};
