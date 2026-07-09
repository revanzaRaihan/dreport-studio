@if($paginator->hasPages())
<div class="pagination-wrap">
    <span class="page-info">
        Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
    </span>
    <div class="pagination-links">
        {{-- Prev --}}
        @if($paginator->onFirstPage())
            <span class="disabled">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">‹</a>
        @endif

        {{-- Page numbers --}}
        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage()-2), min($paginator->lastPage(), $paginator->currentPage()+2)) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next">›</a>
        @else
            <span class="disabled">›</span>
        @endif
    </div>
</div>
@endif
