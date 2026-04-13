@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@php
    $currentDirection = $direction === 'asc' ? 'asc' : 'desc';
    $pageWindowStart = max(1, $rows->currentPage() - 2);
    $pageWindowEnd = min($rows->lastPage(), $rows->currentPage() + 2);
@endphp

@section('admin_content')
    <section class="page-panel">
        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>

            <form method="GET" class="table-toolbar" data-live-search>
                <label class="table-search">
                    <span>Cari data</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        autocomplete="off"
                    >
                </label>

                @foreach ($filters as $filter)
                    <label class="table-filter">
                        <span>{{ $filter['label'] }}</span>
                        <select name="{{ $filter['key'] }}">
                            <option value="">Semua</option>
                            @foreach ($filter['options'] as $option)
                                <option
                                    value="{{ $option['value'] }}"
                                    @selected(($filterValues[$filter['key']] ?? '') === $option['value'])
                                >
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endforeach

                <label class="table-page-size">
                    <span>Baris</span>
                    <select name="per_page">
                        @foreach ([10, 20, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $currentDirection }}">
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $isActiveSort = $sort === $column['key'];
                                $nextDirection = $isActiveSort && $currentDirection === 'asc' ? 'desc' : 'asc';
                                $sortUrl = request()->fullUrlWithQuery([
                                    'sort' => $column['key'],
                                    'direction' => $nextDirection,
                                    'page' => 1,
                                ]);
                            @endphp
                            <th>
                                @if ($column['sortable'])
                                    <a
                                        href="{{ $sortUrl }}"
                                        class="sort-link {{ $isActiveSort ? 'active' : '' }}"
                                        aria-label="Urutkan berdasarkan {{ $column['label'] }} {{ $nextDirection === 'asc' ? 'menaik' : 'menurun' }}"
                                    >
                                        <span>{{ $column['label'] }}</span>
                                        <span class="sort-indicator" aria-hidden="true">{!! $isActiveSort ? ($currentDirection === 'asc' ? '&uarr;' : '&darr;') : '&varr;' !!}</span>
                                    </a>
                                @else
                                    <span>{{ $column['label'] }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($columns as $column)
                                <td>{{ $row[$column['key']] ?? 'null' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="empty-cell">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="table-summary">
                Menampilkan {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} data
            </p>

            @if ($rows->lastPage() > 1)
                <nav class="pager" aria-label="Pagination">
                    <a
                        href="{{ $rows->previousPageUrl() ?? '#' }}"
                        class="pager-link {{ $rows->onFirstPage() ? 'disabled' : '' }}"
                        @if ($rows->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                    >
                        Prev
                    </a>

                    @for ($page = $pageWindowStart; $page <= $pageWindowEnd; $page++)
                        <a
                            href="{{ $rows->url($page) }}"
                            class="pager-link {{ $page === $rows->currentPage() ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $rows->nextPageUrl() ?? '#' }}"
                        class="pager-link {{ $rows->hasMorePages() ? '' : 'disabled' }}"
                        @if (! $rows->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                    >
                        Next
                    </a>
                </nav>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-live-search]');
            if (!form) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const perPageSelect = form.querySelector('select[name="per_page"]');
            const filterSelects = form.querySelectorAll('.table-filter select');
            let debounceId;

            searchInput?.addEventListener('input', () => {
                window.clearTimeout(debounceId);
                debounceId = window.setTimeout(() => {
                    form.submit();
                }, 250);
            });

            perPageSelect?.addEventListener('change', () => {
                form.submit();
            });

            filterSelects.forEach((select) => {
                select.addEventListener('change', () => {
                    form.submit();
                });
            });
        })();
    </script>
@endsection
