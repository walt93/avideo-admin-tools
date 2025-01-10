<?php
// includes/CategoryManager.php

class CategoryManager {
    private $db;
    
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }
    
    public function buildCategoryTree($parentId = 0) {
        $categories = $this->db->getSubcategories($parentId);
        
        $result = [];
        foreach ($categories as $category) {
            $children = $this->buildCategoryTree($category['id']);
            $result[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'children' => $children
            ];
        }
        return $result;
    }
    
    public function getCategoryPath($categoryId) {
        $path = [];
        while ($categoryId > 0) {
            $category = $this->db->getCategoryById($categoryId);
            if ($category) {
                array_unshift($path, [
                    'id' => $category['id'],
                    'name' => $category['name']
                ]);
                $categoryId = $category['parentId'];
            } else {
                break;
            }
        }
        return $path;
    }
}
