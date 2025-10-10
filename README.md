# Studio Bagosi - Photography Portfolio Website

A modern, responsive photography portfolio website with dark mode support for Studio Bagosi.

## ✨ Features

### 🎨 **Dark Mode Support**
- **Navbar Toggle**: Dark mode toggle button integrated in the navigation bar
- **Automatic Detection**: Automatically follows system theme preference (dark/light mode)
- **Inverted Color Scheme**: White elements become dark blue, dark blue elements become white
- **Smooth Transitions**: Seamless animations between light and dark modes
- **Persistent Preference**: Manual user choice overrides system preference and is saved
- **Dynamic Icons**: Moon icon for light mode, sun icon for dark mode
- **Responsive Design**: Mobile-friendly toggle button placement

### 🌟 **Core Features**
- **Responsive Design**: Mobile-friendly layout that works on all devices
- **Static Content**: No database required - all content is hardcoded for simplicity
- **Gallery System**: Multiple gallery categories with placeholder images
- **Package Display**: Photography service packages with pricing
- **Contact Form**: Client-side form validation with contact information
- **Smooth Animations**: Parallax effects, hover animations, and scroll-based animations

### 📱 **Pages**
- **Homepage** (`index.php`): Main landing page with hero section, gallery preview, packages, and contact
- **Gallery View** (`gallery_view.php`): Detailed gallery viewer with category navigation

## 🎯 **Color Scheme**

### Light Mode (Default)
- **Primary**: `#1a237e` (Deep blue)
- **Background**: White gradients
- **Text**: Dark blue
- **Cards**: Semi-transparent white

### Dark Mode
- **Primary**: `#ffffff` (White)
- **Background**: Dark blue gradients
- **Text**: White
- **Cards**: Semi-transparent dark blue

## 🚀 **Usage**

### Basic Setup
1. Place files in your web server directory
2. No database configuration needed
3. Access `index.php` in your browser

### Dark Mode Toggle
- Click the toggle button in the navigation bar
- Automatically detects and follows your system's dark/light mode preference
- Manual selection overrides system preference and is saved
- Works across all pages

### Customization
- Edit arrays in `index.php` to modify packages and gallery content
- Update `css/style.css` to customize colors and styling
- Modify `js/main.js` to add new interactive features

## 📁 **Project Structure**

```
StudioBagosi/
├── clips/                    # Hero video
│   └── trailer.mp4
├── css/                      # Stylesheets
│   └── style.css            # Main CSS with dark mode variables
├── images/                   # Images and assets
│   ├── Logo/                # Logo files
│   └── gallery/             # Gallery placeholder images
├── js/                       # JavaScript
│   └── main.js              # Main JS with dark mode functionality
├── index.php                # Homepage
├── gallery_view.php         # Gallery viewer
└── README.md               # This file
```

## 🎨 **Dark Mode Implementation**

The dark mode system uses CSS custom properties (variables) to create a seamless theme switching experience:

### CSS Variables
```css
:root {
    /* Light mode colors */
    --primary-color: #1a237e;
    --text-color: #1a237e;
    --body-bg: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    /* ... */
}

[data-theme="dark"] {
    /* Dark mode colors */
    --primary-color: #ffffff;
    --text-color: #ffffff;
    --body-bg: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
    /* ... */
}
```

### JavaScript Toggle with System Detection
```javascript
// Automatic system theme detection
function getSystemTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

// Theme initialization with system preference fallback
const savedTheme = localStorage.getItem('theme');
const systemTheme = getSystemTheme();
const initialTheme = savedTheme || systemTheme;

// Manual theme switching
const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
body.setAttribute('data-theme', newTheme);
localStorage.setItem('theme', newTheme);
```

## 🔧 **Technical Details**

- **PHP**: Static content arrays, no database dependencies
- **CSS**: Modern CSS with custom properties and advanced animations
- **JavaScript**: Vanilla JS with localStorage for theme persistence
- **Bootstrap**: 5.3.0 for responsive grid and components
- **Font Awesome**: 6.0.0 for icons
- **Google Fonts**: Poppins font family

## 📱 **Responsive Design**

- **Mobile-first approach**
- **Flexible grid system**
- **Touch-friendly navigation**
- **Optimized dark mode toggle placement**

## 🎭 **Animations & Effects**

- **Smooth theme transitions**
- **Parallax hero section**
- **Hover animations on cards**
- **Scroll-based element animations**
- **Glass morphism effects**

## 📞 **Contact Integration**

- **Contact form with validation**
- **Social media links**
- **Direct contact information display**
- **Mobile-responsive contact section**

---

**Studio Bagosi** - Capturing your special moments with passion and creativity. 