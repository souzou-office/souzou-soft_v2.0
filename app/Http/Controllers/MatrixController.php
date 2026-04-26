<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 3.3 進捗マトリクス画面（/matrix）。
 * 縦軸=案件、横軸=task-code、セル=各タスクの状態。
 * 業務種別で対象外の工程は「対象外」セル（白抜き）として表現される。
 */
class MatrixController extends Controller
{
    public function index(Request $request): Response
    {
        $matters = Matter::query()
            ->with(['tasks.assignee', 'mainUser', 'parties'])
            ->orderBy('settlement_date')
            ->limit(50)
            ->get();

        $taskCodes = config('matter.task_codes');

        $rows = $matters->map(function (Matter $matter) use ($taskCodes) {
            $cells = [];
            $byCode = $matter->tasks->groupBy('task_code');
            foreach ($taskCodes as $code => $name) {
                if (! isset($byCode[$code])) {
                    $cells[$code] = ['state' => 'not_applicable'];
                    continue;
                }
                /** @var \App\Models\Task $task */
                $task = $byCode[$code]->first();
                $cells[$code] = [
                    'task_id'      => $task->id,
                    'state'        => $task->visualState(),
                    'assignee'     => $task->assignee?->name,
                    'planned_date' => optional($task->planned_date)->format('Y-m-d'),
                ];
            }

            return [
                'matter_id'      => $matter->id,
                'matter_number'  => $matter->matter_number,
                'job_type'       => $matter->job_type?->value,
                'job_type_label' => $matter->job_type?->label(),
                'settlement_at'  => optional($matter->settlement_date)->format('Y-m-d'),
                'main_user'      => $matter->mainUser?->name,
                'cells'          => $cells,
            ];
        });

        return Inertia::render('Matrix/Index', [
            'task_codes' => $taskCodes,
            'rows'       => $rows,
        ]);
    }
}
