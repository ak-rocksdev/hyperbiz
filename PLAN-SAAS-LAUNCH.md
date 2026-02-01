# HyperBiz SaaS Launch Plan

> Detailed implementation plan for transforming HyperBiz from a single-tenant app into a publicly launchable SaaS product with landing page, onboarding, multi-tenancy, and subscription management.

---

## Table of Contents

1. [Architecture Decisions (Agreed)](#1-architecture-decisions-agreed)
2. [Phase 1: Multi-Tenancy Foundation](#2-phase-1-multi-tenancy-foundation)
3. [Phase 2: Landing Page](#3-phase-2-landing-page)
4. [Phase 3: Registration & Onboarding Flow](#4-phase-3-registration--onboarding-flow)
5. [Phase 4: Subscription & Package System](#5-phase-4-subscription--package-system)
6. [Phase 5: Platform Admin Dashboard](#6-phase-5-platform-admin-dashboard)
7. [Phase 6: Trial Expiry & Access Control](#7-phase-6-trial-expiry--access-control)
8. [Phase 7: Polish & Launch Prep](#8-phase-7-polish--launch-prep)

---

## 1. Architecture Decisions (Agreed)

These decisions were made during brainstorming and are the foundation for all phases:

| Decision | Choice |
|---|---|
| Multi-tenancy approach | Single database, shared tables, `company_id` on all tenant data |
| Database | MySQL (Approach 1 — compound indexes on `company_id`) |
| User-Company relationship | One user belongs to one company (`company_id` on `users` table) |
| Jetstream Teams | Remove usage — replace with direct `company_id` |
| Platform Admin | Same `users` table, `is_platform_admin` boolean flag |
| Tenant Admin | Role-based via Spatie Permission (existing system) |
| Trial period | 30 days, with pre-loaded demo data option |
| Trial expiry behavior | Soft lock — read-only mode (can view, cannot create/edit/delete) |
| Global data (shared across tenants) | Currencies, base UOMs |
| Company-scoped data | Everything else (products, orders, finance, customers, etc.) |
| Feature gating | Module-level (Approach A — entire modules on/off per plan) |
| Billing (Phase 1) | Manual activation by platform admin |
| Billing (Later) | Payment gateway integration (Stripe/Midtrans) |

---

## 2. Phase 1: Multi-Tenancy Foundation

> **Goal**: Make every data table company-aware so multiple tenants can share the same database safely.

### 2.1 Database Migrations

#### 2.1.1 Modify `users` table

- [x] Add `company_id` column (nullable, foreign key to `mst_company`)
- [x] Add `is_platform_admin` boolean column (default: false)
- [x] Remove dependency on `current_team_id` (Jetstream Teams)
- [x] Add index on `company_id`

#### 2.1.2 Add `company_id` to all master tables

Each migration adds: `$table->foreignId('company_id')->constrained('mst_company')` with compound index.

- [x] `mst_products` — add `company_id`, change UNIQUE(sku) to UNIQUE(company_id, sku)
- [x] `mst_client` (customers/suppliers) — add `company_id`, update unique constraints
- [x] `mst_product_categories` — add `company_id`
- [x] `mst_brands` — add `company_id`
- [x] `mst_expense_categories` — add `company_id`
- [x] `mst_uom_categories` — add `company_id`
- [x] `mst_uom` — add `company_id` (custom UOMs are company-scoped)
- [x] `mst_product_uoms` — add `company_id`

#### 2.1.3 Add `company_id` to all transaction tables

- [x] `purchase_orders` — add `company_id`, update UNIQUE(order_number) to UNIQUE(company_id, order_number)
- [x] `purchase_order_items` — add `company_id`
- [x] `purchase_receivings` — add `company_id`
- [x] `purchase_receiving_items` — add `company_id`
- [x] `purchase_returns` — add `company_id`
- [x] `purchase_return_items` — add `company_id`
- [x] `sales_orders` — add `company_id`, update UNIQUE(order_number) to UNIQUE(company_id, order_number)
- [x] `sales_order_items` — add `company_id`
- [x] `sales_shipments` — add `company_id`
- [x] `sales_shipment_items` — add `company_id`
- [x] `sales_returns` — add `company_id`
- [x] `sales_return_items` — add `company_id`
- [x] `payments` — add `company_id`
- [x] `transactions` — add `company_id`
- [x] `inventory_stock` — add `company_id`
- [x] `inventory_movements` — add `company_id`
- [x] `inventory_adjustments` — add `company_id`
- [x] `inventory_adjustment_items` — add `company_id`

#### 2.1.4 Add `company_id` to all finance tables

- [x] `fin_chart_of_accounts` — add `company_id`
- [x] `fin_fiscal_years` — add `company_id`
- [x] `fin_fiscal_periods` — add `company_id`
- [x] `fin_journal_entries` — add `company_id`
- [x] `fin_journal_entry_lines` — add `company_id`
- [x] `fin_account_balances` — add `company_id`
- [x] `fin_expenses` — add `company_id`
- [x] `fin_expense_attachments` — add `company_id`
- [x] `fin_bank_accounts` — add `company_id`
- [x] `fin_bank_reconciliations` — add `company_id`
- [x] `fin_bank_transactions` — add `company_id`
- [x] `fin_supplier_balances` — add `company_id`
- [x] `fin_settings` — add `company_id`

#### 2.1.5 Add `company_id` to system tables

- [x] `system_logs` — add `company_id`
- [x] `company_settings` — already has `company_id` (verify)

#### 2.1.6 Add subscription fields to `mst_company`

- [x] `subscription_status` enum: `trial`, `active`, `suspended`, `cancelled`, `expired` (default: `trial`)
- [x] `subscription_plan_id` nullable foreign key
- [x] `trial_ends_at` timestamp nullable
- [x] `subscription_starts_at` timestamp nullable
- [x] `subscription_ends_at` timestamp nullable
- [x] `billing_cycle` enum: `monthly`, `yearly` nullable
- [x] `max_users` integer nullable (plan limit)

### 2.2 Model Layer — BelongsToCompany Trait

- [x] Create `app/Traits/BelongsToCompany.php` trait with:
  - Global scope: auto-filter `WHERE company_id = auth()->user()->company_id` (skip for platform admin)
  - Auto-set `company_id` on creating event
  - `company()` belongsTo relationship
- [x] Apply trait to ALL company-scoped models (every model listed in 2.1.2 — 2.1.5)
- [x] Keep Currency model as global (no trait) — or decide: base currencies global, custom ones scoped
- [x] Update `User` model: add `company()` relationship, `isPlatformAdmin()` method
- [x] Remove `HasTeams` trait from User model
- [x] Add `scopeForCompany($query, $companyId)` helper scope

### 2.3 Middleware

- [x] Create `EnsureHasCompany` middleware — redirect users without `company_id` to company setup
- [x] Create `CheckSubscriptionStatus` middleware — check if company subscription is active/trial
- [x] Register middleware in `app/Http/Kernel.php` (or bootstrap/app.php for Laravel 11)
- [x] Apply `EnsureHasCompany` to all authenticated routes except company setup routes
- [x] Apply `CheckSubscriptionStatus` to all authenticated routes

### 2.4 Update Controllers

- [x] Audit every controller — remove any manual `::all()` or unscoped queries (the global scope will handle filtering, but verify)
- [x] Update `UserController` — scope user listing to same `company_id`
- [x] Update `CompanyController` — users can only view/edit their own company
- [x] Update `DashboardController` — scope all stats to `company_id`
- [x] Update any report controllers to scope data

### 2.5 Update Seeders

- [x] Create a default platform admin user in `DatabaseSeeder`
- [x] Update demo seeders to assign `company_id` to all seeded data
- [ ] Create a `TenantDemoSeeder` class that seeds sample data for a single company (reusable for onboarding)

### 2.6 Remove Jetstream Teams Usage

- [x] Remove `HasTeams` trait from User model
- [ ] Remove team-related routes from `routes/web.php` (or keep but disable)
- [ ] Remove/hide Teams pages from `resources/js/Pages/Teams/`
- [ ] Remove team switching from profile page
- [ ] Update `JetstreamServiceProvider` — remove team action registrations
- [ ] Remove `current_team_id` dependency from User model
- [ ] Update Inertia shared data — replace team context with company context

---

## 3. Phase 2: Landing Page

> **Goal**: A single-page public landing that explains HyperBiz and funnels visitors to registration.

### 3.1 Route & Layout Setup

- [ ] Create `GuestLandingLayout.vue` in `resources/js/Layouts/` — full-width layout without sidebar, Metronic-styled, responsive
- [ ] Add route: `GET /` → `LandingController@index` → renders `Landing/Index.vue` (for unauthenticated users)
- [ ] Keep existing behavior: authenticated users visiting `/` get redirected to `/dashboard`
- [ ] Create `LandingController.php` in `app/Http/Controllers/`

### 3.2 Landing Page Sections (resources/js/Pages/Landing/Index.vue)

The landing page is a single Vue component with these sections, scrollable top-to-bottom:

#### 3.2.1 Navigation Bar (sticky top)
- [ ] HyperBiz logo (left)
- [ ] Nav links: Features, Pricing, About (scroll-to anchors)
- [ ] CTA buttons: "Login" (outline) + "Start Free Trial" (primary solid)
- [ ] Mobile hamburger menu for responsive
- [ ] Transparent on top, solid background on scroll

#### 3.2.2 Hero Section
- [ ] Headline: Clear one-line value proposition (e.g., "All-in-One Business Management Platform")
- [ ] Subtitle: 1-2 sentences explaining what HyperBiz does (purchasing, sales, inventory, finance)
- [ ] Primary CTA: "Start Your 30-Day Free Trial" button → links to `/register`
- [ ] Secondary CTA: "See Demo" button → links to demo environment or scrolls to features
- [ ] Hero visual: Screenshot/mockup of the dashboard or a relevant illustration
- [ ] Trust indicator: "No credit card required" text below CTA

#### 3.2.3 Features Overview Section (id="features")
- [ ] Section title: "Everything You Need to Run Your Business"
- [ ] 6 feature cards in a 3x2 grid (responsive: 1 column mobile, 2 tablet, 3 desktop):

| Card | Icon | Title | Description |
|---|---|---|---|
| 1 | ki-outline ki-shop | Purchasing | Manage purchase orders, receiving, returns, and supplier relationships |
| 2 | ki-outline ki-handcart | Sales | Create sales orders, track shipments, process returns and payments |
| 3 | ki-outline ki-parcel | Inventory | Real-time stock tracking, movements, adjustments, and valuation reports |
| 4 | ki-outline ki-dollar | Finance | Chart of accounts, journal entries, expenses, and bank reconciliation |
| 5 | ki-outline ki-chart-line-star | Reports | Profit & loss, balance sheet, trial balance, AR/AP aging reports |
| 6 | ki-outline ki-people | Team Management | Role-based access control, user permissions, and audit logging |

- [ ] Each card: icon + title + 1-line description
- [ ] Cards use Metronic card styling with hover effect

#### 3.2.4 How It Works Section
- [ ] Section title: "Get Started in 3 Simple Steps"
- [ ] 3 steps displayed horizontally (vertical on mobile):

| Step | Icon/Number | Title | Description |
|---|---|---|---|
| 1 | Circle with "1" | Create Your Account | Sign up with your email — no credit card needed |
| 2 | Circle with "2" | Set Up Your Company | Enter your business details and invite your team |
| 3 | Circle with "3" | Start Managing | Explore with demo data or start fresh with your own |

- [ ] Visual connector lines between steps (horizontal desktop, vertical mobile)

#### 3.2.5 Pricing Section (id="pricing")
- [ ] Section title: "Simple, Transparent Pricing"
- [ ] Billing toggle: Monthly / Yearly (yearly shows discount)
- [ ] 3 pricing cards side by side (responsive: stacked on mobile):

| Plan | Price (example) | Highlighted | Features |
|---|---|---|---|
| Starter | Rp 299K/mo | No | Products, Purchasing, Sales, Inventory, 3 users |
| Professional | Rp 599K/mo | Yes (recommended badge) | Everything in Starter + Finance, Reports, 10 users |
| Enterprise | Rp 999K/mo | No | Everything + Bank Reconciliation, Unlimited users, Priority support |

- [ ] Each card: plan name, price, feature list with checkmarks/crosses, CTA button
- [ ] "Start Free Trial" on each card → `/register?plan={slug}`
- [ ] "All plans include 30-day free trial" note below cards
- [ ] Prices are placeholder — actual values configured by platform admin later

#### 3.2.6 FAQ Section (optional but recommended)
- [ ] 4-6 expandable accordion items:
  - "What happens after my trial ends?"
  - "Can I change plans later?"
  - "How many users can I add?"
  - "Is my data secure?"
  - "Can I export my data?"
  - "What payment methods do you accept?"
- [ ] Uses Metronic accordion component

#### 3.2.7 Footer
- [ ] Company info / copyright
- [ ] Links: Terms of Service, Privacy Policy, Contact
- [ ] Social media links (optional)

### 3.3 Landing Page Technical Details

- [ ] Page must be fully static (no API calls) — plan data passed as Inertia props from controller
- [ ] SEO: proper `<title>`, `<meta description>`, Open Graph tags via `<Head>` component
- [ ] Performance: lazy load hero image, minimal JS
- [ ] Smooth scroll behavior for anchor links
- [ ] Responsive breakpoints: mobile (< 768px), tablet (768-1024px), desktop (> 1024px)
- [ ] Dark mode not required for landing page (light theme only for simplicity)

### 3.4 Terms of Service & Privacy Policy Pages

- [ ] Create `Landing/TermsOfService.vue` — static content page
- [ ] Create `Landing/PrivacyPolicy.vue` — static content page
- [ ] Add routes: `GET /terms-of-service`, `GET /privacy-policy`
- [ ] Both use `GuestLandingLayout` with nav bar + footer
- [ ] Content: placeholder text initially — legal review later

---

## 4. Phase 3: Registration & Onboarding Flow

> **Goal**: After a user registers, guide them through company setup so they land on a functional dashboard.

### 4.1 Update Registration Flow

#### 4.1.1 Modify Register Page
- [x] Keep existing fields: name, email, password, password confirmation
- [ ] Add hidden field: `plan` (from URL query param `?plan=starter`)
- [x] Keep terms & conditions checkbox
- [ ] Update styling to match new landing page look
- [x] After registration: redirect to onboarding wizard instead of dashboard

#### 4.1.2 Update CreateNewUser Action (Fortify)
- [x] File: `app/Actions/Fortify/CreateNewUser.php`
- [x] Remove team creation logic
- [x] Set `company_id` as null on user creation (they don't have a company yet)
- [x] Set `is_platform_admin` to false
- [ ] Store selected plan in session or user metadata for onboarding step

#### 4.1.3 Update Post-Registration Redirect
- [x] File: `app/Providers/FortifyServiceProvider.php` or RouteServiceProvider
- [x] Change redirect from `/dashboard` to `/onboarding` for users without `company_id`
- [x] `EnsureHasCompany` middleware handles this globally for all auth routes

### 4.2 Onboarding Wizard

#### 4.2.1 Route & Controller
- [x] Create `OnboardingController.php`
- [x] `GET /onboarding` → show wizard (only if user has no `company_id`)
- [x] `POST /onboarding/company` → Step 1: save company
- [ ] `POST /onboarding/preferences` → Step 2: save preferences
- [x] `POST /onboarding/complete` → Step 3: finalize and redirect to dashboard
- [x] Middleware: `auth` only (no `EnsureHasCompany` — they're creating one)

#### 4.2.2 Wizard UI (resources/js/Pages/Onboarding/Index.vue)
- [x] Full-page layout (no sidebar, minimal header with logo only)
- [x] Step indicator at top: Step 1 → Step 2 → Step 3 (with progress visualization)
- [ ] Each step is a section within the same component (or sub-components)
- [x] "Back" and "Next" navigation between steps
- [ ] Progress saved to backend on each step (not lost on page refresh)

#### 4.2.3 Step 1: Company Information
- [x] Company name (required)
- [x] Industry/business type (dropdown: Retail, Manufacturing, Trading, Services, Other)
- [ ] Company address (textarea, optional)
- [ ] Company phone (optional)
- [ ] Company email (optional, default from user email)
- [ ] Company logo upload (optional, with preview)
- [x] On submit: creates `mst_company` record, sets `subscription_status = 'trial'`, sets `trial_ends_at = now + 30 days`, links user to company via `company_id`

#### 4.2.4 Step 2: Preferences & Setup
- [ ] Default currency selection (dropdown from global currencies table)
- [ ] Fiscal year start month (dropdown: January-December)
- [ ] "Load demo data?" toggle with explanation:
  - ON: "Pre-load sample products, customers, and transactions so you can explore all features immediately"
  - OFF: "Start with a clean slate and enter your own data"
- [ ] Number format preference (e.g., 1,000.00 vs 1.000,00)

#### 4.2.5 Step 3: Ready to Go (Confirmation)
- [ ] Summary of company info and preferences
- [ ] Subscription plan display: "You're starting a 30-day free trial of {Plan Name}"
- [x] Trial end date shown
- [x] "Go to Dashboard" primary CTA button
- [ ] On submit:
  - If demo data selected: run `TenantDemoSeeder` for this company
  - Create default Chart of Accounts for the company
  - Create default fiscal year/period
  - Set user's role to "Super Admin" (Spatie role) scoped to their company
  - Create default financial settings
  - Redirect to `/dashboard`

### 4.3 Demo Data Seeder (TenantDemoSeeder)

- [ ] Create `database/seeders/TenantDemoSeeder.php`
- [ ] Accepts `company_id` as parameter
- [ ] Seeds the following for the given company:
  - 3 product categories
  - 10-15 sample products with SKUs
  - 5 customers and 5 suppliers
  - 3 brands
  - Base UOMs (pcs, kg, liter, box)
  - 2-3 sample purchase orders (different statuses)
  - 2-3 sample sales orders (different statuses)
  - Sample inventory movements
  - Chart of accounts (standard template)
  - 1 fiscal year with 12 periods
  - Sample expenses
- [ ] All seeded data has `company_id` set to the target company
- [ ] Can be called from controller: `Artisan::call('db:seed', ['--class' => 'TenantDemoSeeder', ...])` or direct instantiation

### 4.4 Post-Onboarding Dashboard

- [ ] Update dashboard to show a "Welcome" banner for new users (first login detection)
- [ ] Welcome banner contains:
  - "Welcome to HyperBiz! Your 30-day trial is active."
  - Quick links: "Add your first product", "Create a purchase order", "Invite team members"
  - "Dismiss" button to hide banner permanently (save preference)
- [ ] Trial countdown visible somewhere on dashboard (e.g., "Trial: 25 days remaining")

---

## 5. Phase 4: Subscription & Package System

> **Goal**: Platform admin can define packages/plans, and each company is assigned a plan that controls module access.

### 5.1 Database Tables

#### 5.1.1 `subscription_plans` table
- [x] Create migration with columns:
  - `id` (primary key)
  - `name` (string) — e.g., "Starter", "Professional", "Enterprise"
  - `slug` (string, unique) — e.g., "starter", "professional", "enterprise"
  - `description` (text, nullable)
  - `price_monthly` (decimal 12,2) — monthly price
  - `price_yearly` (decimal 12,2) — yearly price (discounted)
  - `max_users` (integer, nullable) — null means unlimited
  - `is_active` (boolean, default true)
  - `is_featured` (boolean, default false) — highlighted on pricing page
  - `sort_order` (integer, default 0)
  - `timestamps`

#### 5.1.2 `subscription_plan_features` table
- [ ] Create migration with columns:
  - `id` (primary key)
  - `plan_id` (foreign key to `subscription_plans`)
  - `feature_key` (string) — e.g., "products", "purchasing", "sales", "finance", "reports_advanced"
  - `timestamps`

#### 5.1.3 `company_subscriptions` table (subscription history/log)
- [ ] Create migration with columns:
  - `id` (primary key)
  - `company_id` (foreign key to `mst_company`)
  - `plan_id` (foreign key to `subscription_plans`)
  - `status` enum: `trial`, `active`, `expired`, `cancelled`
  - `billing_cycle` enum: `monthly`, `yearly`
  - `trial_ends_at` (timestamp, nullable)
  - `current_period_start` (timestamp)
  - `current_period_end` (timestamp)
  - `cancelled_at` (timestamp, nullable)
  - `payment_reference` (string, nullable) — for future payment gateway
  - `timestamps`

### 5.2 Models

- [x] Create `SubscriptionPlan` model (`app/Models/SubscriptionPlan.php`)
  - `features()` hasMany relationship
  - `companies()` hasManyThrough relationship
  - `scopeActive($query)` scope
  - `hasFeature($key)` helper method
- [ ] Create `SubscriptionPlanFeature` model (`app/Models/SubscriptionPlanFeature.php`)
  - `plan()` belongsTo relationship
- [ ] Create `CompanySubscription` model (`app/Models/CompanySubscription.php`)
  - `company()` belongsTo
  - `plan()` belongsTo
  - `isActive()` helper
  - `isTrialing()` helper
  - `daysRemaining()` helper
- [x] Update `Company` model:
  - `subscription()` hasOne (latest active subscription)
  - `subscriptionHistory()` hasMany
  - `plan()` through subscription
  - `hasFeature($key)` helper that checks via plan
  - `isOnTrial()`, `isSubscriptionActive()`, `isExpired()` helpers

### 5.3 Feature Keys Registry

Define all feature keys that can be gated. These map to modules in the sidebar:

```
Feature Key               | Description                    | Module Path
--------------------------|--------------------------------|------------------
products                  | Product management             | /product/*
product_categories        | Product categories             | /product-category/*
brands                    | Brand management               | /brand/*
customers                 | Customer/supplier management   | /customer/*
purchasing                | Purchase orders & receiving    | /purchase-order/*
sales                     | Sales orders & shipments       | /sales-order/*
payments                  | Payment management             | /payment/*
inventory                 | Inventory & stock management   | /inventory/*
inventory_adjustments     | Stock adjustments              | /inventory/adjustments/*
finance_basic             | Chart of accounts, expenses    | /finance/chart-of-accounts/*, /finance/expenses/*
finance_advanced          | Journal entries, fiscal periods | /finance/journal-entries/*, /finance/fiscal-periods/*
reports_financial         | P&L, Balance Sheet, Trial Bal  | /finance/reports/*
reports_aging             | AR/AP aging reports            | /finance/ar-aging/*, /finance/ap-aging/*
bank_accounts             | Bank account management        | /finance/bank-accounts/*
bank_reconciliation       | Bank reconciliation            | /finance/bank-reconciliations/*
uom_management            | UoM & UoM categories           | /uom/*, /uom-category/*
system_logs               | Audit/system log viewer        | /logs/*
access_management         | Role & permission management   | /access-management/*
```

### 5.4 Feature Gate Middleware

- [ ] Create `CheckFeatureAccess` middleware (`app/Http/Middleware/CheckFeatureAccess.php`)
  - Accepts feature key as parameter: `feature:purchasing`
  - Checks: `auth()->user()->company->hasFeature($featureKey)`
  - Platform admin bypasses all checks
  - On failure: return Inertia response to `Subscription/UpgradeRequired.vue` page
- [ ] Register middleware alias in kernel
- [ ] Apply to route groups:
  ```php
  Route::middleware(['feature:purchasing'])->group(function () {
      // All purchase order routes
  });
  ```

### 5.5 Frontend Feature Gating

#### 5.5.1 Inertia Shared Data
- [ ] Share `company.features` array via `AppServiceProvider` (list of feature keys the company has access to)
- [x] Share `company.subscription` object (status, plan name, trial_ends_at, days_remaining)

#### 5.5.2 Vue Feature Helper
- [ ] Create composable `useFeature()` in `resources/js/Composables/useFeature.js`:
  ```javascript
  export function useFeature() {
      const page = usePage();
      const hasFeature = (key) => page.props.company?.features?.includes(key);
      const subscription = computed(() => page.props.company?.subscription);
      return { hasFeature, subscription };
  }
  ```

#### 5.5.3 Update Sidebar
- [ ] File: `resources/js/Components/Metronic/Sidebar.vue`
- [ ] Each menu item checks both permission AND feature:
  - `v-if="hasPermission('products.view') && hasFeature('products')"`
- [ ] Locked modules show with a lock icon and "Pro" / "Enterprise" badge
- [ ] Clicking locked module opens upgrade prompt modal

#### 5.5.4 Upgrade Required Page
- [x] Create `resources/js/Pages/Subscription/UpgradeRequired.vue`
- [ ] Shows: "This feature is available on the {Plan Name} plan"
- [ ] CTA: "Upgrade Now" or "Contact us"
- [ ] Accessible info about what the plan includes

### 5.6 Default Plan Seeder

- [ ] Create `database/seeders/SubscriptionPlanSeeder.php`
- [ ] Seeds 3 default plans:

**Starter Plan:**
```
Features: products, product_categories, brands, customers, purchasing, sales,
          payments, inventory, uom_management, access_management
Max users: 3
```

**Professional Plan:**
```
Features: (all Starter) + inventory_adjustments, finance_basic, finance_advanced,
          reports_financial, reports_aging, system_logs
Max users: 10
```

**Enterprise Plan:**
```
Features: (all Professional) + bank_accounts, bank_reconciliation
Max users: unlimited (null)
```

---

## 6. Phase 5: Platform Admin Dashboard

> **Goal**: A separate dashboard for you (software owner) to manage tenants, subscriptions, and monitor the platform.

### 6.1 Routes & Access

- [x] Create route group: `/admin/*` with middleware `is_platform_admin`
- [x] Create `PlatformAdminMiddleware` — checks `auth()->user()->is_platform_admin`
- [x] Create `PlatformAdminController.php`

### 6.2 Platform Admin Pages

#### 6.2.1 Platform Dashboard (`/platform-admin/dashboard`)
- [x] Create `resources/js/Pages/Admin/Dashboard.vue`
- [ ] Stats cards:
  - Total companies (tenants)
  - Active subscriptions
  - Trial accounts
  - Expired/cancelled accounts
  - Total users across all tenants
  - Revenue this month (when billing is added)
- [ ] Recent registrations list (last 10 companies)
- [ ] Companies expiring soon (trial ending within 7 days)

#### 6.2.2 Companies Management (`/platform-admin/companies`)
- [x] Create `resources/js/Pages/Admin/Companies/Index.vue`
- [ ] List all companies with: name, owner email, plan, status, trial end, user count, created date
- [ ] Filters: status (trial/active/expired/cancelled), plan, search
- [ ] Actions per company:
  - View details
  - Change subscription plan
  - Extend trial
  - Activate/suspend/cancel subscription
  - Impersonate (login as company admin for debugging)

#### 6.2.3 Company Detail (`/platform-admin/companies/{id}`)
- [x] Create `resources/js/Pages/Admin/Companies/Detail.vue`
- [ ] Company info, subscription history, user list, usage stats
- [ ] Quick actions: change plan, extend trial, impersonate

#### 6.2.4 Subscription Plans Management (`/platform-admin/plans`)
- [x] Create `resources/js/Pages/Admin/Plans/Index.vue`
- [x] CRUD for subscription plans
- [ ] Feature checkboxes per plan
- [x] Pricing configuration (monthly/yearly)
- [x] Drag-to-reorder plans

#### 6.2.5 Platform Settings
- [ ] Default trial duration (days)
- [ ] Registration open/closed toggle
- [ ] Maintenance mode toggle
- [ ] Global announcements (shown to all tenants)

### 6.3 Platform Admin Layout

- [ ] Create `PlatformAdminLayout.vue` — separate layout from tenant AppLayout
- [x] Different sidebar with platform admin menu items
- [x] Visual distinction (different color scheme or banner) so you know you're in platform admin mode
- [ ] "Exit to tenant view" button for impersonation

### 6.4 Impersonation

- [ ] Create `ImpersonationController.php`
- [ ] `POST /platform-admin/impersonate/{user_id}` — store original user in session, login as target
- [ ] `POST /platform-admin/stop-impersonate` — restore original user
- [ ] Show "Impersonating {user}" banner at top of page during impersonation
- [ ] Only platform admin can impersonate
- [ ] Audit log impersonation actions

---

## 7. Phase 6: Trial Expiry & Access Control

> **Goal**: When a trial expires, lock the company into read-only mode with upgrade prompts.

### 7.1 Subscription Status Check

- [x] Create `CheckSubscriptionStatus` middleware (if not already in Phase 1):
  - Active subscription or valid trial → allow all
  - Expired/suspended → allow only GET requests (read-only) + redirect POST/PUT/DELETE to upgrade page
  - Cancelled → redirect to "account cancelled" page
  - Platform admin → bypass all checks

### 7.2 Read-Only Mode Implementation

#### 7.2.1 Backend
- [x] Middleware blocks all write operations (POST, PUT, PATCH, DELETE) for expired companies
- [x] Returns JSON `{ error: 'subscription_expired', message: '...' }` for API calls
- [x] Returns Inertia redirect to upgrade page for web requests

#### 7.2.2 Frontend
- [x] Share `company.is_read_only` boolean via Inertia
- [x] Global banner at top: "Your trial has expired. Subscribe to continue creating and editing data."
- [ ] Disable all "Create", "Edit", "Delete" buttons when `is_read_only` is true
- [ ] Hide action buttons or show them as disabled with tooltip "Upgrade to unlock"
- [x] Create `SubscriptionBanner.vue` component, included in `AppLayout.vue`

### 7.3 Trial Expiry Notifications (In-App)

- [x] Dashboard shows trial countdown: "X days remaining in your trial"
- [x] Color coding: green (> 14 days), yellow (7-14 days), red (< 7 days)
- [x] At 7 days: show persistent banner "Your trial expires in 7 days"
- [x] At 3 days: show more urgent banner
- [x] At 0 days: switch to read-only mode automatically

### 7.4 Scheduled Command

- [x] Create scheduled commands in `routes/console.php` (Laravel 11)
- [x] Runs daily via scheduler
- [x] Finds companies where `trial_ends_at < now()` and `subscription_status = 'trial'`
- [x] Updates their status to `expired`
- [ ] (Future: sends email notification)

---

## 8. Phase 7: Polish & Launch Prep

> **Goal**: Final touches before the app is publicly accessible.

### 8.1 Auth Page Restyling

- [ ] Update Login page to match landing page design language
- [ ] Update Register page to match landing page design language
- [ ] Update Forgot Password page styling
- [ ] Ensure consistent branding across all public-facing pages
- [ ] Add "Back to Home" link on login/register pages → goes to landing page

### 8.2 Email Templates (Basic)

- [ ] Welcome email after registration (confirm email + getting started tips)
- [ ] Trial expiring soon (7 days, 3 days, 1 day) — can be deferred
- [ ] Trial expired notification — can be deferred
- [ ] Using Laravel's built-in mail with Blade templates

### 8.3 SEO & Meta

- [ ] Proper `<title>` tags on all public pages
- [ ] `<meta description>` on landing page
- [ ] Open Graph tags for social sharing
- [ ] `robots.txt` — allow landing, block app routes
- [ ] `sitemap.xml` — landing page, terms, privacy

### 8.4 Security Hardening

- [ ] Rate limiting on registration (prevent spam signups)
- [ ] Honeypot field on registration form
- [ ] CSRF protection verified on all forms
- [ ] Verify global scopes work — test that Tenant A cannot see Tenant B data
- [ ] Test platform admin can see all data
- [ ] SQL injection protection audit on any raw queries
- [ ] Verify file uploads are scoped per company (storage paths include company_id)

### 8.5 Testing

- [ ] Feature test: Registration → Onboarding → Dashboard flow
- [ ] Feature test: Tenant data isolation (create 2 companies, verify no data leakage)
- [ ] Feature test: Platform admin can view all companies
- [ ] Feature test: Feature gating (Starter user cannot access Finance)
- [ ] Feature test: Trial expiry → read-only mode
- [ ] Feature test: Impersonation
- [ ] Unit test: BelongsToCompany trait scoping
- [ ] Unit test: Subscription status helpers

### 8.6 Public Demo Environment

- [ ] Create a seeded demo company with demo user credentials
- [ ] `php artisan schedule:run` resets demo data daily
- [ ] Demo credentials displayed on login page (or landing page)
- [ ] Demo user is read-only (cannot delete data, only create/edit)

---

## Implementation Priority & Dependencies

```
Phase 1 (Multi-Tenancy Foundation)     ← Must be first, everything depends on this
    │
    ├── Phase 2 (Landing Page)         ← Can start in parallel after routes are set up
    │
    ├── Phase 3 (Onboarding)           ← Depends on Phase 1 (needs company creation)
    │       │
    │       └── Phase 4 (Subscriptions) ← Depends on Phase 3 (onboarding assigns plan)
    │               │
    │               └── Phase 5 (Platform Admin) ← Depends on Phase 4 (manages plans)
    │               │
    │               └── Phase 6 (Trial Expiry)   ← Depends on Phase 4 (needs subscription status)
    │
    └── Phase 7 (Polish)               ← Last, after all features work
```

**Critical path**: Phase 1 → Phase 3 → Phase 4 → Phase 6

**Parallel work**: Phase 2 (Landing Page) can be built alongside Phase 1 since it's purely frontend.

---

## Files to Create (New)

| File | Phase |
|---|---|
| `app/Traits/BelongsToCompany.php` | 1 |
| `app/Http/Middleware/EnsureHasCompany.php` | 1 |
| `app/Http/Middleware/CheckSubscriptionStatus.php` | 1 |
| `app/Http/Middleware/CheckFeatureAccess.php` | 4 |
| `app/Http/Middleware/PlatformAdminMiddleware.php` | 5 |
| `app/Http/Controllers/LandingController.php` | 2 |
| `app/Http/Controllers/OnboardingController.php` | 3 |
| `app/Http/Controllers/PlatformAdminController.php` | 5 |
| `app/Http/Controllers/ImpersonationController.php` | 5 |
| `app/Models/SubscriptionPlan.php` | 4 |
| `app/Models/SubscriptionPlanFeature.php` | 4 |
| `app/Models/CompanySubscription.php` | 4 |
| `app/Console/Commands/CheckExpiredTrials.php` | 6 |
| `database/seeders/TenantDemoSeeder.php` | 3 |
| `database/seeders/SubscriptionPlanSeeder.php` | 4 |
| `resources/js/Layouts/GuestLandingLayout.vue` | 2 |
| `resources/js/Layouts/PlatformAdminLayout.vue` | 5 |
| `resources/js/Pages/Landing/Index.vue` | 2 |
| `resources/js/Pages/Landing/TermsOfService.vue` | 2 |
| `resources/js/Pages/Landing/PrivacyPolicy.vue` | 2 |
| `resources/js/Pages/Onboarding/Index.vue` | 3 |
| `resources/js/Pages/Subscription/UpgradeRequired.vue` | 4 |
| `resources/js/Pages/PlatformAdmin/Dashboard.vue` | 5 |
| `resources/js/Pages/PlatformAdmin/Companies/Index.vue` | 5 |
| `resources/js/Pages/PlatformAdmin/Companies/Detail.vue` | 5 |
| `resources/js/Pages/PlatformAdmin/Plans/Index.vue` | 5 |
| `resources/js/Composables/useFeature.js` | 4 |
| `resources/js/Components/ReadOnlyBanner.vue` | 6 |

## Files to Modify (Existing)

| File | Phase | Changes |
|---|---|---|
| `app/Models/User.php` | 1 | Add `company_id`, `is_platform_admin`, remove `HasTeams` |
| All models in `app/Models/` | 1 | Add `BelongsToCompany` trait |
| `app/Providers/JetstreamServiceProvider.php` | 1 | Remove team actions |
| `app/Providers/AppServiceProvider.php` | 1 | Update shared Inertia data |
| `app/Actions/Fortify/CreateNewUser.php` | 3 | Remove team creation |
| `routes/web.php` | 1-5 | Add all new routes |
| `resources/js/Components/Metronic/Sidebar.vue` | 4 | Add feature gating |
| `resources/js/Layouts/AppLayout.vue` | 6 | Add ReadOnlyBanner |
| `resources/js/Pages/Auth/Login.vue` | 7 | Restyle to match landing |
| `resources/js/Pages/Auth/Register.vue` | 3 | Add plan param, update redirect |
| `resources/js/Pages/Dashboard.vue` | 3 | Add welcome banner, trial countdown |
