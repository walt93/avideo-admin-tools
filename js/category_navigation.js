// js/category_navigation.js

class CategoryNavigation {
    constructor() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category');
            if (categoryId) {
                this.loadActiveCategoryPath(categoryId);
            }
        });
    }

    async loadActiveCategoryPath(categoryId) {
        try {
            const response = await fetch(`?ajax=category_path&id=${categoryId}`);
            const path = await response.json();

            if (path.length > 0) {
                // Load each level sequentially
                for (let i = 0; i < path.length; i++) {
                    const select = document.getElementById(`categoryLevel${i + 1}`);
                    select.value = path[i].id;
                    if (i < path.length - 1) {
                        await this.loadSubcategories(i + 1, path[i].id);
                    }
                }
            }
        } catch (error) {
            console.error('Error loading category path:', error);
        }
    }

    async loadSubcategories(level, parentId) {
        if (!parentId) {
            // If no parent selected, disable and clear lower levels
            for (let i = level + 1; i <= 3; i++) {
                const select = document.getElementById(`categoryLevel${i}`);
                select.innerHTML = '<option value="">Select Subcategory...</option>';
                select.disabled = true;
            }
            // Update URL to remove category filter
            if (level === 1) {
                window.location.href = '?';
            }
            return;
        }

        try {
            const response = await fetch(`?ajax=subcategories&parent=${parentId}`);
            const categories = await response.json();

            // Populate the next level
            const nextLevel = level + 1;
            if (nextLevel <= 3) {
                const select = document.getElementById(`categoryLevel${nextLevel}`);
                select.innerHTML = '<option value="">Select Subcategory...</option>';
                categories.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
                select.disabled = false;

                // Disable and clear any levels below
                for (let i = nextLevel + 1; i <= 3; i++) {
                    const select = document.getElementById(`categoryLevel${i}`);
                    select.innerHTML = '<option value="">Select Subcategory...</option>';
                    select.disabled = true;
                }
            }

            // If this is the final selection or there are no subcategories, update the filter
            if (nextLevel > 3 || categories.length === 0) {
                this.updateCategoryFilter(parentId);
            }
        } catch (error) {
            console.error('Error loading subcategories:', error);
        }
    }

    updateCategoryFilter(categoryId) {
        // Update URL with selected category
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('playlist'); // Clear playlist filter when changing category
        urlParams.set('category', categoryId);
        window.location.href = `?${urlParams.toString()}`;
    }
}

// Initialize the category navigation
const categoryNav = new CategoryNavigation();

// Expose the loadSubcategories function globally
loadSubcategories = function(level, parentId) {
    return categoryNav.loadSubcategories(level, parentId);
};
