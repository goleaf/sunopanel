@props([
    'paginator',
    'class' => ''
])

@if ($paginator->hasPages())
    <div class="pagination-wrapper {{ $class }}">
        <nav class="flex justify-center mt-4" aria-label="Pagination">
            <ul class="flex items-center space-x-2">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <span class="btn btn-disabled btn-sm">«</span>
                    </li>
                    <li>
                        <span class="btn btn-disabled btn-sm">‹</span>
                    </li>
                @else
                    <li>
                        <a href="{{ $paginator->url(1) }}" class="btn btn-sm" aria-label="First Page">«</a>
                    </li>
                    <li>
                        <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm" aria-label="Previous Page">‹</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- 'Three Dots' Separator --}}
                    @if (is_string($element))
                        <li>
                            <span class="text-sm">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span class="btn btn-active btn-sm">{{ $page }}</span>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $url }}" class="btn btn-sm">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm" aria-label="Next Page">›</a>
                    </li>
                    <li>
                        <a href="{{ $paginator->url($paginator->lastPage()) }}" class="btn btn-sm" aria-label="Last Page">»</a>
                    </li>
                @else
                    <li>
                        <span class="btn btn-disabled btn-sm">›</span>
                    </li>
                    <li>
                        <span class="btn btn-disabled btn-sm">»</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif 