# Havasu Lake Burritos

> **Desert Fresh • Lake Views • Authentic Flavors**

An online burrito ordering platform built with Laravel, featuring weekend-only production, real-time availability tracking, and kitchen-friendly order management.

## 🌯 About This Project

Havasu Lake Burritos transforms traditional paper-based burrito ordering into a streamlined digital experience while maintaining the simplicity and familiarity of the original paper forms in the kitchen.

### Key Features

- **Weekend-Only Production** - Orders only accepted for Saturday/Sunday production
- **Real-Time Availability** - Live countdown of remaining burritos
- **5-Step Burrito Builder** - Guided ingredient selection process:
  - 🥩 **Proteins** - Pork Barbacoa, Chicken (select one)
  - 🍚 **Rice & Beans** - Spanish Rice, Boiled/Black/Refried Beans
  - 🥬 **Fresh Toppings** - Lettuce, Tomato, Chopped Onions, Cilantro
  - 🌶 **Salsas** - Mild Salsa, Hot Salsa
  - 🧀 **Creamy** - Cheese, Sour Cream
- **Kitchen Print System** - Auto-prints orders in paper form layout
- **Shopping List Generation** - Automatic ingredient quantity calculations
- **Weekly Ingredient Rotation** - Flexible menu management

## 🛠 Technology Stack

- **Backend**: Laravel 12.x with PHP 8.2+
- **Frontend**: Laravel Breeze + Alpine.js + TailwindCSS 4.0
- **Development**: Laravel Sail + Docker Compose
- **Database**: MySQL 8.0 (development), Aurora Serverless (production)
- **Caching/Queues**: Redis
- **Email Testing**: Mailpit (local), Mailgun/SendGrid (production)
- **Authentication**: Laravel Breeze + Phone/SMS verification
- **SMS Service**: Twilio (phone verification & order notifications)
- **Permissions**: Spatie Laravel Permission
- **Build Tool**: Vite with Hot Module Replacement
- **Deployment**: Laravel Vapor (AWS Lambda + Aurora Serverless)

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Installation (Laravel Sail + Docker)

1. **Clone and setup Laravel Sail:**
   ```bash
   git clone <repository-url>
   cd havasu-lake-burritos
   composer install
   composer require laravel/sail --dev
   php artisan sail:install
   ```
   Select services: **mysql**, **redis**, **mailpit**

2. **Environment setup:**
   ```bash
   cp .env.example .env
   ./vendor/bin/sail artisan key:generate
   ```

3. **Start Docker environment:**
   ```bash
   # Start all services in background
   ./vendor/bin/sail up -d

   # Install frontend dependencies
   ./vendor/bin/sail npm install
   ```

4. **Database setup:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan db:seed
   ```

5. **Start development:**
   ```bash
   # Start Vite for hot-reloading
   ./vendor/bin/sail npm run dev
   ```

   **Access the application:**
   - App: http://localhost
   - Mailpit: http://localhost:8025

### Alternative Development Commands

**Laravel Sail (Docker) Commands:**
- `./vendor/bin/sail up` - Start all Docker services
- `./vendor/bin/sail down` - Stop all Docker services
- `./vendor/bin/sail shell` - Access application container
- `./vendor/bin/sail artisan migrate` - Run database migrations
- `./vendor/bin/sail test` - Run PHPUnit tests
- `./vendor/bin/sail artisan tinker` - Interactive PHP shell

**Sail Alias (Optional):**
```bash
alias sail='./vendor/bin/sail'
# Then use: sail up, sail artisan migrate, etc.
```

## 📋 Business Logic

### Production Schedule
- **Operating Days**: Saturdays and Sundays only
- **Daily Limits**: Configurable maximum burritos per day
- **Availability Tracking**: Real-time countdown display

### Portion Standards
- **Proteins**: 0.5 cups per burrito
- **Rice**: 0.5 cups per burrito
- **Beans**: 0.667 cups per burrito
- **Tortillas**: 14-inch (1 per burrito)
- **Other Toppings**: Variable amounts

### Pricing
- **Base Price**: $7.50 per burrito
- **Weekly Ingredient Costs**: Configurable per ingredient per week

## 🏗 Project Structure

```
app/
├── Models/
│   ├── User.php
│   ├── Ingredient.php
│   ├── IngredientWeek.php
│   ├── ProductionDay.php
│   ├── Order.php
│   └── Burrito.php
├── Http/Controllers/
│   ├── BurritoBuilderController.php
│   ├── OrderController.php
│   └── Admin/
resources/
├── views/
│   ├── components/
│   ├── order/
│   └── admin/
├── css/app.css
└── js/app.js
database/
├── migrations/
├── seeders/
└── factories/
docs/
└── implementation/
    ├── implementation-plan.md
    ├── database-schema.md
    ├── frontend-architecture.md
    ├── style-guide.md
    └── tech-stack.md
