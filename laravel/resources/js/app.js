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
import { io } from 'socket.io-client';
import './dialer/webrtc-client';
import aiAgentBoxComponent from './dialer/ai-agent-box';

// Make Popper available globally with the correct structure
window.Popper = Popper;
// Expose socket.io client for pages that need realtime events (e.g. inbound calls).
window.io = io;

// Register slug generator component with Alpine.
Alpine.data('slugGenerator', (initialTitle = '', initialSlug = '') => {
  return SlugGenerator.alpineComponent(initialTitle, initialSlug);
});

// Register advanced fields component with Alpine.
Alpine.data('advancedFields', (initialMetadata = {}) => {
  return {
    fields: [],
    initialized: false,

    init() {
      // Convert oldMetadata to fields array.
      if (initialMetadata && Object.keys(initialMetadata).length > 0) {
        this.fields = Object.entries(initialMetadata).map(([key, data]) => {
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

      // If no fields are provided, add an empty one.
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
      this.fields.splice(index, 1);
    },
    get fieldsJson() {
      return this.initialized ? JSON.stringify(this.fields) : '[]';
    }
  };
});

// Register AI agent box component with Alpine.
Alpine.data('aiAgentBox', aiAgentBoxComponent);

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
    const copyButton = document.getElementById("copy-button");
    const copyText = document.getElementById("copy-text");
    const websiteInput = document.getElementById("website-input");

    // Event listener for copy button click
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

  // Function to focus on search input
  function focusSearchInput() {
    if (searchInput) searchInput.focus();
  }

  if (searchInput && searchButton) {
    searchButton.addEventListener("click", focusSearchInput);
  }

  // Keyboard shortcut: Cmd/Ctrl + K to focus search
  document.addEventListener("keydown", function (i) {
    if ((i.metaKey || i.ctrlKey) && i.key === "k") {
      i.preventDefault();
      focusSearchInput();
    }
  });

  // Keyboard shortcut: "/" to focus search (when not in an input)
  document.addEventListener("keydown", function (i) {
    if (i.key === "/" && document.activeElement !== searchInput) {
      i.preventDefault();
      focusSearchInput();
    }
  });
});

// Toast notification helper function
window.showToast = function (variant, title, message) {
  window.dispatchEvent(new CustomEvent("notify", {
    detail: { variant, title, message }
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
        const input = modal.querySelector('input[name="log_date"]');
        if (input && !input.value) {
          const now = new Date().toISOString().split("T")[0];
          input.value = now;
        }
      }
    });
  });

  // Close modal on .modal-close click
  document.querySelectorAll(".modal-close").forEach(btn => {
    btn.addEventListener("click", function () {
      const modal = this.closest(".modal");
      if (modal) modal.classList.add("hidden");
    });
  });
});