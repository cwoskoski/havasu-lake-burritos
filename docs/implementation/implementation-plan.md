# Havasu Lake Burritos - Implementation Plan

## Project Overview
Build an online burrito ordering platform where customers create custom burritos through a guided 5-step process, with weekend-only production, daily limits, and weekly ingredient rotation.

## Backend Design Strategy
**Admin Dashboard Impact on Core Architecture:**

The admin management console (Phase 4) doesn't change the customer order flow, but **must be considered during initial backend design** to avoid refactoring later.

### Critical Backend Considerations:
- **Database Schema**: Design migrations with admin features in mind from day 1
- **Model Relationships**: Build relationships to support shopping lists, inventory tracking, and reporting
- **Business Logic**: Core validation rules (weekend-only, daily limits) serve both customer and admin interfaces
- **Data Structure**: Ensure order and ingredient data can generate shopping lists and usage reports

### Customer Flow Independence:
- **Order Process**: Customer experience remains unchanged regardless of admin features
- **Payment Integration**: Simple payment flows unaffected by admin dashboard
- **Mobile Experience**: Frontend optimization independent of admin backend complexity
- **Performance**: Customer-facing APIs optimized separately from admin operations

**Key Principle:** Build backend foundation robust enough for admin features, implement customer flow first, add admin interface later without architectural changes.

## Git Workflow Strategy - Trunk-Based Development

**Single Trunk Strategy for Fast, Continuous Integration:**

### Core Principles
- **Single source of truth**: All development happens on `main` trunk
- **Frequent commits**: Multiple commits per day, small incremental changes
- **Short-lived branches**: Feature branches last hours to 1-2 days maximum
- **Feature flags**: Hide incomplete features, keep trunk always releasable
- **Automated testing**: Strong CI/CD pipeline catches issues immediately

### Branch Structure
- **`main`** - Single trunk branch (production-ready, auto-deploys to Laravel Vapor)
- **`feature/*`** - Very short-lived branches (optional, <2 days)
- **`hotfix/*`** - Emergency fixes (merged immediately)

### Typical Workflow
1. **Pull latest trunk**:
   ```bash
   git checkout main
   git pull origin main
   ```

2. **Make small changes** (Option A - Direct to trunk):
   ```bash
   # Work in small increments
   git add .
   git commit -m "Add ingredient validation logic"
   git push origin main
   ```

3. **Make small changes** (Option B - Short-lived branch):
   ```bash
   git checkout -b feature/payment-validation
   # Work for <2 days maximum
   git push origin feature/payment-validation
   # Create PR immediately, merge within hours
   ```

4. **Use feature flags for incomplete work**:
   ```php
   if (config('features.new_payment_flow')) {
       // New payment logic
   } else {
       // Current payment logic
   }
   ```

### Laravel Vapor Integration
- **Production**: Direct deployment from `main` trunk
- **Staging**: Optional staging environment for larger changes
- **Feature flags**: Environment-based feature toggles

### Key Practices
- **Small commits**: Break work into <4 hour chunks
- **TDD approach**: Write tests first, commit frequently
- **Fast code reviews**: <2 hour review turnaround
- **Feature flags**: Hide incomplete features behind toggles
- **CI/CD**: Automated testing prevents trunk breakage

## Phase 1: Foundation & Setup (Week 1)

### 1.1 Development Environment & Stack Setup
- [x] Laravel 12.x project initialized
- [x] Vite + TailwindCSS 4.0 configured
- [x] Basic project structure established
- [x] **Set up trunk-based development on main branch**
- [x] **Install Laravel Sail** for Docker-based development (MySQL, Redis, Mailpit)
- [x] **Configure Docker environment** - All services running and healthy
- [x] **Install Laravel Breeze** (auth routes and views configured)
- [x] **Install additional packages**:
  - [x] `spatie/laravel-permission` for admin/customer roles
  - [x] `twilio/sdk` for SMS verification
  - [x] `laravel/vapor-core` for deployment
  - [x] Testing stack: Pest, Dusk, Playwright
  - [ ] Optional: `barryvdh/laravel-dompdf` for kitchen ticket printing
