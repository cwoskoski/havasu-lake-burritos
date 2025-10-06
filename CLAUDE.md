# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Havasu Lake Burritos is a **mobile-first** online burrito ordering platform built with Laravel 12.x, PHP 8.2+, Vite, and TailwindCSS 4.0. Customers build custom burritos through a guided "track" process, selecting ingredients from organized categories.

**CRITICAL: Mobile-First Development**
- Most customers will order via mobile phones
- All interfaces must be optimized for touch interactions
- Minimum 44px touch targets for all interactive elements
- Single-handed operation capability required
- Fast loading on mobile networks essential

### Business Domain

**Burrito Building Process ("Track"):**
1. **Proteins** - Customer selects protein options
2. **Rice & Beans** - Rice and bean varieties
3. **Fresh Toppings** - Fresh vegetables and garnishes
4. **Salsas** - Various salsa options
5. **Creamy** - Cheese and sour cream selections

**Ingredient Management:**
- Ingredients change week to week based on availability
- Each burrito uses standardized portions:
  - 1/2 cup protein
  - 1/2 cup rice
  - 2/3 cup beans
  - 14-inch tortillas (standard)
  - Variable amounts of other toppings

**Purchasing System:**
- Quantity-based ordering for inventory management
- Portion calculations for ingredient purchasing
- Weekly ingredient rotation capability

**Production Schedule & Limits:**
- Burritos only made on Saturdays and Sundays
- Limited number of burritos available per production day
- Real-time countdown display of remaining burritos
- Order availability based on production capacity and schedule

## Development Setup (Laravel Sail + Docker)

### Initial Setup
Install Laravel Sail for Docker-based development:
```bash
composer require laravel/sail --dev
php artisan sail:install
```

Select services: **mysql**, **redis**, **mailpit**

### Environment Configuration
```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

### Start Development Environment
```bash
# Start all Docker services
./vendor/bin/sail up -d

# Install dependencies (inside Docker containers)
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

## Common Development Commands

### Laravel Sail (Docker) Commands
- `./vendor/bin/sail up` - Start all Docker services
- `./vendor/bin/sail up -d` - Start services in background (detached)
- `./vendor/bin/sail down` - Stop all Docker services
- `./vendor/bin/sail shell` - Access application container shell

### Backend Development (via Sail)
- `./vendor/bin/sail artisan serve` - Laravel development server (unnecessary with Sail)
- `./vendor/bin/sail artisan migrate` - Run database migrations
- `./vendor/bin/sail artisan migrate:fresh --seed` - Fresh migration with seeders
- `./vendor/bin/sail artisan tinker` - Interactive PHP shell
- `./vendor/bin/sail artisan queue:work` - Start queue worker
- `./vendor/bin/sail artisan pail --timeout=0` - View real-time logs

### Authentication & SMS Development
- `./vendor/bin/sail artisan breeze:install blade` - Install Laravel Breeze
- `./vendor/bin/sail composer require twilio/sdk` - Install Twilio PHP SDK
- `./vendor/bin/sail artisan make:controller PhoneVerificationController` - Create SMS verification controller
- `./vendor/bin/sail artisan queue:listen` - Process SMS jobs in background

### Frontend Development (via Sail)
- `./vendor/bin/sail npm run dev` - Start Vite development server with hot reload
- `./vendor/bin/sail npm run build` - Build production assets

### Testing and Quality (via Sail)
- `./vendor/bin/sail test` - Run PHPUnit tests
- `./vendor/bin/sail artisan test` - Run Laravel tests
- `./vendor/bin/sail bin pint` - Run Laravel Pint (code formatter)

### Database (Docker Services)
- **MySQL 8.0** - Development database (Docker container)
- **Redis** - Caching and queue backend (Docker container)
- **Mailpit** - Local email testing (Docker container)
- **SQLite** - Testing database (in-memory)

### Docker Service Access
- **Application**: http://localhost (port 80)
- **MySQL**: localhost:3306 (from host machine)
- **Redis**: localhost:6379 (from host machine)
- **Mailpit**: http://localhost:8025 (email dashboard)

### Sail Alias (Optional)
Add to your shell profile for shorter commands:
```bash
alias sail='./vendor/bin/sail'
# Then use: sail up, sail artisan migrate, etc.
```

## Project Structure

