<?php
function buildUrl($params_to_update) {
     $current_params = $_GET;
     // Merge current params with updates, allowing updates to override
     $params = array_merge($current_params, $params_to_update);
     return '?' . http_build_query($params);
 }

function getSortIcon($field, $current_sort_field, $current_direction) {
    if ($field !== $current_sort_field) {
        return '↕️';
    }
    return $current_direction === 'asc' ? '↑' : '↓';
}

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}