- [ ] **Configure feature flags system** for hiding incomplete features

### 1.2 Database Design & Migrations (Admin-Ready Architecture)
**Design database schema to support future admin features without refactoring:**

- [ ] **Core migrations** with admin considerations:
  - `users` - include role support and customer preferences
  - `ingredients` - with categories, portion sizes, cost tracking
  - `ingredient_weeks` - weekly rotation and availability management
  - `production_days` - weekend scheduling with daily limits
  - `orders` - comprehensive state tracking and customer history
  - `burritos` - detailed ingredient tracking for shopping lists
  - `order_items` - junction table for complex order relationships
- [ ] **Model relationships** designed for admin queries:
  - Order → Ingredients (shopping list generation)
  - ProductionDay → Orders (daily limit monitoring)
  - User → Orders (customer history for reordering)
  - IngredientWeek → Ingredients (rotation management)
- [ ] **Database seeders** with admin and customer sample data
- [ ] **Database factories** for testing both customer and admin scenarios

**Key Design Principles:**
- All customer data must be queryable for admin reports
- Ingredient tracking enables automatic shopping list generation
- Order state management supports kitchen workflow monitoring
- User preferences stored for quick reordering functionality

### 1.2.1 Test-Driven Development (TDD) Approach
**Implement backend using TDD methodology:**

- [ ] **Set up testing environment** with Laravel Sail:
  - Configure PHPUnit for Docker environment
  - Set up test database (SQLite in-memory)
  - Create base test classes for Feature and Unit tests
- [ ] **Write tests BEFORE implementation**:
  - Model tests (relationships, business logic)
  - Feature tests (API endpoints, user flows)
  - Unit tests (business rules, calculations)
- [ ] **TDD Cycle for each feature**:
  1. Write failing test
  2. Write minimal code to pass test
  3. Refactor while keeping tests green
- [ ] **Test categories to implement**:
  - **Business Logic Tests**: Weekend-only ordering, daily limits
  - **Model Tests**: Ingredient relationships, order calculations
  - **API Tests**: Burrito builder endpoints, order processing
  - **Integration Tests**: Payment flows, kitchen printing

### 1.3 Authentication & User Management (Laravel Breeze + Phone/SMS)
- [ ] Configure Breeze authentication views and routes
- [ ] Install and configure `spatie/laravel-permission` for basic roles
- [ ] **Add phone number field** to user registration
- [ ] **Implement SMS verification system** for phone validation
- [ ] **Set up SMS service integration** (Twilio recommended)
- [ ] Create customer and admin roles
- [ ] Customize Breeze registration for mobile-first phone collection
- [ ] Create admin-only routes and middleware
- [ ] **Guest checkout option** - collect phone for order notifications only

## Phase 2: Core Business Logic (Week 2-3)

### 2.1 Ingredient Management System
- [ ] Create Ingredient model with categories
- [ ] Build IngredientWeek model for weekly rotation
- [ ] Implement admin interface for ingredient management
- [ ] Create ingredient category enum/constants
- [ ] Add ingredient image upload functionality

### 2.2 Production Schedule Management
- [ ] Create ProductionDay model
- [ ] Build weekend schedule configuration
- [ ] Implement daily burrito limit tracking
- [ ] Create admin production management interface
- [ ] Add real-time availability checking

### 2.3 Order System Foundation
- [ ] Design Order and Burrito models
- [ ] Create order state management (cart, pending, confirmed, completed)
- [ ] Implement basic order validation
- [ ] Set up order number generation

### 2.4 SMS Communication System
- [ ] **SMS Service Setup** - Configure Twilio or AWS SNS for messaging
- [ ] **Phone Verification Flow** - Send verification codes during registration
- [ ] **Order Status Notifications** - Automated SMS for order updates
- [ ] **Pickup Notifications** - SMS alerts when orders are ready
- [ ] **Marketing Opt-in** - Optional SMS promotions with consent
- [ ] **SMS Rate Limiting** - Prevent spam and manage costs

