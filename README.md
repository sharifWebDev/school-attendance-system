# School Attendance System

A comprehensive Mini School Attendance System built with Laravel backend and Vue.js frontend, featuring AI-assisted development workflow documentation.

## 🚀 Features

### Backend (Laravel 10+)
- **Student Management**: Complete CRUD operations for students
- **Attendance Module**: Bulk attendance recording and reporting
- **REST API**: Fully documented API endpoints
- **Redis Caching**: Optimized performance with caching
- **Artisan Commands**: Report generation utilities
- **Event System**: Real-time notifications
- **Unit Testing**: Comprehensive test coverage

### Frontend (Vue.js 3)
- **Student Management**: Search, filter, and pagination
- **Attendance Recording**: Bulk actions with real-time stats
- **Dashboard**: Analytics with Chart.js visualizations
- **Responsive Design**: Bootstrap-powered UI
- **SPA Architecture**: Single Page Application

### AI Development Workflow
- Detailed documentation of AI-assisted development
- Prompt examples and efficiency analysis
- Manual vs AI-generated code breakdown

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 10+
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis
- **Authentication**: Laravel Sanctum
- **API**: RESTful JSON API

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Routing**: Vue Router 4
- **HTTP Client**: Axios
- **Charts**: Chart.js with vue-chartjs
- **UI Framework**: Bootstrap 5
- **Build Tool**: Vite

## 📋 Prerequisites

- PHP 8.1+
- Composer
- Node.js 16+
- MySQL/PostgreSQL
- Redis

## ⚡ Quick Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd school-attendance-system
```

### 2. Backend Setup
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_attendance
DB_USERNAME=root
DB_PASSWORD=

# Configure Redis in .env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Generate Sanctum keys
php artisan sanctum:install

# Create storage link
php artisan storage:link
```

### 3. Frontend Setup
```bash
# Install Node.js dependencies
npm install

# Build assets for development
npm run dev

# Or build for production
npm run build
```

### 4. Run the Application
```bash
# Start Laravel development server
php artisan serve

# Start frontend dev server (in another terminal)
npm run dev
```

The application will be available at:
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000

## 🐳 Docker Installation (Alternative)

```bash
# Clone the repository
git clone <repository-url>
cd school-attendance-system

# Copy docker environment file
cp .env.docker .env

# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Install frontend dependencies and build
docker-compose exec app npm install
docker-compose exec app npm run build
```

Access the application at http://localhost:8000
  

## 🔧 Configuration

### Environment Variables
Key environment variables to configure in `.env`:

```env
APP_NAME="School Attendance System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_attendance
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

VITE_API_URL="${APP_URL}/api"
```

### API Endpoints

#### Student Management
- `GET /api/students` - List students with pagination
- `POST /api/students` - Create new student
- `GET /api/students/{id}` - Get student details
- `PUT /api/students/{id}` - Update student
- `DELETE /api/students/{id}` - Delete student

#### Attendance Management
- `POST /api/attendance/bulk` - Record bulk attendance
- `GET /api/attendance/monthly-report` - Generate monthly reports
- `GET /api/attendance/today-summary` - Get today's summary
- `GET /api/attendance` - List attendance records

## 🧪 Testing

### Backend Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter AttendanceServiceTest

# Generate test coverage
php artisan test --coverage-html coverage/
```

### Frontend Tests
```bash
# Run Vue.js tests (if configured)
npm run test:unit
```

## 🎯 Usage

### 1. Student Management
- Add students with photos, class, and section information
- Search and filter students by various criteria
- Bulk import/export capabilities

### 2. Attendance Recording
- Select class and section to load students
- Mark attendance (Present/Absent/Late) with bulk actions
- Add optional notes for each attendance record
- Real-time attendance percentage calculation

### 3. Reports & Analytics
- View daily attendance summary
- Generate monthly attendance reports
- Export reports in CSV format
- Visual charts for attendance trends

### 4. Artisan Commands
```bash
# Generate monthly attendance report
php artisan attendance:generate-report 2024-01

# Generate report for specific class
php artisan attendance:generate-report 2024-01 "10A"

# Clear attendance cache
php artisan cache:clear
```

## 🤖 AI Development Workflow

This project was developed using AI-assisted workflow. Key aspects:

### AI Tools Used
- **Claude Code**: Primary code generation
- **Cursor**: Context-aware completion
- **ChatGPT**: Architecture brainstorming

### Development Approach
- **AI-Generated**: Boilerplate code, common patterns, configurations
- **Manual Development**: Business logic, complex integrations, UI/UX

### Efficiency Gains
- **63% Time Savings** compared to manual development
- **Consistent Code Quality** across the application
- **Comprehensive Documentation** automatically generated

See `AI_WORKFLOW.md` for detailed documentation.

## 🔒 Security Features

- Laravel Sanctum for API authentication
- CSRF protection
- SQL injection prevention
- XSS protection
- Input validation and sanitization
- Secure file upload handling

## 📊 Performance Optimizations

- Redis caching for frequently accessed data
- Eager loading to prevent N+1 queries
- Database indexing for faster searches
- Pagination for large datasets
- Asset compression and minification

## 🚀 Deployment

### Production Build
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm run build

# Run migrations
php artisan migrate --force
```

### Environment Setup
- Set `APP_DEBUG=false`
- Configure production database
- Set up Redis server
- Configure proper file permissions
- Set up SSL certificate

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
1. Check the [Issues](../../issues) page
2. Create a new issue with detailed description
3. Contact the development team

## 🙏 Acknowledgments

- Laravel community for excellent documentation
- Vue.js team for the fantastic framework
- AI tools that accelerated development
- Contributors and testers

---

**Built with ❤️ using AI-assisted development**
