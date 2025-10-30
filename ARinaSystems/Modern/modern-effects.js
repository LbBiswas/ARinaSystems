// Modern Effects and Animations for ARina Systems Website
// This file contains all the interactive effects, animations, and modern UI behaviors
// that enhance the user experience across the website

/**
 * ParticleSystem Class
 * Creates and manages floating particle animations in the background
 * These particles add visual depth and modern aesthetic to the website
 */
class ParticleSystem {
  /**
   * Constructor - Initializes the particle system
   * Sets up an empty particles array and calls the initialization method
   */
  constructor() {
    this.particles = []; // Array to store particle elements for future reference
    this.init(); // Start the particle system setup
  }

  /**
   * init() - Sets up the particle system
   * Creates dynamic floating particles and adds them to the background
   * Particles have random sizes, positions, and animation timings for natural movement
   */
  init() {
    // Find the particle container element in the DOM
    const particleContainer = document.querySelector('.particles-bg');
    if (!particleContainer) return; // Exit if no particle container exists

    // Create 15 additional floating particles dynamically
    for (let i = 0; i < 15; i++) {
      // Create a new div element for each particle
      const particle = document.createElement('div');
      particle.className = 'particle'; // Apply CSS class for styling and animation
      
      // Set random size between 2px and 10px
      particle.style.width = Math.random() * 8 + 2 + 'px';
      particle.style.height = particle.style.width; // Keep particles circular
      
      // Position particles randomly across the viewport
      particle.style.left = Math.random() * 100 + '%'; // Random horizontal position
      particle.style.top = Math.random() * 100 + '%';  // Random vertical position
      
      // Add random animation timing for natural, staggered movement
      particle.style.animationDelay = Math.random() * 6 + 's'; // Delay between 0-6 seconds
      particle.style.animationDuration = (Math.random() * 4 + 4) + 's'; // Duration between 4-8 seconds
      
      // Add the particle to the background container
      particleContainer.appendChild(particle);
    }
  }
}

/**
 * SmoothScroller Class
 * Handles smooth scrolling animations for internal navigation links
 * Provides a better user experience when clicking anchor links
 */
class SmoothScroller {
  /**
   * Constructor - Initializes smooth scrolling functionality
   */
  constructor() {
    this.initSmoothScroll(); // Set up smooth scroll event listeners
  }

  /**
   * initSmoothScroll() - Sets up smooth scrolling for all anchor links
   * Replaces default browser jump behavior with smooth animations
   * Works with any link that starts with "#" (internal page links)
   */
  initSmoothScroll() {
    // Find all anchor links that point to sections on the same page
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      // Add click event listener to each anchor link
      anchor.addEventListener('click', function (e) {
        e.preventDefault(); // Prevent default browser jump behavior
        
        // Find the target element that the link points to
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          // Smoothly scroll to the target element
          target.scrollIntoView({
            behavior: 'smooth', // Use smooth scrolling animation
            block: 'start'      // Align target to top of viewport
          });
        }
      });
    });
  }
}

/**
 * ScrollAnimations Class
 * Manages scroll-triggered animations using Intersection Observer API
 * Elements animate into view when they become visible during scrolling
 * Provides performance-optimized scroll detection and staggered animations
 */
class ScrollAnimations {
  /**
   * Constructor - Initializes the scroll animation system
   */
  constructor() {
    this.initObserver(); // Set up Intersection Observer for scroll animations
  }

  /**
   * initObserver() - Sets up Intersection Observer to detect when elements enter viewport
   * Uses modern browser API for efficient scroll monitoring without constant scroll events
   * Applies animations and staggered effects when elements become visible
   */
  initObserver() {
    // Configuration options for the Intersection Observer
    const options = {
      threshold: 0.1,                    // Trigger when 10% of element is visible
      rootMargin: '0px 0px -50px 0px'   // Trigger 50px before element fully enters viewport
    };

    // Create the Intersection Observer with callback function
    const observer = new IntersectionObserver((entries) => {
      // Process each element that has entered or left the viewport
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Element is now visible - add animation class
          entry.target.classList.add('animate-in');
          
          // Add stagger effect for multiple elements (like service boxes)
          // This creates a wave-like animation when multiple items appear together
          if (entry.target.classList.contains('service-box')) {
            // Calculate the element's position among its siblings
            const index = Array.from(entry.target.parentNode.children).indexOf(entry.target);
            // Apply incremental delay based on position (0.1s per item)
            entry.target.style.animationDelay = (index * 0.1) + 's';
          }
        }
      });
    }, options);

    // Observe all elements that should animate on scroll
    // Target elements: service boxes, floating cards, glass elements, and reveal elements
    document.querySelectorAll('.service-box, .floating-card, .glass, .reveal').forEach(el => {
      observer.observe(el); // Start observing each element
    });
  }
}