### 2.5 Payment System Integration
- [ ] **Simple Payment API Design** - Create endpoints for popular platforms
- [ ] **Cash App Integration** - Simple payment link generation
- [ ] **Venmo Integration** - Payment request URL creation
- [ ] **Apple Pay/Google Pay** - Web API integration for mobile browsers
- [ ] **Payment Status Tracking** - Link payments to orders
- [ ] **Order History for Reordering** - Store customer preferences with phone lookup
- [ ] **One-tap Reorder System** - Quick duplicate previous orders

## Phase 3: Customer Frontend (Week 4-5)

### 3.1 Homepage & Landing
- [ ] Design homepage with weekend schedule display
- [ ] Show current availability counter
- [ ] Create "Order Now" call-to-action
- [ ] Add ingredient rotation highlights

### 3.2 Mobile-First Burrito Builder (Matching Paper Form Layout)
- [ ] **Step 1: 🥩 PROTEIN** - Pork Barbacoa, Chicken (Select only one!)
- [ ] **Step 2: 🍚 RICE & BEANS** - Spanish Rice, Boiled/Black/Refried Beans
- [ ] **Step 3: 🥬 FRESH TOPPINGS** - Lettuce, Tomato, Chopped Onions, Cilantro
- [ ] **Step 4: 🌶 SALSAS** - Mild Salsa, Hot Salsa
- [ ] **Step 5: 🧀 CREAMY** - Cheese, Sour Cream
- [ ] **$7.50 base price** display
- [ ] **Customer info**: Name and Phone fields
- [ ] **Mobile-optimized interface**: Large touch targets (44px+), single-column layout
- [ ] **Responsive ingredient grid**: 2 columns on mobile, 3-4 on larger screens
- [ ] **Thumb-friendly interactions**: Easy one-handed operation

### 3.3 Kitchen Print System
- [ ] **Auto-print kitchen tickets** when orders submitted
- [ ] **Identical layout** to paper forms with checkboxes
- [ ] **Large fonts** for easy reading while cooking
- [ ] **Order numbering** for production sequence
- [ ] **Multiple copy printing** (kitchen/customer/backup)

### 3.4 Order Management
- [ ] Order confirmation system for customers
- [ ] Simple order status tracking (preparing → ready → completed)
- [ ] SMS/email notifications for pickup
- [ ] Weekend pickup scheduling

## Phase 4: Admin Dashboard (Week 6)

### 4.1 Ingredient Management
- [ ] Weekly ingredient setup interface
- [ ] Bulk ingredient import/export
- [ ] Ingredient availability toggle
- [ ] Photo management system

### 4.2 Production Management
- [ ] Daily production limit configuration
- [ ] Weekend schedule management
- [ ] Real-time order monitoring
- [ ] **Shopping list generation** based on orders
- [ ] **Ingredient quantity calculator** (0.5 cup protein × orders, etc.)
- [ ] **Weekly prep lists** with total amounts needed

### 4.3 Order Management
- [ ] Order queue display
- [ ] Order status updates
- [ ] Customer communication tools
- [ ] Basic reporting dashboard

## Phase 5: Advanced Features (Week 7-8)

### 5.1 Inventory & Purchasing
- [ ] Implement IngredientUsage tracking
- [ ] Build portion calculation system
- [ ] Create PurchaseOrder functionality
- [ ] Inventory level monitoring
- [ ] Automated purchasing suggestions

