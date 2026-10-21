@props(['category'])

<span @class([
    'inline-block rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-purple-100 text-purple-800' => $category === App\Enums\NoticeCategory::ProvostOffice,
    'bg-sky-100 text-sky-800' => $category === App\Enums\NoticeCategory::HallOffice,
    'bg-emerald-100 text-emerald-800' => $category === App\Enums\NoticeCategory::Club,
])>{{ $category->label() }}</span>