/**
 * DynamicBackground Class
 * Creates dynamic background color changes based on scroll position
 * Provides an immersive experience by shifting background hues as user scrolls
 * Uses requestAnimationFrame for smooth, performance-optimized updates
 */
class DynamicBackground {
  /**
   * Constructor - Initializes dynamic background system
   */
  constructor() {
    this.initBackgroundChange(); // Set up scroll-based background changes
  }

  /**
   * initBackgroundChange() - Sets up scroll-triggered background color transitions
   * Changes background gradient colors based on scroll position for dynamic visual effect
   * Uses performance optimization to prevent excessive updates
   */
  initBackgroundChange() {
    let ticking = false; // Flag to prevent excessive animation frame requests

    /**
     * updateBackground() - Updates background gradient based on current scroll position
     * Calculates new hue values and applies them to create shifting color effect
     */
    const updateBackground = () => {
      const scrolled = window.pageYOffset; // Get current scroll position
      const rate = scrolled * -0.5;        // Calculate parallax rate (unused in current implementation)
      const body = document.body;          // Get body element for background application
      
      // Calculate dynamic hue values based on scroll position
      // Colors shift gradually as user scrolls down the page
      const hue1 = 210 + (scrolled * 0.1) % 360; // Primary hue (blue to purple range)
      const hue2 = 240 + (scrolled * 0.15) % 360; // Secondary hue (purple to blue range)
      
      // Apply the new gradient background with calculated hue values
      // Creates a smooth transition between colors as user scrolls
      body.style.background = `linear-gradient(135deg, hsl(${hue1}, 70%, 60%) 0%, hsl(${hue2}, 70%, 50%) 100%)`;
      body.style.backgroundAttachment = 'fixed'; // Keep background fixed for parallax effect
      
      ticking = false; // Reset ticking flag to allow next animation frame
    };

    /**
     * requestTick() - Optimized scroll handler using requestAnimationFrame
     * Prevents excessive background updates by limiting to browser's refresh rate
     * Ensures smooth performance even during rapid scrolling
     */
    const requestTick = () => {
      if (!ticking) {
        requestAnimationFrame(updateBackground); // Schedule update on next frame
        ticking = true; // Set flag to prevent multiple requests
      }
    };

    // Listen for scroll events and request background updates
    window.addEventListener('scroll', requestTick);
  }
}

/**
 * MouseFollower Class
 * Creates an interactive light effect that follows the mouse cursor in the hero section
 * Adds an engaging visual element that responds to user mouse movement
 * Uses subtle gradient effects for modern, professional appearance
 */
class MouseFollower {
  /**
   * Constructor - Initializes the mouse following effect
   */
  constructor() {
    this.initMouseFollower(); // Set up mouse tracking and visual effects
  }

  /**
   * initMouseFollower() - Creates and manages the mouse-following light effect
   * Creates a glowing circle that follows mouse movement within the hero section
   * Provides smooth transitions and hover states for better interaction feedback
   */
  initMouseFollower() {
    // Find the hero section where the effect should be active
    const hero = document.querySelector('.hero');
    if (!hero) return; // Exit if no hero section exists

    // Create the follower element (glowing circle)
    const follower = document.createElement('div');
    follower.className = 'mouse-follower'; // Apply CSS class for styling
    
    // Apply inline styles for the glowing effect
    follower.style.cssText = `
      position: absolute;          /* Position relative to hero section */
      width: 200px;               /* Size of the glow effect */
      height: 200px;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
      border-radius: 50%;         /* Make it circular */
      pointer-events: none;       /* Don't interfere with mouse interactions */
      transform: translate(-50%, -50%); /* Center on mouse position */
      transition: all 0.3s ease;  /* Smooth movement transitions */
      z-index: 1;                 /* Layer above background but below content */
    `;
    
    // Add the follower element to the hero section
    hero.appendChild(follower);

    // Track mouse movement within the hero section
    hero.addEventListener('mousemove', (e) => {
      // Calculate mouse position relative to hero section
      const rect = hero.getBoundingClientRect();
      const x = e.clientX - rect.left; // X coordinate within hero
      const y = e.clientY - rect.top;  // Y coordinate within hero
      
      // Update follower position to match mouse
      follower.style.left = x + 'px';
      follower.style.top = y + 'px';
    });

    // Hide follower when mouse leaves hero section
    hero.addEventListener('mouseleave', () => {
      follower.style.opacity = '0'; // Fade out effect
    });

    // Show follower when mouse enters hero section
    hero.addEventListener('mouseenter', () => {
      follower.style.opacity = '1'; // Fade in effect
    });
  }
}

