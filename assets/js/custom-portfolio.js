jQuery(window).on('load', function () {


    // Aqui eu consigo alternar entre a versao paginada ou normal, apenas pela classe
    // vamos deixar tudo no mesmo arquivo, e mudar a classe atraves do elementor.
    // vamos precisar passar algumas informacoes para o JS (numero de paginas, etc)

    // Talvez de para deixar o packery etc tudo na mesma variavel, porque o packery tambem pode ser paginado

    if ( jQuery( ".elpt-portfolio-content" ).length ) {
        //Isotope Layout
        var $grid = jQuery('.elpt-portfolio-content-isotope').isotope({
            //layoutMode: 'packery',
            layoutMode: 'masonry',
            itemSelector: '.portfolio-item-wrapper'
        });
        
        $grid.imagesLoaded().progress( function() {
            $grid.isotope('layout');
        });

        //Packery Layout
        var $packery = jQuery('.elpt-portfolio-content-packery').isotope({
            layoutMode: 'packery',
            itemSelector: '.portfolio-item-wrapper'
        });

        $packery.imagesLoaded().progress( function() {
            $packery.isotope('layout');
        });

        //fitRows Layout (for Special Grid 7 and similar)
        var $fitrows = jQuery('.elpt-portfolio-content-fitrows').isotope({
            layoutMode: 'fitRows',
            itemSelector: '.portfolio-item-wrapper'
        });

        $fitrows.imagesLoaded().progress( function() {
            $fitrows.isotope('layout');
        });

        /*
        * Paginated Isotope
        */
        //https://codepen.io/TimRizzo/details/ervrRq
        //https://codepen.io/Igorxp5/pen/ojJLQE

        var itemSelector = ".portfolio-item-wrapper";

        // Support masonry, packery, and fitRows pagination
        var $container = jQuery('.elpt-portfolio-content-isotope-pro').isotope({
            layoutMode: 'masonry',
            itemSelector: itemSelector
        });

        // Check layout mode based on additional classes
        if ($container.hasClass('elpt-portfolio-content-fitrows')) {
            // Special Grid 7 - Alternate Rows 1
            $container.isotope('option', { layoutMode: 'fitRows' });
        } else if ($container.hasClass('elpt-portfolio-content-packery')) {
            // Grid Builder
            $container.isotope('option', { layoutMode: 'packery' });
        }

        $container.imagesLoaded().progress( function() {
            $container.isotope('layout');
        });
    
        // Pagination variables
        var gridConfig = (typeof powerfolioGridSettings !== 'undefined') ? powerfolioGridSettings : (typeof gridSettings !== 'undefined' ? gridSettings : {});
        var itemsPerPageDefault = gridConfig.itemsPerPageDefault || 10;
        var paginationMode = gridConfig.paginationMode || 'numbers';
        var loadMoreLabel = gridConfig.loadMoreLabel || 'Load more projects';
        var loadingLabel = gridConfig.loadingLabel || 'Loading…';

        var itemsPerPage = defineItemsPerPage();
        var currentNumberPages = 1;
        var currentPage = 1;
        var currentFilter = '*';
        var filterAtribute = 'data-filter';
        var pageAtribute = 'data-page';
        var pagerClass = 'isotope-pager';
        var $portfolioRoot = $container.closest('.elpt-portfolio');
        var $loadMoreBtn = null;
        var infiniteObserver = null;
        var isLoadingMore = false;

        jQuery(window).resize(function () {
            applyPageFilter();
        });

        function changeFilter(selector) {
            $container.isotope({ filter: selector });
        }

        function getFilterSelector() {
            var selector = itemSelector;
            if (currentFilter != '*') {
                selector += currentFilter;
            }
            return selector;
        }

        function itemMatchesCategory($item) {
            if (currentFilter === '*') {
                return true;
            }
            return $item.is(currentFilter);
        }

        function applyPageFilter() {
            var $gridBuilder = jQuery('.elpt-portfolio-content-packery.elpt-portfolio-grid-builder');
            var isFixedLayout = $gridBuilder.length > 0 && $gridBuilder.hasClass('elpt-fixed-layout-mode');

            if (isFixedLayout) {
                applyFixedLayoutPositionsForPage($gridBuilder, currentPage);
            }

            if (paginationMode === 'numbers') {
                var selector = getFilterSelector();
                selector += '[' + pageAtribute + '="' + currentPage + '"]';
                changeFilter(selector);
            } else {
                $container.isotope({
                    filter: function () {
                        var $el = jQuery(this);
                        if (!itemMatchesCategory($el)) {
                            return false;
                        }
                        var page = parseInt($el.attr(pageAtribute), 10) || 1;
                        return page <= currentPage;
                    }
                });
            }

            if (isFixedLayout) {
                $container.isotope('layout');
            }

            updatePaginationUI();
        }

        function goToPage(n) {
            currentPage = parseInt(n, 10) || 1;
            applyPageFilter();
        }

        function defineItemsPerPage() {
            return itemsPerPageDefault;
        }

        function assignPageAttributes() {
            var item = 1;
            var page = 1;
            var selector = getFilterSelector();

            $container.children(selector).each(function () {
                if (item > itemsPerPage) {
                    page++;
                    item = 1;
                }
                jQuery(this).attr(pageAtribute, page);
                item++;
            });

            currentNumberPages = page;
        }

        function updatePaginationUI() {
            if (!$loadMoreBtn || !$loadMoreBtn.length) {
                return;
            }

            if (currentPage >= currentNumberPages) {
                $loadMoreBtn.addClass('is-hidden').prop('disabled', true);
            } else {
                $loadMoreBtn.removeClass('is-hidden is-loading').prop('disabled', false).text(loadMoreLabel);
            }
        }

        function loadNextPage() {
            if (isLoadingMore || currentPage >= currentNumberPages) {
                return;
            }

            isLoadingMore = true;
            if ($loadMoreBtn && $loadMoreBtn.length) {
                $loadMoreBtn.addClass('is-loading').text(loadingLabel);
            }

            window.setTimeout(function () {
                currentPage++;
                applyPageFilter();
                isLoadingMore = false;
            }, 280);
        }

        function createPaginationUI() {
            $portfolioRoot.find('.' + pagerClass).remove();
            $portfolioRoot.find('.elpt-portfolio-pagination').not('[aria-hidden="true"]').remove();

            if (paginationMode === 'load_more') {
                var $wrap = $portfolioRoot.find('.elpt-portfolio-pagination[aria-hidden="true"]');
                if (!$wrap.length) {
                    $wrap = jQuery('<div class="elpt-portfolio-pagination"></div>');
                    $container.after($wrap);
                } else {
                    $wrap.removeAttr('aria-hidden');
                }

                $loadMoreBtn = jQuery('<button type="button" class="elpt-load-more-btn"></button>').text(loadMoreLabel);
                $loadMoreBtn.on('click', function (e) {
                    e.preventDefault();
                    loadNextPage();
                });
                $wrap.empty().append($loadMoreBtn);
                updatePaginationUI();
                return;
            }

            if (paginationMode === 'infinite') {
                var $sentinelWrap = $portfolioRoot.find('.elpt-portfolio-pagination[aria-hidden="true"]');
                if (!$sentinelWrap.length) {
                    $sentinelWrap = jQuery('<div class="elpt-portfolio-pagination"></div>');
                    $container.after($sentinelWrap);
                } else {
                    $sentinelWrap.removeAttr('aria-hidden');
                }

                $sentinelWrap.empty().append('<div class="elpt-infinite-sentinel"></div>');
                var $sentinel = $sentinelWrap.find('.elpt-infinite-sentinel');

                if ('IntersectionObserver' in window) {
                    if (infiniteObserver) {
                        infiniteObserver.disconnect();
                    }
                    infiniteObserver = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                loadNextPage();
                            }
                        });
                    }, { rootMargin: '160px' });
                    infiniteObserver.observe($sentinel[0]);
                }
                return;
            }

            // Numbered pagination (default)
            var $isotopePager = jQuery('.' + pagerClass);
            if ($isotopePager.length === 0) {
                $isotopePager = jQuery('<div class="' + pagerClass + '"></div>');
                $container.after($isotopePager);
            }

            $isotopePager.html('');

            for (var i = 0; i < currentNumberPages; i++) {
                var pageNum = i + 1;
                var $pager = jQuery('<a href="javascript:void(0);" class="pager" ' + pageAtribute + '="' + pageNum + '"></a>');
                $pager.html(pageNum);

                $pager.on('click', function () {
                    jQuery('.isotope-pager .active').removeClass('active');
                    jQuery(this).addClass('active');
                    goToPage(jQuery(this).attr(pageAtribute));
                });

                $isotopePager.append($pager);
            }

            $isotopePager.find('.pager').first().addClass('active');
        }

        function setPagination() {
            assignPageAttributes();
            createPaginationUI();
        }

        setPagination();
        goToPage(1);

        // Fixed Layout Filter function
        // Note: This function only applies position classes to matching items.
        // The actual Isotope filtering is done later by goToPage() to avoid
        // container height being calculated for all items before pagination.
        function applyFixedLayoutFilter($container, filterValue) {
            var $allItems = $container.children('.portfolio-item-wrapper');

            // Determine which items match the filter (without hiding them yet)
            var $matchingItems;
            if (filterValue === '*') {
                $matchingItems = $allItems;
            } else {
                $matchingItems = $allItems.filter(filterValue);
            }

            // Remove all position classes from all items
            $allItems.removeClass(function(index, className) {
                return (className.match(/\belpt-grid-pos-\d+\b/g) || []).join(' ');
            });

            // Apply position classes to matching items in their new visual order
            // This makes item 2 become "position 1" and get position 1's styles (60%)
            $matchingItems.each(function(visualIndex) {
                jQuery(this).addClass('elpt-grid-pos-' + (visualIndex + 1));
            });

            // Note: We intentionally do NOT call isotope({ filter }) here.
            // The filtering will be handled by setPagination() + goToPage()
            // which applies both the category filter AND pagination together,
            // ensuring the container height is calculated correctly.
        }

        // Fixed Layout: Apply position classes to visible items on current page
        // This ensures items get correct styles when paginating in Fixed Layout Mode
        function applyFixedLayoutPositionsForPage($container, pageNumber) {
            var $allItems = $container.children('.portfolio-item-wrapper');

            // Get items that match current filter AND current page
            var selector = getFilterSelector();
            selector += '[' + pageAtribute + '="' + pageNumber + '"]';
            var $visibleItems = $allItems.filter(selector);

            // Remove all position classes from all items
            $allItems.removeClass(function(index, className) {
                return (className.match(/\belpt-grid-pos-\d+\b/g) || []).join(' ');
            });

            // Apply position classes to visible items in their visual order
            $visibleItems.each(function(visualIndex) {
                jQuery(this).addClass('elpt-grid-pos-' + (visualIndex + 1));
            });
        }

        // On Click Actions
        jQuery('.elpt-portfolio-filter').on('click', 'button', function () {
            jQuery('.elpt-portfolio-filter button').removeClass('item-active');
            jQuery(this).addClass('item-active');

            var filterValue = jQuery(this).attr(filterAtribute);
            var filter = filterValue;
            currentFilter = filter;

            // Check if Fixed Layout Mode is enabled (only for Grid Builder)
            var $gridBuilder = jQuery('.elpt-portfolio-content-packery.elpt-portfolio-grid-builder');
            var isFixedLayout = $gridBuilder.hasClass('elpt-fixed-layout-mode');

            if (isFixedLayout) {
                // Fixed Layout: Use CSS visibility instead of Isotope filter
                applyFixedLayoutFilter($gridBuilder, filterValue);
            } else {
                // Normal: Use Isotope filter (current behavior)
                $grid.isotope({
                    filter: filterValue
                });
                $packery.isotope({
                    filter: filterValue
                });
                $fitrows.isotope({
                    filter: filterValue
                });
            }

            setPagination();
            goToPage(1);
        });       
        
    }

});