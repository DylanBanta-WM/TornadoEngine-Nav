{{-- The dashboard: a card for each tool this person can open, in sections. Modules add them with Nav::tool(). --}}
@php
    $sections = app(\TornadoEngine\Nav\Nav::class)->toolsFor(Auth::user());
    // Full class names, so Tailwind keeps them.
    $colors = [
        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        'teal' => 'bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400',
        'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
        'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    ];
@endphp

@if ($sections)
    <div class="space-y-10">
        @foreach ($sections as $section => $tools)
            <section>
                <h3 class="mb-4 px-4 sm:px-0 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                    @foreach ($tools as $tool)
                        <a href="{{ route($tool->route) }}" class="block bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg transition-shadow duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <div class="p-6">
                                <div class="mb-3 flex items-center gap-4">
                                    <div class="rounded-lg p-3 {{ $colors[$tool->color] }}">
                                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $tool->icon !!}</svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $tool->label }}</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tool->description }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@else
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
            <svg class="size-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! \TornadoEngine\Nav\Nav::ICONS['lock'] !!}</svg>
        </div>
        <h3 class="mb-1 text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('No tools yet') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Your account doesn\'t have access to any tools yet. Ask an administrator for access.') }}</p>
    </div>
@endif