### Backend Architecture
- **Models**: `app/Models/` - Eloquent models (currently includes User model)
- **Controllers**: `app/Http/Controllers/` - HTTP request handling
- **Providers**: `app/Providers/` - Service providers for dependency injection
- **Routes**: `routes/web.php` - Web routes definition
- **Database**: `database/migrations/` - Database schema migrations
- **Database**: `database/seeders/` - Database seeders
- **Database**: `database/factories/` - Model factories for testing

### Expected Domain Models
- **User** - Customer accounts with phone verification (extends Laravel User model)
- **PhoneVerification** - SMS verification codes and phone number validation
- **Ingredient** - Individual ingredients with categories (proteins, rice_beans, fresh_toppings, salsas, creamy)
- **IngredientWeek** - Weekly availability and pricing of ingredients
- **Burrito** - Customer's burrito configuration
- **Order** - Customer orders containing multiple burritos (linked to phone/user)
- **IngredientUsage** - Tracking portions used per burrito type
- **PurchaseOrder** - Quantity-based ingredient ordering for inventory
- **ProductionDay** - Weekend production settings (Saturday/Sunday) and burrito availability tracking
- **WeekendSchedule** - Manages Saturday/Sunday production capacity and remaining counts
- **SmsNotification** - Track SMS messages sent to customers (order updates, pickup alerts)

### Frontend Architecture
- **Assets**: `resources/css/app.css` and `resources/js/app.js` - Main frontend entry points
- **Views**: `resources/views/` - Blade templates
- **Build**: Vite configuration in `vite.config.js` with Laravel plugin and TailwindCSS
- **Styling**: TailwindCSS 4.0 integrated via Vite plugin

### Configuration
- **Environment**: `.env` file for environment-specific configuration
- **Application**: `config/app.php` - Main application configuration
- **Testing**: `phpunit.xml` - PHPUnit configuration with in-memory SQLite

## Development Workflow

### Trunk-Based Development + Docker (Laravel Sail)

**Primary workflow (Trunk-based):**
1. `git pull origin main` - Always start with latest trunk
2. `./vendor/bin/sail up -d` - Start all Docker services in background
3. `./vendor/bin/sail npm run dev` - Start Vite for hot-reloading frontend assets
4. Develop in small increments with frequent commits to main
5. Use feature flags to hide incomplete features

**Trunk-based Development Principles:**
- **Frequent commits**: Multiple commits per day to main branch
- **Small changes**: Break work into <4 hour chunks
- **Feature flags**: Hide incomplete features behind config toggles
- **TDD approach**: Write tests first, commit often
- **Short-lived branches**: Optional branches last <2 days maximum

**All services run in Docker containers:**
- **Laravel Application** - Served on http://localhost
- **MySQL 8.0** - Database service
- **Redis** - Caching and queue backend
- **Mailpit** - Email testing dashboard
- **Vite Dev Server** - Hot-reloading for TailwindCSS 4.0 and JavaScript

**Development benefits:**
- Consistent environment across team members
- No local PHP/MySQL/Redis installation required
- Easy switching between projects
- Preparation for Laravel Vapor deployment
- Continuous integration with trunk-based workflow

For focused development, individual services can be managed separately using the Sail commands listed above.

## README.md Maintenance

**IMPORTANT**: The README.md file should be continuously updated as the project evolves. When working on this project, you should:

### When to Update README.md
- **New features implemented** - Add to Features Roadmap and mark as complete
- **API endpoints added** - Update the API Endpoints section
- **Configuration changes** - Update environment variables and config files
- **New dependencies** - Update Technology Stack section
- **Database schema changes** - Update Project Structure if significant
- **Deployment changes** - Update deployment instructions

### Key Sections to Maintain
1. **Features Roadmap** - Move completed features from [ ] to [x]
2. **Technology Stack** - Keep dependencies current
3. **Quick Start** - Ensure installation steps work
4. **Project Structure** - Update when adding new models/controllers
5. **API Endpoints** - Document new routes as they're added
6. **Configuration** - Add new environment variables

### README.md Update Process
When implementing features:
1. **Before starting** - Check current roadmap status
2. **During development** - Note any new dependencies or config needed
3. **After completion** - Update README.md to reflect:
   - Completed features (check boxes)
   - New installation steps
   - New API endpoints
   - Updated configuration requirements

### Example Updates
```markdown
# When completing burrito builder:
- [x] Basic burrito builder  # Changed from [ ] to [x]

# When adding new endpoint:
- `POST /api/orders` - Submit burrito order

# When adding new dependency:
- **PDF Generation**: barryvdh/laravel-dompdf
```