/**
 * TypingAnimation Class
 * Creates typewriter-style text animations for elements with data-typing attribute
 * Simulates text being typed character by character for engaging text reveals
 * Commonly used for hero headlines or important announcements
 */
class TypingAnimation {
  /**
   * Constructor - Initializes typing animation system
   */
  constructor() {
    this.initTypingAnimation(); // Set up typewriter effects for marked elements
  }

  /**
   * initTypingAnimation() - Sets up typewriter effect for elements with data-typing attribute
   * Finds all elements marked for typing animation and creates character-by-character reveal
   * Each element can have its own text content defined in the data-typing attribute
   */
  initTypingAnimation() {
    // Find all elements that should have typing animation
    const elements = document.querySelectorAll('[data-typing]');
    
    // Process each element individually
    elements.forEach(element => {
      // Get the text content from the data attribute
      const text = element.getAttribute('data-typing');
      element.textContent = ''; // Clear existing content
      
      let i = 0; // Character index counter
      
      // Create timer to add characters one by one
      const timer = setInterval(() => {
        if (i < text.length) {
          // Add next character to the element
          element.textContent += text.charAt(i);
          i++; // Move to next character
        } else {
          // Animation complete - clear the timer
          clearInterval(timer);
        }
      }, 100); // 100ms delay between characters (adjustable for speed)
    });
  }
}

/**
 * PerformanceMonitor Class
 * Monitors and optimizes website performance based on device capabilities
 * Implements lazy loading for images and reduces animations on low-performance devices
 * Ensures smooth experience across all device types
 */
class PerformanceMonitor {
  /**
   * Constructor - Initializes performance monitoring and optimization
   */
  constructor() {
    this.initPerformanceChecks(); // Set up performance optimizations
  }

  /**
   * initPerformanceChecks() - Implements performance optimizations
   * Sets up lazy loading for images and adapts animations based on device performance
   * Helps maintain smooth performance on older or less powerful devices
   */
  initPerformanceChecks() {
    // Lazy loading implementation for images with data-src attribute
    // Images only load when they're about to enter the viewport
    const images = document.querySelectorAll('img[data-src]');
    
    // Create Intersection Observer for lazy loading
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Image is about to be visible - load it now
          const img = entry.target;
          img.src = img.dataset.src;        // Set actual image source
          img.classList.remove('lazy');     // Remove lazy loading class
          imageObserver.unobserve(img);     // Stop observing this image
        }
      });
    });

    // Start observing all lazy-load images
    images.forEach(img => imageObserver.observe(img));

    // Performance adaptation: Reduce animations on low-performance devices
    // Check if device has fewer than 4 CPU cores (indicator of lower performance)
    if (navigator.hardwareConcurrency < 4) {
      // Add class that reduces animation complexity and duration
      document.body.classList.add('reduce-motion');
    }
  }
}

