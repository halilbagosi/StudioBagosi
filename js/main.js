// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Dark Mode Toggle Functionality
    const darkModeToggle = document.getElementById('darkModeToggle');
    const root = document.documentElement;
    const navbarLogo = document.getElementById('navbarLogo');

    function setLogoForTheme(theme) {
        if (!navbarLogo) return;
        if (theme === 'dark') {
            navbarLogo.src = 'images/Logo/BAGOSI.png';
        } else {
            navbarLogo.src = 'images/Logo/BAGOSI_Light.png';
        }
    }
    
    // Function to get system theme preference
    function getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }
    
    // Always follow system preference on load
    const systemTheme = getSystemTheme();
    root.setAttribute('data-theme', systemTheme);
    setLogoForTheme(systemTheme);
    
    // Always follow system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            const newTheme = e.matches ? 'dark' : 'light';
            root.setAttribute('data-theme', newTheme);
            setLogoForTheme(newTheme);
            
            // Smooth transition
            root.style.transition = 'all 0.3s ease';
            setTimeout(() => { root.style.transition = ''; }, 300);
            
            // Update navbar background immediately
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (newTheme === 'dark') {
                    navbar.style.background = 'rgba(26, 35, 126, 0.8)';
                    navbar.style.boxShadow = 'none';
                } else {
                    navbar.style.background = 'rgba(255, 255, 255, 0.8)';
                    navbar.style.boxShadow = 'none';
                }
            }
        });
    }
    
    // Toggle: temporary override (no persistence); system changes will still win later
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = root.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            root.setAttribute('data-theme', newTheme);
            setLogoForTheme(newTheme);
            
            // Smooth transition
            root.style.transition = 'all 0.3s ease';
            setTimeout(() => { root.style.transition = ''; }, 300);
            
            // Update navbar immediately
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (newTheme === 'dark') {
                    navbar.style.background = 'rgba(26, 35, 126, 0.8)';
                    navbar.style.boxShadow = 'none';
                } else {
                    navbar.style.background = 'rgba(255, 255, 255, 0.8)';
                    navbar.style.boxShadow = 'none';
                }
            }
        });
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Navbar background change on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        const currentTheme = root.getAttribute('data-theme');
        
        if (window.scrollY > 50) {
            if (currentTheme === 'dark') {
                navbar.style.background = 'rgba(26, 35, 126, 0.95)';
                navbar.style.boxShadow = '0 2px 10px rgba(255, 255, 255, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        } else {
            if (currentTheme === 'dark') {
                navbar.style.background = 'rgba(26, 35, 126, 0.8)';
                navbar.style.boxShadow = 'none';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.8)';
                navbar.style.boxShadow = 'none';
            }
        }
    });

    // Form validation and submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic form validation
            const formData = new FormData(this);
            let isValid = true;
            
            formData.forEach((value, key) => {
                if (!value && key !== 'phone') {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                alert('Ju lutemi plotësoni të gjitha fushat e kërkuara');
                return;
            }
            
            alert('Faleminderit për mesazhin tuaj! Do t\'ju kontaktojmë së shpejti.');
            this.reset();
        });
    }

    // Add parallax effect to hero section
    window.addEventListener('scroll', function() {
        const hero = document.querySelector('.hero-section');
        if (hero) {
            const scrolled = window.pageYOffset;
            hero.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });

    // Animate elements on scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.card, .gallery-item, .contact-card');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementBottom = element.getBoundingClientRect().bottom;
            
            if (elementTop < window.innerHeight && elementBottom > 0) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };

    // Set initial styles for animation
    document.querySelectorAll('.card, .gallery-item, .contact-card').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    // Listen for scroll events
    window.addEventListener('scroll', animateOnScroll);
    // Initial check for elements in view
    animateOnScroll();
    
    // Initialize navbar background based on current theme
    const navbar = document.querySelector('.navbar');
    const currentTheme = root.getAttribute('data-theme');
    if (currentTheme === 'dark') {
        navbar.style.background = 'rgba(26, 35, 126, 0.8)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.8)';
    }
}); 