This ensures the README.md remains accurate and helpful for new developers joining the project.

## Mobile-First Development Guidelines

**ESSENTIAL**: All components and pages must prioritize mobile experience.

### Touch Target Requirements
- **Minimum size**: 44px × 44px for all interactive elements
- **Adequate spacing**: 8px minimum between touch targets
- **Thumb-friendly placement**: Important actions within thumb reach

### Responsive Design Patterns
- **Mobile**: Single column, large typography, simplified navigation
- **Tablet (md:)**: 2-3 column grids, expanded touch targets
- **Desktop (lg:)**: Full multi-column layouts, hover states

### Performance Requirements
- **First paint**: < 2 seconds on 3G networks
- **Interactive**: < 3 seconds on mobile
- **Image optimization**: WebP with fallbacks, lazy loading
- **Critical CSS**: Inline above-the-fold styles

### Mobile UX Patterns
- **Progressive disclosure**: Show only essential info initially
- **Swipe gestures**: For ingredient browsing (where appropriate)
- **Pull-to-refresh**: For availability updates
- **Bottom sheet modals**: Instead of traditional modals on mobile
- **Sticky navigation**: Keep key actions accessible while scrolling

### Testing Requirements
- **Real device testing**: Test on actual phones, not just browser DevTools
- **Various screen sizes**: iPhone SE to large Android phones
- **Touch interaction testing**: Ensure all gestures work properly
- **Network conditions**: Test on slow connections

When implementing any feature, always ask: "How will this work on a phone with someone's thumb?"

## Authentication & SMS Development Guidelines

### Phone + SMS Authentication Approach

**Why Phone Numbers Over Social Auth:**
- **Direct customer contact** - Essential for order updates and pickup notifications
- **Local business appropriate** - Food service needs reliable communication
- **Mobile-first friendly** - Phone numbers are natural on mobile devices
- **Reduced complexity** - No OAuth setup, simpler user flows
- **Fraud prevention** - SMS verification ensures real customers

### User Registration Flow
1. **Initial Registration**: Name, email, password (Laravel Breeze)
2. **Phone Collection**: Mandatory phone number field
3. **SMS Verification**: Send 6-digit code to phone
4. **Account Activation**: Verify code before allowing orders
5. **Guest Option**: Phone-only checkout for non-account users

### SMS Integration Best Practices

**Rate Limiting & Cost Control:**
- Limit verification attempts (3 per 10 minutes)
- Implement progressive delays between resend requests
- Use queues for SMS sending to prevent blocking
- Log all SMS activity for cost monitoring

**User Experience:**
- Clear messaging about why phone is required
- Auto-format phone numbers (show as: (555) 123-4567)
- International number support with country codes
- Graceful fallback if SMS fails

**Security Considerations:**
- Hash verification codes in database
- Short expiration times (5-10 minutes)
- Rate limit by IP and phone number
- Validate phone number format before sending

### Development Environment Setup

**Twilio Test Credentials:**
- Use Twilio test credentials during development
- Test phone numbers: +15005550006 (valid format)
- Test verification codes work without actual SMS
- Log SMS content to application logs in local environment

**Database Fields for User Model:**
```php
$table->string('phone')->nullable();
$table->timestamp('phone_verified_at')->nullable();
$table->boolean('sms_notifications')->default(true);
$table->boolean('marketing_sms')->default(false);
```

**Queue Configuration:**
- SMS sending should always be queued
- Use 'sms' queue for SMS-specific jobs
- Implement retry logic for failed SMS
- Monitor queue status for SMS bottlenecks

### Mobile-First Phone Input Design

**Phone Number Input Best Practices:**
- Use `type="tel"` for mobile keyboard
- Implement auto-formatting as user types
- Show country flag/code selector
- Large touch targets for number pad
- Clear validation messaging

**SMS Code Input:**
- 6-digit code entry with auto-advance
- Large, finger-friendly input boxes
- Auto-submit when 6 digits entered
- Clear countdown timer for resend
- Paste support for verification codes

### Testing Strategy

**SMS Testing Scenarios:**
- Valid phone number with successful verification
- Invalid phone number format handling
- Rate limiting verification attempts
- SMS delivery failures and retries
- Code expiration and regeneration
- International phone number support

**Test Data Management:**
- Use factory to generate test users with verified phones
- Seed database with sample verified customers
- Mock Twilio service in automated tests
- Test SMS templates and formatting