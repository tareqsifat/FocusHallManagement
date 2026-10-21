@props(['status'])

<span @class([
    'inline-block rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-green-100 text-green-800' => $status === App\Enums\ResidencyStatus::Resident,
    'bg-amber-100 text-amber-800' => $status === App\Enums\ResidencyStatus::Attached,
])>{{ ucfirst($status->value) }}</span>
