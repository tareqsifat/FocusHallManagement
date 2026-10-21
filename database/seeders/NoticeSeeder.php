<?php

namespace Database\Seeders;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use App\Services\NoticeService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/** 12 notices: 10 published (2 pinned, 2 with a PDF) and 2 drafts. */
class NoticeSeeder extends Seeder
{
    public function run(NoticeService $notices): void
    {
        $by = [
            'provost' => User::where('email', 'provost@kjh.test')->firstOrFail(),
            'office' => User::where('email', 'office@kjh.test')->firstOrFail(),
            'club' => User::where('email', 'clubadmin@student.du.test')->firstOrFail(),
        ];

        $list = [
            // [title, body, category, author, days ago, status, pinned, pdf]
            ['Seat allocation for the 2025-26 session', "Applications for hall seats for the 2025-26 session are open until 31 October.\nSubmit the form at the hall office with two passport-size photos.", 'hall_office', 'office', 12, 'published', true, true],
            ['Hall will remain closed on Victory Day', 'The hall office will remain closed on 16 December. Emergency contacts are posted at the main gate.', 'provost_office', 'provost', 2, 'published', true, false],
            ['Water supply interruption on Saturday', 'Water supply will be off from 10 AM to 2 PM on Saturday for tank cleaning.', 'hall_office', 'office', 5, 'published', false, false],
            ['Code of conduct reminder', "Residents must return to the hall by 11 PM.\nVisitors are allowed in the guest room only.", 'provost_office', 'provost', 30, 'published', false, true],
            ['Debate workshop for first-year students', 'The Debating Club will run a two-day workshop for first-year students in the common room.', 'club', 'club', 8, 'published', false, false],
            ['Inter-hall debate championship trials', 'Trials for the inter-hall championship team will be held next Thursday at 4 PM.', 'club', 'club', 1, 'published', false, false],
            ['Room inspection schedule', 'House tutors will inspect rooms in each block next week. Please keep your rooms tidy.', 'hall_office', 'office', 15, 'published', false, false],
            ['বার্ষিক সাংস্কৃতিক সন্ধ্যা', 'হলের বার্ষিক সাংস্কৃতিক সন্ধ্যা আগামী মাসে অনুষ্ঠিত হবে। আগ্রহী শিক্ষার্থীরা সাংস্কৃতিক ক্লাবে নাম জমা দিন।', 'provost_office', 'provost', 20, 'published', false, false],
            ['Internet maintenance in South block', 'The hall network in South block will be upgraded on Sunday night.', 'hall_office', 'office', 25, 'published', false, false],
            ['Library hours extended during exams', 'The hall library will stay open until midnight during the exam period.', 'provost_office', 'provost', 40, 'published', false, false],
            ['Draft: new guest room rules', 'Draft rules for the guest room booking system. Not for publication yet.', 'provost_office', 'provost', 1, 'draft', false, false],
            ['Draft: club fair date', 'The club fair date is still being finalised.', 'club', 'club', 1, 'draft', false, false],
        ];

        foreach ($list as [$title, $body, $category, $author, $daysAgo, $status, $pinned, $pdf]) {
            DatabaseSeeder::travelTo($daysAgo, 12);
            $notices->save(new Notice, [
                'title' => $title,
                'body' => $body,
                'category' => NoticeCategory::from($category)->value,
                'status' => NoticeStatus::from($status)->value,
                'is_pinned' => $pinned,
            ], $pdf ? $this->pdf($title) : null, $by[$author]);
        }
    }

    /** A tiny valid one-page PDF so the download link works in the demo. */
    private function pdf(string $title): UploadedFile
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'notice');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, 'notice.pdf', 'application/pdf', null, true);
    }
}