### 5.2 Mobile & Customer Experience Enhancements
- [ ] **Progressive Web App (PWA)** features for app-like mobile experience
- [ ] **Offline capability** for browsing menu when connection is poor
- [ ] **Mobile notifications** for order status updates
- [ ] **One-thumb navigation** optimization for single-handed use
- [ ] **Touch gesture support** for ingredient browsing
- [ ] **Order history and favorites** with mobile-optimized interface
- [ ] **One-tap reorder functionality** from previous orders
- [ ] **Fast checkout flow** - minimize steps on mobile
- [ ] **Quick payment integration** with Cash App, Venmo, Apple Pay, Google Pay
- [ ] **Saved payment methods** for faster checkout
- [ ] Real-time availability updates

### 5.3 Business Intelligence
- [ ] Sales reporting dashboard
- [ ] Ingredient usage analytics
- [ ] Customer behavior insights
- [ ] Peak hour analysis

## Phase 6: Testing & Deployment (Week 9)

### 6.1 Testing Suite
- [ ] Unit tests for models and business logic
- [ ] Feature tests for order flow
- [ ] Browser testing for burrito builder
- [ ] Mobile device testing
- [ ] Performance testing

### 6.2 Security & Optimization
- [ ] Security audit and fixes
- [ ] Performance optimization
- [ ] SEO optimization
- [ ] Accessibility compliance (WCAG 2.1 AA)

### 6.3 Laravel Vapor Deployment (Cost-Optimized)
- [ ] **Install Vapor CLI** globally with Composer
- [ ] **Initialize Vapor project** with `vapor init`
- [ ] **Configure vapor.yml** for cost optimization:
  - 512MB memory allocation (lowest tier)
  - Aurora Serverless database (auto-pause when idle)
  - Minimal Lambda configuration for ~30 orders/week
- [ ] **Environment variables** setup for production
- [ ] **Database migrations** through Vapor commands
- [ ] **Asset compilation** with `npm run build`
- [ ] **Deploy to production** with `vapor deploy`
- [ ] **Monitor AWS costs** - should stay within free tier ($0-6/month)
- [ ] **SSL certificate** - automatically handled by Vapor
- [ ] **Domain configuration** through Vapor dashboard

## Technical Implementation Details

### Key Artisan Commands to Create
```bash
# Models with migrations
php artisan make:model Ingredient -mfs
php artisan make:model IngredientWeek -mfs
php artisan make:model ProductionDay -mfs
php artisan make:model Order -mfs
php artisan make:model Burrito -mfs
php artisan make:model IngredientUsage -mfs

# Controllers
php artisan make:controller BurritoBuilderController
php artisan make:controller OrderController
php artisan make:controller Admin/IngredientController --resource
php artisan make:controller Admin/ProductionController --resource

# Middleware
php artisan make:middleware CheckProductionSchedule
php artisan make:middleware CheckDailyLimit

# Jobs for background processing
php artisan make:job ProcessOrder
php artisan make:job UpdateInventory
```

### Configuration Requirements
- **Environment Variables**: Production limits, email settings, payment gateway
- **Cache Configuration**: Redis for session and order data
- **Queue Configuration**: Database or Redis for order processing
- **Storage Configuration**: S3 or local for ingredient images

### Third-Party Integrations
- **SMS Service**: Twilio for phone verification and order notifications
- **Payment Processing**: Stripe or Square for order payments
- **Email Service**: Mailgun or SendGrid for backup notifications
- **Image Optimization**: ImageKit or Cloudinary for ingredient photos
- **Analytics**: Google Analytics for business insights

## Success Metrics
- [ ] Order completion rate > 90%
- [ ] Average time to complete order < 3 minutes
- [ ] Mobile conversion rate > 70%
- [ ] Weekend capacity utilization > 85%
- [ ] Customer satisfaction > 4.5/5

## Risk Mitigation
- **Peak Load Planning**: Load testing for busy weekend periods
- **Data Backup**: Automated daily backups of orders and customer data
- **Fallback Systems**: Manual order taking if system is down
- **Security Monitoring**: Real-time alerts for suspicious activity

## Post-Launch Enhancements (Future Phases)
- Customer loyalty program
- Subscription/recurring orders
- Catering options for larger orders
- Integration with delivery services
- Mobile app development
- Multi-location support