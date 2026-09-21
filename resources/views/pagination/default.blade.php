@if ($paginator->hasPages())
    <nav class="resback-pagination" role="navigation" aria-label="Pagination Navigation">
        <p class="pagination-summary">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </p>

        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-link pagination-arrow is-disabled" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
            @else
                <a class="pagination-link pagination-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-link is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-link pagination-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
            @else
                <span class="pagination-link pagination-arrow is-disabled" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
