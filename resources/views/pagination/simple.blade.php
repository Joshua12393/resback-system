@if ($paginator->hasPages())
    <nav class="resback-pagination" role="navigation" aria-label="Pagination Navigation">
        <p class="pagination-summary">Page {{ $paginator->currentPage() }}</p>

        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-link pagination-arrow is-disabled" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
            @else
                <a class="pagination-link pagination-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
            @endif

            <span class="pagination-link is-current" aria-current="page">{{ $paginator->currentPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="pagination-link pagination-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
            @else
                <span class="pagination-link pagination-arrow is-disabled" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
