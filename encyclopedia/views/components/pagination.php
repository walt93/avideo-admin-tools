<?php
// New file: views/components/pagination.php

function renderPagination($pagination, $current_params = []) {
    $current_page = $pagination['current_page'];
    $total_pages = $pagination['total_pages'];

    // Don't show pagination if there's only one page
    if ($total_pages <= 1) return;

    // Helper function to build URLs
    $buildPageUrl = function($page) use ($current_params) {
        $params = $current_params;
        $params['page'] = $page;
        return '?' . http_build_query($params);
    };

    echo '<div class="pagination">';

    // First page and previous 10
    if ($current_page > 1) {
        echo '<a href="' . $buildPageUrl(1) . '" class="page-control">«</a>';
        if ($current_page > 10) {
            echo '<a href="' . $buildPageUrl($current_page - 10) . '" class="page-control">-10</a>';
        }
    }

    // Previous page
    if ($current_page > 1) {
        echo '<a href="' . $buildPageUrl($current_page - 1) . '" class="page-control">‹</a>';
    }

    // Page numbers
    $start = max(1, min($current_page - 2, $total_pages - 4));
    $end = min($total_pages, max(5, $current_page + 2));

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            echo '<span class="page-current">' . $i . '</span>';
        } else {
            echo '<a href="' . $buildPageUrl($i) . '" class="page-number">' . $i . '</a>';
        }
    }

    // Next page
    if ($current_page < $total_pages) {
        echo '<a href="' . $buildPageUrl($current_page + 1) . '" class="page-control">›</a>';
    }

    // Next 10 and last page
    if ($current_page < $total_pages) {
        if ($current_page < $total_pages - 10) {
            echo '<a href="' . $buildPageUrl($current_page + 10) . '" class="page-control">+10</a>';
        }
        echo '<a href="' . $buildPageUrl($total_pages) . '" class="page-control">»</a>';
    }

    echo '</div>';
}
?>

<style>
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
    gap: 5px;
}

.pagination a, .pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
    min-width: 24px;
    text-align: center;
}

.pagination .page-control {
    background: #f8f9fa;
}

.pagination .page-current {
    background: #007bff;
    color: white;
    border-color: #0056b3;
}

.pagination a:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}

.entries-per-page {
    margin: 20px 0;
    text-align: right;
}

.entries-per-page select {
    margin-left: 10px;
    padding: 5px;
}
</style>