```

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test suite
./vendor/bin/phpunit --testsuite=Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## 📱 API Endpoints

### Public Endpoints
- `GET /` - Homepage with availability
- `GET /order` - Burrito builder (authenticated)
- `GET /api/availability` - Current burrito availability

### Admin Endpoints
- `GET /admin` - Admin dashboard
- `GET /admin/ingredients` - Ingredient management
- `GET /admin/production` - Production planning
- `GET /admin/orders` - Order management

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="Havasu Lake Burritos"
APP_URL=http://localhost

# Production Limits
DAILY_BURRITO_LIMIT=50
WEEKEND_PRODUCTION=true

# SMS Service (Twilio)
TWILIO_SID=your_twilio_account_sid
TWILIO_TOKEN=your_twilio_auth_token
TWILIO_FROM=+1234567890

# Kitchen Printing (optional)
KITCHEN_PRINTER_ENABLED=true
```

### Key Configuration Files
- `config/burrito.php` - Business logic configuration
- `config/production.php` - Production schedule settings
- `config/sms.php` - SMS service configuration
- `.env` - Environment-specific settings

### Authentication & SMS Setup

**User Registration Flow:**
1. **Basic Info**: Name, email, password (Laravel Breeze)
2. **Phone Verification**: SMS code sent to provided phone number
3. **Account Activation**: Phone verified before first order

**SMS Integration (Twilio):**
1. **Sign up for Twilio** at https://www.twilio.com
2. **Get credentials** from Twilio Console:
   - Account SID
   - Auth Token
   - Phone number (for sending SMS)
3. **Configure environment variables** in `.env`
4. **Install Twilio PHP SDK**: `composer require twilio/sdk`

**Phone Number Benefits:**
- Direct customer contact for order updates
- SMS notifications for pickup alerts
- Reduced fraud through verified phone numbers
- Guest checkout option for quick orders

## 🚀 Deployment

### Laravel Vapor (Recommended for Low Volume)
**Perfect for ~30 orders/week - Stays within AWS Free Tier**

1. **Install Vapor CLI:**
   ```bash
   composer global require laravel/vapor-cli
   ```

2. **Initialize Vapor:**
   ```bash
   vapor init
   ```

3. **Configure `vapor.yml`:**
   ```yaml
   id: 12345
   name: havasu-lake-burritos
   environments:
     production:
       memory: 512  # Lowest memory for cost optimization
       cli-memory: 512
       runtime: 'php-8.2'
       build:
         - 'composer install --no-dev'
         - 'php artisan config:cache'
       database: havasu-lake-burritos-prod  # Aurora Serverless
   ```

4. **Deploy:**
   ```bash
   vapor deploy production
   ```

### Cost Optimization for Low Volume
- **Lambda**: ~$0 (within free tier with 30 orders/week)
- **Aurora Serverless**: ~$0-5/month (auto-pauses when idle)
- **CloudFront**: ~$0 (free tier includes 1TB transfer)
- **S3**: ~$0-1/month for assets
- **Total Expected**: $0-6/month

### Alternative: Traditional VPS
If you prefer traditional hosting:
- **Server Requirements**:
  - PHP 8.2+ with required extensions
  - MySQL 8.0+ or PostgreSQL 13+
  - Redis (optional for caching)
  - 1GB RAM minimum (512MB works for low volume)

## 📊 Features Roadmap

### Phase 1 (Current)
- [x] Basic project structure
- [x] Implementation documentation
- [ ] Core migrations and models
- [ ] Authentication with Laravel Breeze + Phone/SMS verification
- [ ] SMS integration for order notifications
- [ ] Basic burrito builder

### Phase 2
- [ ] Kitchen print system
- [ ] Admin dashboard
- [ ] Production management
- [ ] Shopping list generation

### Phase 3
- [ ] Customer order history
- [ ] Business analytics
- [ ] Mobile optimizations
- [ ] Performance enhancements

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for significant changes
- Use conventional commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For development guidance and implementation details, see:
- [`CLAUDE.md`](CLAUDE.md) - Claude Code assistance guide
- [`docs/implementation/`](docs/implementation/) - Detailed technical documentation

---

**Built with ❤️ for fresh burritos and lake views**