<?php
function buildUrl($params_to_update) {
    $params = $_GET;
    foreach ($params_to_update as $key => $value) {
        $params[$key] = $value;
    }
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