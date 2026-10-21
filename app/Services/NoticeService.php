<?php

namespace App\Services;

use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Attachments live on the private disk under generated names (BR-13) and are streamed
 * by NoticeController::attachment, which refuses drafts (BR-11).
 */
class NoticeService
{
    public function save(Notice $notice, array $data, ?UploadedFile $attachment, User $by): Notice
    {
        $notice->fill($data);
        $notice->is_pinned = (bool) ($data['is_pinned'] ?? false);
        $notice->created_by ??= $by->id;

        // First publication sets the date the board sorts by; later edits keep it.
        if ($notice->status === NoticeStatus::Published && ! $notice->published_at) {
            $notice->published_at = now();
        }

        $old = $notice->attachment_path;
        if ($attachment) {
            $notice->attachment_path = $attachment->store('notices', 'local');
        } elseif ($data['remove_attachment'] ?? false) {
            $notice->attachment_path = null;
        }

        $notice->save();

        if ($old && $old !== $notice->attachment_path) {
            Storage::disk('local')->delete($old);
        }

        return $notice;
    }

    public function delete(Notice $notice): void
    {
        $notice->delete();

        if ($notice->attachment_path) {
            Storage::disk('local')->delete($notice->attachment_path);
        }
    }
}