/**
 * DOM Content Loaded Event Handler
 * Main initialization function that runs when the HTML document is fully loaded
 * Sets up all modern effects, animations, and interactive features
 * Ensures all DOM elements are available before initializing components
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 Modern Effects: DOM Content Loaded - Starting initialization...');
  
  /**
   * Dynamic CSS Injection
   * Adds essential CSS animations and styles directly via JavaScript
   * Ensures animations work even if external CSS fails to load
   */
  const style = document.createElement('style');
  style.textContent = `
    /* Animation class applied by ScrollAnimations for slide-in effects */
    .animate-in {
      animation: slideInUp 0.8s ease-out forwards;
    }
    
    /* Keyframe animation for elements entering viewport */
    @keyframes slideInUp {
      from {
        transform: translateY(50px);  /* Start 50px below final position */
        opacity: 0;                   /* Start invisible */
      }
      to {
        transform: translateY(0);     /* End at final position */
        opacity: 1;                   /* End fully visible */
      }
    }
    
    /* Performance optimization: Reduce animations on low-end devices */
    .reduce-motion * {
      animation-duration: 0.01ms !important;        /* Nearly instant animations */
      animation-iteration-count: 1 !important;      /* No repeating animations */
      transition-duration: 0.01ms !important;       /* Nearly instant transitions */
    }
    
    /* Visual enhancement for mouse follower effect */
    .mouse-follower {
      mix-blend-mode: screen;  /* Blend mode for glowing effect */
    }
  `;
  
  // Inject the styles into the document head
  document.head.appendChild(style);
  console.log('✅ Dynamic CSS injected successfully');

  /**
   * Component Initialization
   * Create instances of all effect classes to activate website features
   * Each component runs independently and manages its own functionality
   */
  
  try {
    // Initialize particle background system
    console.log('🎨 Initializing ParticleSystem...');
    new ParticleSystem();
    console.log('✅ ParticleSystem initialized');
    
    // Initialize smooth scrolling for navigation links
    console.log('📜 Initializing SmoothScroller...');
    new SmoothScroller();
    console.log('✅ SmoothScroller initialized');
    
    // Initialize scroll-triggered animations
    console.log('🎭 Initializing ScrollAnimations...');
    new ScrollAnimations();
    console.log('✅ ScrollAnimations initialized');
    
    // Initialize dynamic background color changes
    console.log('🌈 Initializing DynamicBackground...');
    new DynamicBackground();
    console.log('✅ DynamicBackground initialized');
    
    // Initialize mouse-following light effect
    console.log('🖱️ Initializing MouseFollower...');
    new MouseFollower();
    console.log('✅ MouseFollower initialized');
    
    // Initialize typewriter text animations
    console.log('⌨️ Initializing TypingAnimation...');
    new TypingAnimation();
    console.log('✅ TypingAnimation initialized');
    
    // Initialize performance monitoring and optimizations
    console.log('⚡ Initializing PerformanceMonitor...');
    new PerformanceMonitor();
    console.log('✅ PerformanceMonitor initialized');
    
    console.log('🎉 All Modern Effects initialized successfully!');
    
  } catch (error) {
    console.error('❌ Error during Modern Effects initialization:', error);
  }

  /**
   * Loading Complete Handler
   * Adds 'loaded' class to body after brief delay
   * Allows for CSS transitions from loading state to fully loaded state
   */
  setTimeout(() => {
    document.body.classList.add('loaded');
  }, 500); // 500ms delay to ensure smooth loading transition
});

/**
 * Utility Functions Object
 * Collection of reusable helper functions for performance and common operations
 * Available globally for use by other scripts or future enhancements
 */
const utils = {
  /**
   * debounce() - Performance optimization function
   * Limits function execution frequency to improve performance during rapid events
   * Commonly used with scroll, resize, and input events
   * 
   * @param {Function} func - Function to be debounced
   * @param {number} wait - Delay in milliseconds before function execution
   * @param {boolean} immediate - Whether to execute immediately on first call
   * @returns {Function} - Debounced version of the original function
   */
  debounce: (func, wait, immediate) => {
    let timeout; // Variable to store timeout reference
    
    return function executedFunction(...args) {
      // Function that will replace the original
      const later = () => {
        timeout = null; // Clear timeout reference
        if (!immediate) func(...args); // Execute function if not immediate mode
      };
      
      const callNow = immediate && !timeout; // Determine if should execute immediately
      clearTimeout(timeout); // Cancel previous timeout
      timeout = setTimeout(later, wait); // Set new timeout
      
      if (callNow) func(...args); // Execute immediately if in immediate mode
    };
  },

  /**
   * random() - Generate random number within specified range
   * Utility function for creating random values for animations and effects
   * 
   * @param {number} min - Minimum value (inclusive)
   * @param {number} max - Maximum value (exclusive)
   * @returns {number} - Random number between min and max
   */
  random: (min, max) => Math.random() * (max - min) + min,

  /**
   * isInViewport() - Check if element is currently visible in viewport
   * Useful for triggering animations or loading content when elements are visible
   * 
   * @param {Element} element - DOM element to check
   * @returns {boolean} - True if element is fully visible in viewport
   */
  isInViewport: (element) => {
    const rect = element.getBoundingClientRect(); // Get element position and size
    return (
      rect.top >= 0 &&    // Element top is visible
      rect.left >= 0 &&   // Element left is visible
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && // Element bottom is visible
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)      // Element right is visible
    );
  }
};

/**
 * Global Export Object
 * Makes all classes and utilities available globally for external use
 * Allows other scripts to access and extend the modern effects system
 * Useful for custom implementations or third-party integrations
 */
window.ModernEffects = {
  // Core effect classes
  ParticleSystem,      // Background particle animations
  SmoothScroller,      // Smooth scrolling navigation
  ScrollAnimations,    // Scroll-triggered animations
  DynamicBackground,   // Dynamic background colors
  MouseFollower,       // Mouse-following effects
  TypingAnimation,     // Typewriter text effects
  PerformanceMonitor,  // Performance optimizations
  
  // Utility functions
  utils                // Helper functions for common operations
};