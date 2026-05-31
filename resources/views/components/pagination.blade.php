@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between sm:justify-center gap-2 mt-4 mb-2">
        
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-caramel-dark/40 bg-latte/10 rounded-xl cursor-not-allowed border border-latte/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-espresso bg-white border border-latte/60 rounded-xl hover:bg-caramel/20 hover:border-caramel transition-all duration-200 shadow-sm hover:shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="hidden sm:flex items-center gap-1.5">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-caramel-dark cursor-default">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-bold text-cream bg-espresso border border-espresso rounded-xl shadow-md cursor-default transform scale-105 transition-transform z-10">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-espresso bg-white border border-latte/60 rounded-xl hover:bg-caramel/10 hover:text-espresso hover:border-caramel transition-all duration-200 shadow-sm hover:shadow" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-espresso bg-white border border-latte/60 rounded-xl hover:bg-caramel/20 hover:border-caramel transition-all duration-200 shadow-sm hover:shadow">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        @else
            <span class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-medium text-caramel-dark/40 bg-latte/10 rounded-xl cursor-not-allowed border border-latte/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </span>
        @endif
    </nav>
@endif
