<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus as S;
use App\Enums\ResidencyStatus;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\ComplaintStatusService;
use Illuminate\Database\Seeder;

/**
 * 24 complaints across every status, 6 of them overdue, one reopened by its student.
 * Each spec: [student key, category, title, description, submitted N days ago,
 *             assignee key or null (assigned the day after submission),
 *             [[status, days ago, actor key, note], ...], [[actor key, days ago, comment], ...]]
 */
class ComplaintSeeder extends Seeder
{
    public function run(ComplaintService $complaints, ComplaintStatusService $statuses): void
    {
        $users = collect([
            'office' => 'office@kjh.test', 'office2' => 'office2@kjh.test', 'provost' => 'provost@kjh.test',
            'tutorN' => 'tutor@kjh.test', 'tutorNS' => 'tutor2@kjh.test', 'tutorS' => 'tutor3@kjh.test',
            'tutorE' => 'tutor4@kjh.test', 'tutorES' => 'tutor5@kjh.test',
            'demo' => 'student@student.du.test', 'clubadmin' => 'clubadmin@student.du.test',
        ])->map(fn ($email) => User::where('email', $email)->firstOrFail());

        $demoIds = [$users['demo']->id, $users['clubadmin']->id];
        $active = StudentProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->whereNotIn('user_id', $demoIds)
            ->with(['user', 'currentAllocation.seat.room.block'])
            ->orderBy('id')
            ->get();
        $byBlock = $active->where('residency_status', ResidencyStatus::Resident)
            ->groupBy(fn ($p) => $p->currentAllocation->seat->room->block->name);
        $students = collect(['demo' => $users['demo'], 'clubadmin' => $users['clubadmin']]);
        foreach (['North' => 'n', 'South' => 's', 'Extension' => 'e'] as $block => $prefix) {
            foreach ($byBlock[$block]->values() as $i => $profile) {
                $students["{$prefix}".($i + 1)] = $profile->user;
            }
        }
        foreach ($active->where('residency_status', ResidencyStatus::Attached)->values() as $i => $profile) {
            $students['a'.($i + 1)] = $profile->user;
        }

        // Oldest first, so tracking numbers follow submission order.
        $specs = collect($this->specs())->sortByDesc(4)->values();
        foreach ($specs as [$who, $category, $title, $description, $daysAgo, $assignee, $steps, $comments, $location]) {
            DatabaseSeeder::travelTo($daysAgo, 9);
            $student = $students[$who]->fresh();
            $complaint = $complaints->submit($student, [
                'category' => $category, 'title' => $title, 'description' => $description, 'location_text' => $location,
            ], null);

            if ($assignee) {
                DatabaseSeeder::travelTo(max($daysAgo - 1, 0), 11);
                $complaints->assign($complaint, $users[$assignee]);
            }
            foreach ($steps as [$to, $at, $actor, $note]) {
                DatabaseSeeder::travelTo($at, 15);
                $statuses->transition($complaint->fresh(), $to, $note, $actor === 'owner' ? $student : $users[$actor]);
            }
            foreach ($comments as [$actor, $at, $body]) {
                DatabaseSeeder::travelTo($at, 18);
                $complaints->comment($complaint, $actor === 'owner' ? $student : $users[$actor], $body);
            }
        }
    }

