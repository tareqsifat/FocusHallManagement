<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        <p class="text-sm text-gray-500">{{ $scope }}</p>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if ($hall)
            <section aria-labelledby="hall-heading">
                <h3 id="hall-heading" class="mb-3 font-semibold text-gray-900">Seats and registrations</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-stat label="Total seats" :value="$hall['total_seats']" :href="route('rooms.index')" />
                    <x-stat label="Occupied seats" :value="$hall['occupied_seats']" :hint="($hall['total_seats'] - $hall['occupied_seats']).' free'" />
                    <x-stat label="Occupancy" :value="$hall['occupancy_percent'].'%'" />
                    <x-stat label="Pending registrations" :value="$hall['pending_registrations']" :href="route('registrations.index')" />
                </div>
            </section>
        @endif

        <section aria-labelledby="complaints-heading">
            <h3 id="complaints-heading" class="mb-3 font-semibold text-gray-900">Complaints</h3>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <x-stat label="Open complaints" :value="$complaints['open_total']" :href="route('complaints.index')" />
                <x-stat label="Overdue (open > 7 days)" :value="$complaints['overdue']" :alert="$complaints['overdue'] > 0"
                        :href="route('complaints.index', ['overdue' => 1])" />
                <x-stat label="Avg. resolution time" class="col-span-2 lg:col-span-1"
                        :value="$complaints['avg_resolution_days'] === null ? '—' : $complaints['avg_resolution_days'].' days'"
                        :hint="$complaints['resolved_last_30_days'].' resolved in the last 30 days'" />
            </div>

            @php $max = max(1, max($complaints['open_by_category'])); @endphp
            <figure class="mt-4 rounded-lg bg-white p-4 shadow-sm">
                <figcaption class="text-sm font-medium text-gray-900">Open complaints by category</figcaption>
                <table class="mt-3 w-full text-sm">
                    <caption class="sr-only">Number of open complaints in each category</caption>
                    <tbody>
                        @foreach ($complaints['open_by_category'] as $category => $count)
                            <tr title="{{ $category }}: {{ $count }} open">
                                <th scope="row" class="w-28 py-1 pe-3 text-left font-normal text-gray-600">{{ $category }}</th>
                                <td class="py-1">
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 flex-1">
                                            @if ($count)
                                                <div class="h-3 rounded-e bg-indigo-500" style="width: {{ $count / $max * 100 }}%"></div>
                                            @endif
                                        </div>
                                        <span class="w-8 text-right tabular-nums text-gray-900">{{ $count }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </figure>
        </section>
    </div>
</x-app-layout>
