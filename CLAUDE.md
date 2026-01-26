# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

HyperBiz is a Laravel 11 + Vue 3 + Inertia.js application for managing business transactions, products, customers, and company operations. It uses a full-stack SPA architecture where Laravel handles the backend API and Vue components render via Inertia without traditional API calls.

## Development Commands

```bash
# Start development (runs artisan serve, queue listener, and Vite concurrently)
composer run dev

# Or run individually:
php artisan serve          # Laravel dev server
npm run dev                # Vite dev server with HMR

# Build frontend for production
npm run build

# Run tests
php artisan test           # Or: ./vendor/bin/pest

# Run single test file
php artisan test tests/Feature/ExampleTest.php

# Lint PHP code
./vendor/bin/pint

# Run database migrations
php artisan migrate

# Clear caches
php artisan optimize:clear
```

## Architecture

### Stack
- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Vue 3 with Inertia.js (SPA without traditional API)
- **UI Theme**: Metronic 9 (Tailwind CSS edition)
- **Auth**: Laravel Jetstream with Sanctum, Teams, 2FA support
- **Permissions**: Spatie Laravel Permission (role-based access)
- **Testing**: Pest PHP

### UI Framework (Metronic 9)
The admin panel uses **Metronic 9 Tailwind CSS** theme. Key UI conventions:
- **CSS Classes**: Use Metronic's utility classes alongside Tailwind (e.g., `btn`, `btn-primary`, `btn-sm`, `card`, `card-body`, `input`, `select`, `badge`, `menu`, `menu-item`)
- **Icons**: Keenicons with `ki-filled` or `ki-outline` prefix (e.g., `ki-filled ki-pencil`, `ki-outline ki-cross`)
- **Components**: Cards, modals, dropdowns, tables follow Metronic patterns
- **Theme Assets**: Located in `resources/metronic/` and `public/assets/`
- **Forms**: Use classes like `input`, `select`, `checkbox`, `radio` (Metronic-styled)
- **Buttons**: `btn btn-primary`, `btn btn-light`, `btn btn-sm`, `btn-icon`
- **Badges**: `badge badge-sm badge-primary`, `badge-success`, `badge-warning`, `badge-danger`
- **Data Menu**: Dropdowns use `data-menu="true"` with `menu-toggle`, `menu-dropdown`, `menu-link` classes

### Request Flow
1. Routes defined in `routes/web.php` point to Controllers
2. Controllers return `Inertia::render('Page/Component', $props)`
3. Vue components in `resources/js/Pages/` receive props and render
4. Forms use axios for API calls to `/api/*` endpoints or direct Inertia form submissions

### Key Directories
```
app/
├── Http/Controllers/    # Main controllers (User, Product, Transaction, etc.)
├── Models/              # Eloquent models with Spatie roles/permissions
├── Traits/              # LogsSystemChanges for audit logging
└── Enums/               # PHP enumerations

resources/js/
├── Pages/               # Vue page components (maps to Inertia routes)
├── Components/          # Reusable Vue components
└── Layouts/             # AppLayout and other layout wrappers
```

### Database Conventions
- Master tables use `mst_` prefix (e.g., `mst_products`, `mst_customers`)
- Transaction tables use `trx_` prefix
- Foreign keys follow Laravel conventions (`model_id`)

### Permission System
Uses dot notation for permissions: `users.view`, `users.create`, `users.edit`, `users.delete`. Check permissions in controllers via middleware and in Vue via `page.props.user.permissions`.

### Audit Logging
Models using `LogsSystemChanges` trait automatically log changes to `system_logs` table with before/after values, user info, and IP address.

## Common Patterns

### Controller Structure
```php
// Middleware in constructor
public function __construct()
{
    $this->middleware(['permission:resource.view'], ['only' => ['index', 'show']]);
    $this->middleware(['permission:resource.create'], ['only' => ['store']]);
}

// Return Inertia response with props
return Inertia::render('Resource/Index', [
    'items' => $data,
    'pagination' => [...],
    'filters' => [...],
]);
```

### Vue Page Pattern
```javascript
// Use usePage() for accessing shared props
import { usePage, router } from '@inertiajs/vue3';
const page = usePage();

// Partial reload without page refresh
router.reload({ only: ['items', 'pagination'] });

// Check permissions
const hasPermission = (perm) => page.props.user?.permissions?.includes(perm);
```

### API Endpoints
Web routes serve Inertia pages. API operations use routes like:
- `POST /resource/api/store`
- `PUT /resource/api/update/{id}`
- `DELETE /resource/api/delete/{id}`
- `PATCH /resource/api/toggle-status/{id}`