    private function specs(): array
    {
        $none = [];

        return [
            ['demo', 'water', 'No water on 3rd floor washroom', 'Taps on the 3rd floor of North block have been dry every evening this week.', 9, 'tutorN',
                [[S::UnderReview, 8, 'office', null], [S::InProgress, 7, 'tutorN', 'Pump room checked.']],
                [['owner', 5, 'Still no water in the evening.'], ['tutorN', 4, 'The plumber has been called; the pump needs a new part.']], null],
            ['demo', 'internet', 'Wi-Fi drops every night', 'The Wi-Fi disconnects every night after 11 PM on the 3rd floor.', 25, 'tutorN',
                [[S::UnderReview, 24, 'office', null], [S::InProgress, 22, 'tutorN', null], [S::Resolved, 20, 'tutorN', 'Router on the 3rd floor replaced.'], [S::Closed, 12, 'office', null]],
                [['owner', 19, 'Working fine now, thank you.']], null],
            ['n1', 'electricity', 'Ceiling fan not working', 'The ceiling fan in my room stopped working yesterday.', 4, null, $none, $none, null],
            ['n2', 'cleanliness', 'Corridor bins not emptied', 'The bins in the 2nd floor corridor have not been emptied for days.', 12, null, $none, $none, null],
            ['n3', 'furniture', 'Broken study table', 'One leg of my study table is broken.', 15, 'tutorN',
                [[S::UnderReview, 14, 'office', null], [S::InProgress, 10, 'tutorN', null], [S::Resolved, 6, 'tutorN', 'Table replaced from the hall store.']], $none, null],
            ['n4', 'security', 'Main gate left open after midnight', 'The North block gate was open after midnight on two nights.', 6, 'office',
                [[S::UnderReview, 5, 'provost', 'Night guard roster to be checked.']], $none, null],
            ['n5', 'water', 'Leaking tap in room', 'The tap in the washroom near my room leaks all the time.', 3, null,
                [[S::Rejected, 2, 'office', 'Duplicate: the same tap is already being handled in another complaint.']], $none, null],
            ['n6', 'water', 'Washroom flush broken', 'The flush in the 1st floor washroom does not work.', 3, 'tutorN',
                [[S::UnderReview, 2, 'office', null]], $none, null],
            ['s1', 'electricity', 'Power socket sparking', 'The wall socket next to my bed sparks when I plug in a charger.', 20, 'tutorS',
                [[S::UnderReview, 19, 'office', null], [S::InProgress, 18, 'tutorS', 'Electrician informed.'], [S::Resolved, 16, 'tutorS', 'Socket replaced by the electrician.'], [S::Closed, 10, 'provost', null]], $none, null],
            ['s2', 'internet', 'No LAN connection in South block', 'None of the LAN ports on the 2nd floor of South block work.', 10, 'tutorNS',
                [[S::UnderReview, 9, 'office', null]], [['tutorNS', 6, 'Waiting for the IT office to visit.']], null],
            ['s3', 'water', 'Water filter empty', 'The drinking water filter on the ground floor is empty.', 2, null, $none, $none, null],
            ['s4', 'cleanliness', 'Washroom not cleaned', 'The 3rd floor washroom has not been cleaned since Friday.', 8, 'office2',
                [[S::UnderReview, 7, 'office', null], [S::InProgress, 5, 'office2', 'Cleaner reassigned.']], $none, null],
            ['s5', 'other', 'Noise from construction at night', 'Construction next to the hall continues until 2 AM.', 5, null,
                [[S::Rejected, 4, 'provost', 'The construction schedule is set by the university, not the hall office.']], $none, null],
            ['s6', 'furniture', 'Bed frame cracked', 'The wooden bed frame has a crack along the side rail.', 18, 'tutorS',
                [[S::UnderReview, 17, 'office', null], [S::InProgress, 15, 'tutorS', null], [S::Resolved, 9, 'tutorS', 'Bed frame repaired.'], [S::InProgress, 7, 'owner', 'The crack has come back.']],
                [['tutorS', 6, 'A replacement frame has been requested.']], null],
            ['s7', 'electricity', 'Room light switch broken', 'The light switch in my room is broken.', 26, 'tutorNS',
                [[S::UnderReview, 25, 'office', null], [S::InProgress, 24, 'tutorNS', null], [S::Resolved, 23, 'tutorNS', 'Switch replaced.']], $none, null],
            ['e1', 'electricity', 'Tube light flickering', 'The tube light in my room keeps flickering.', 1, null, $none, $none, null],
            ['e2', 'water', 'Low water pressure', 'Water pressure in the Extension block 2nd floor is very low.', 11, 'tutorE',
                [[S::UnderReview, 10, 'office', null], [S::InProgress, 8, 'tutorE', null], [S::Resolved, 3, 'tutorE', 'Main valve adjusted; pressure is normal again.']], $none, null],
            ['e3', 'security', 'CCTV near stairs not working', 'The CCTV camera near the Extension block stairs shows no picture.', 30, 'office',
                [[S::UnderReview, 29, 'provost', null], [S::InProgress, 27, 'office', null], [S::Resolved, 24, 'office', 'Camera repaired by the vendor.'], [S::Closed, 20, 'provost', null]], $none, null],
            ['e4', 'cleanliness', 'Common area dirty', 'The Extension block common area is not cleaned regularly.', 6, 'tutorES',
                [[S::UnderReview, 5, 'office', null], [S::InProgress, 4, 'tutorES', null]], $none, null],
            ['e5', 'internet', 'Slow internet in Extension block', 'Internet speed has been very slow for two days.', 2, null,
                [[S::UnderReview, 1, 'office', null]], $none, null],
            ['clubadmin', 'furniture', 'Chairs missing from the debate room', 'Twenty chairs were taken from the debate room and not returned.', 45, 'office',
                [[S::UnderReview, 44, 'office', null], [S::InProgress, 43, 'office', null], [S::Resolved, 40, 'office', 'Chairs returned from the auditorium.'], [S::Closed, 38, 'provost', null]], $none, null],
            ['a1', 'security', 'Bicycle stolen from parking', 'My bicycle was taken from the stand near the main gate.', 13, 'office',
                [[S::UnderReview, 12, 'provost', 'Checking CCTV footage.']], [['owner', 10, 'Any update on the footage?']], 'Bicycle stand near the main gate'],
            ['a2', 'other', 'Reading room closes too early', 'The reading room lights are switched off at 9 PM.', 5, null, $none, $none, 'Ground floor reading room'],
            ['a3', 'internet', 'Common room Wi-Fi password not shared', 'Attached students are not given the common room Wi-Fi password.', 16, null,
                [[S::Rejected, 15, 'office', 'Hall Wi-Fi is for residents only; attached students can use the library network.']], $none, 'Common room'],
        ];
    }
}
