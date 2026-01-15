import './bootstrap';
import "jsvectormap/dist/jsvectormap.min.css";
import "flatpickr/dist/flatpickr.min.css";
import "dropzone/dist/dropzone.css";

import Alpine from "alpinejs";
import persist from "@alpinejs/persist";
import focus from '@alpinejs/focus'
import flatpickr from "flatpickr";
import Dropzone from "dropzone";
import 'flowbite';

import chart01 from "./components/charts/chart-01";
import chart02 from "./components/charts/chart-02";
import chart03 from "./components/charts/chart-03";
import userGrowthChart from "./components/charts/user-growth-chart.js";
import map01 from "./components/map-01";
// import "./components/calendar-init.js";
import "./components/image-resize";
import SlugGenerator from "./components/slug-generator";
import * as Popper from '@popperjs/core';

// Make Popper available globally with the correct structure
window.Popper = Popper;

// Register slug generator component with Alpine.
Alpine.data('slugGenerator', (initialTitle = '', initialSlug = '') => {
    return SlugGenerator.alpineComponent(initialTitle, initialSlug);
});

// Register advanced fields component with Alpine.
Alpine.data('advancedFields', (initialMeta = {}) => {
    return {
        fields: [],
        initialized: false,
        
        init() {
            // Convert initial meta object to array format.
            if (initialMeta && Object.keys(initialMeta).length > 0) {
                this.fields = Object.entries(initialMeta).map(([key, data]) => {
                    if (typeof data === 'object' && data !== null && data.value !== undefined) {
                        return {
                            key: key,
                            value: data.value || '',
                            type: data.type || 'input',
                            default_value: data.default_value || ''
                        };
                    } else {
                        // Handle legacy format where data is just the value
                        return {
                            key: key,
                            value: typeof data === 'string' ? data : '',
                            type: 'input',
                            default_value: ''
                        };
                    }
                });
            }
            
            // If no fields exist, add one empty field.
            if (this.fields.length === 0) {
                this.addField();
            }
            
            this.initialized = true;
        },
        
        addField() {
            this.fields.push({
                key: '',
                value: '',
                type: 'input',
                default_value: ''
            });
        },
        
        removeField(index) {
            if (this.fields.length > 1) {
                this.fields.splice(index, 1);
            }
        },
        
        get fieldsJson() {
            return this.initialized ? JSON.stringify(this.fields) : '[]';
        }
    };
});

// Alpine plugins
Alpine.plugin(persist);
Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();

// Init flatpickr
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".datepicker-single").forEach(input => {
    if (!input._flatpickr) {
      flatpickr(input, {
        mode: "single",
        dateFormat: "M j, Y"
      });
    }
  });
});

// Init Dropzone
const dropzoneArea = document.querySelectorAll("#demo-upload");

if (dropzoneArea.length) {
    let myDropzone = new Dropzone("#demo-upload", { url: "/file/post" });
}

// Document Loaded
document.addEventListener("DOMContentLoaded", () => {
    chart01();
    chart02();
    chart03();
    userGrowthChart();
    map01();
});

// Get the current year
const year = document.getElementById("year");
if (year) {
    year.textContent = new Date().getFullYear();
}

// For Copy//
document.addEventListener("DOMContentLoaded", () => {
    const copyInput = document.getElementById("copy-input");
    if (copyInput) {
        // Select the copy button and input field
        const copyButton = document.getElementById("copy-button");
        const copyText = document.getElementById("copy-text");
        const websiteInput = document.getElementById("website-input");

        // Event listener for the copy button
        copyButton.addEventListener("click", () => {
            // Copy the input value to the clipboard
            navigator.clipboard.writeText(websiteInput.value).then(() => {
                // Change the text to "Copied"
                copyText.textContent = "Copied";

                // Reset the text back to "Copy" after 2 seconds
                setTimeout(() => {
                    copyText.textContent = "Copy";
                }, 2000);
            });
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("search-input");
    const searchButton = document.getElementById("search-button");

    // Function to focus the search input
    function focusSearchInput() {
        searchInput.focus();
    }

    if (searchInput) {
        // Add click event listener to the search button
        searchButton.addEventListener("click", focusSearchInput);
    }

    // Add keyboard event listener for Cmd+K (Mac) or Ctrl+K (Windows/Linux)
    document.addEventListener("keydown", function (event) {
        if ((event.metaKey || event.ctrlKey) && event.key === "k") {
            event.preventDefault(); // Prevent the default browser behavior
            focusSearchInput();
        }
    });

    // Add keyboard event listener for "/" key
    document.addEventListener("keydown", function (event) {
        if (event.key === "/" && document.activeElement !== searchInput) {
            event.preventDefault(); // Prevent the "/" character from being typed
            focusSearchInput();
        }
    });
});

// Toast notification helper function
window.showToast = function(variant, title, message) {
    // Dispatch the notify event that the toast component listens for
    window.dispatchEvent(new CustomEvent('notify', {
        detail: {
            variant, // 'success', 'error', 'warning', 'info'
            title,
            message
        }
    }));
};

// Import term drawer functionality
import './term-drawer.js';


document.addEventListener("DOMContentLoaded", () => {
    // Open modal on edit button click
    document.querySelectorAll('[data-modal-target^="editModal-"]').forEach(button => {
        button.addEventListener("click", function () {
            const modalId = this.getAttribute("data-modal-target");
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove("hidden");

                // Set today's date as default in the log_date field
                const dateInput = modal.querySelector('input[name="log_date"]');
                if (dateInput && !dateInput.value) {
                    const today = new Date().toISOString().split("T")[0];
                    dateInput.value = today;
                }
            }
        });
    });

    // Close modal on .modal-close click
    document.querySelectorAll(".modal-close").forEach(btn => {
        btn.addEventListener("click", function () {
            const modal = this.closest(".modal");
            if (modal) {
                modal.classList.add("hidden");
            }
        });
    });
});




