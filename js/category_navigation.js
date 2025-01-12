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
            console.log("the response: ", response);
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

    async function loadSubcategories(level, parentId) {
        // Clear and disable lower-level dropdowns first
        for (let i = level + 1; i <= 3; i++) {
            const select = document.getElementById(`categoryLevel${i}`);
            select.innerHTML = '<option value="">Select Subcategory...</option>';
            select.disabled = true;
        }

        if (!parentId) {
            if (level === 1) {
                window.location.href = '?';
            }
            return;
        }

        try {
            const response = await fetch(`?ajax=subcategories&parent=${parentId}`);
            console.log("Subcategories response:", response);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log("Parsed response:", result);

            if (!result.success) {
                throw new Error(result.error || 'Failed to load subcategories');
            }

            // Populate the next level
            const nextLevel = level + 1;
            if (nextLevel <= 3) {
                const select = document.getElementById(`categoryLevel${nextLevel}`);
                select.innerHTML = '<option value="">Select Subcategory...</option>';

                result.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
                select.disabled = false;
            }

            // Update filter if this is the final selection or there are no subcategories
            if (nextLevel > 3 || result.data.length === 0) {
                updateCategoryFilter(parentId);
            }

        } catch (error) {
            console.error('Error loading subcategories:', error);
            alert('Failed to load subcategories. Please try again.');
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
