<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Matter;
use App\Models\Task;
use App\Services\Documents\DocumentGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 第6章 書類生成 UI（チェックボックス方式）。
 */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentGenerator $generator) {}

    public function form(Matter $matter, DocumentTemplate $template): Response
    {
        return Inertia::render('Document/Form', [
            'matter'       => ['id' => $matter->id, 'matter_number' => $matter->matter_number],
            'template'     => [
                'id'          => $template->id,
                'description' => $template->description,
                'code'        => $template->code,
            ],
            'ui_definition' => $template->uiDefinition(),
        ]);
    }

    public function generate(Request $request, Matter $matter, DocumentTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'task_id'   => 'required|exists:tasks,id',
            'form_data' => 'array',
        ]);

        $task = Task::findOrFail($data['task_id']);
        if ($task->job_id !== $matter->id) {
            abort(403);
        }

        $output = $this->generator->generate($template, $matter, $task, $data['form_data'] ?? []);

        return back()->with('success', "書類を生成しました: {$output->local_path}");
    }
}
