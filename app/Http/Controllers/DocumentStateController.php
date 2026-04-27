<?php

namespace App\Http\Controllers;

use App\Enums\DocumentState;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 書類の状態遷移を扱う。InlineSelect から PATCH で叩かれる。
 *
 *   collection: not_started → requested → received → confirmed
 *   creation:   not_started → drafted → confirmed
 *
 * confirmed への遷移時は confirmation_requires をチェックして依存書類が
 * すべて confirmed であることを確認（Document::canConfirm）。
 */
class DocumentStateController extends Controller
{
    public function update(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'state'   => 'nullable|in:not_started,requested,received,drafted,confirmed',
            'is_held' => 'nullable|boolean',
            'memo'    => 'nullable|string|max:2000',
        ]);

        $updates = [];

        if (array_key_exists('state', $data)) {
            $next = DocumentState::from($data['state']);

            // confirmed への遷移は依存書類チェック
            if ($next === DocumentState::Confirmed) {
                $matterDocs = $document->matter->documents;
                if (! $document->canConfirm($matterDocs)) {
                    return back()->with('error', '依存書類がまだ確定していません');
                }
            }

            $updates['state'] = $next;
            $updates = array_merge($updates, $this->stampForState($next));
        }

        if (array_key_exists('is_held', $data)) {
            $updates['is_held'] = $data['is_held'];
        }

        if (array_key_exists('memo', $data)) {
            $updates['memo'] = $data['memo'];
        }

        $updates['updated_by_user_id'] = $request->user()->id;
        $document->update($updates);

        return back();
    }

    private function stampForState(DocumentState $state): array
    {
        return match ($state) {
            DocumentState::Requested => ['requested_at' => now()],
            DocumentState::Received  => ['received_at'  => now()],
            DocumentState::Drafted   => ['drafted_at'   => now()],
            DocumentState::Confirmed => ['confirmed_at' => now()],
            default => [],
        };
    }
}
