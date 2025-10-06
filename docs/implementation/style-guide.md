# Havasu Lake Burritos - Style Guide

## Brand Identity

### Color Palette

**Primary Colors:**
- **Lake Blue**: `#2563eb` (Primary brand color, representing Havasu Lake)
- **Sunset Orange**: `#ea580c` (Accent color, warm and appetizing)
- **Desert Sand**: `#f5f5dc` (Background neutral)

**Secondary Colors:**
- **Fresh Green**: `#16a34a` (Fresh ingredients indicator)
- **Warm Red**: `#dc2626` (Salsa/spice indicator)
- **Cream**: `#fef7ed` (Light background)

**Grays:**
- **Dark Gray**: `#1f2937` (Text primary)
- **Medium Gray**: `#6b7280` (Text secondary)
- **Light Gray**: `#f3f4f6` (Borders, dividers)

### Typography

**Primary Font**: `Inter` (Modern, clean, highly readable)
- **Headings**: `font-bold` with appropriate sizing
- **Body Text**: `font-normal`
- **Labels**: `font-medium`
- **Captions**: `font-light`

**Font Sizes:**
- **Hero/Main Title**: `text-4xl` (36px)
- **Section Headers**: `text-2xl` (24px)
- **Card Titles**: `text-xl` (20px)
- **Body Text**: `text-base` (16px)
- **Small Text**: `text-sm` (14px)
- **Captions**: `text-xs` (12px)

## UI Components

### Buttons

**Primary Button:**
```css
bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg
```

**Secondary Button:**
```css
bg-orange-100 hover:bg-orange-200 text-orange-700 font-medium px-6 py-3 rounded-lg border border-orange-300
```

**Danger/Remove Button:**
```css
bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg
```

### Cards

**Ingredient Card:**
```css
bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-shadow cursor-pointer
```

**Selected State:**
```css
border-blue-500 ring-2 ring-blue-200 bg-blue-50
```

### Form Elements

**Input Fields:**
```css
border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
```

**Labels:**
```css
text-gray-700 font-medium mb-2
```

## Layout Principles

### Spacing
- **Container max-width**: `max-w-4xl mx-auto`
- **Section padding**: `px-6 py-8`
- **Component spacing**: `space-y-6` for vertical, `space-x-4` for horizontal
- **Card padding**: `p-6` for large cards, `p-4` for smaller

### Grid System
- **Ingredient Grid**: `grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4`
- **Track Steps**: `grid grid-cols-1 md:grid-cols-5 gap-6`

### Responsive Breakpoints
- **Mobile**: Default (< 768px)
- **Tablet**: `md:` (768px+)
- **Desktop**: `lg:` (1024px+)
- **Large Desktop**: `xl:` (1280px+)

## Iconography

### Icon Style
- **Line style**: Outline icons preferred
- **Weight**: Medium stroke (2px)
- **Size**: 24px standard, 20px for small contexts
- **Source**: Heroicons or similar consistent set

### Common Icons
- **Add/Plus**: For adding ingredients
- **Remove/X**: For removing items
- **Check**: For selected states
- **Clock**: For production countdown
- **Calendar**: For weekend schedule
- **Shopping Cart**: For order summary

## Animation & Transitions

### Standard Transitions
```css
transition-all duration-200 ease-in-out
```

### Hover Effects
- **Scale**: `hover:scale-105` for cards
- **Shadow**: `hover:shadow-lg` for elevated feel
- **Color**: Background/border color changes

### Loading States
- **Skeleton**: Light gray placeholders with subtle animation
- **Spinner**: Blue spinning indicator for async operations

## Content Guidelines

### Voice & Tone
- **Friendly**: Welcoming and approachable
- **Clear**: Direct and easy to understand
- **Enthusiastic**: Show excitement about fresh ingredients
- **Helpful**: Guide users through the process

### Content Patterns
- **Call-to-Action**: "Build Your Burrito", "Add to Order", "Complete Order"
- **Progress Indicators**: "Step 1 of 5: Choose Your Protein"
- **Availability**: "Only X burritos left today!"
- **Fresh Emphasis**: "This week's fresh ingredients"

## Accessibility

### WCAG 2.1 AA Compliance
- **Color Contrast**: Minimum 4.5:1 for normal text, 3:1 for large text
- **Focus States**: Clear focus indicators with `focus:ring-2`
- **Alt Text**: Descriptive alt text for all images
- **Keyboard Navigation**: Full keyboard accessibility

### Semantic HTML
- Proper heading hierarchy (h1 → h2 → h3)
- Form labels properly associated
- Semantic landmarks (nav, main, section)
- ARIA labels where needed

## Mobile-First Considerations

### Touch Targets
- **Minimum Size**: 44px x 44px for tap targets
- **Spacing**: Adequate spacing between interactive elements
- **Gesture Support**: Swipe for ingredient browsing (if implemented)

### Performance
- **Image Optimization**: WebP format with fallbacks
- **Lazy Loading**: For ingredient images
- **Critical CSS**: Inline critical above-the-fold styles