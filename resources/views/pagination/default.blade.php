@if ($paginator->hasPages())
{{--    {{dd($paginator,$elements)}}--}}

    <div>
        <span>نمایش</span>
        <span>{{ $paginator->firstItem() }}</span>
        <span>تا</span>
        <span>{{ $paginator->lastItem() }}</span>
        <span>از</span>
        <span>{{ $paginator->total() }}</span>
        <span>رکورد</span>
    </div>

    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="btn btn-outline-secondary ml-1 disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li>
                    <a class="btn btn-outline-secondary ml-1" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="btn btn-info active ml-1" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a class="btn btn-outline-info ml-1" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a class="btn btn-outline-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="btn btn-outline-secondary disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
