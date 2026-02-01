# HyperBiz SaaS Development Plan

This document outlines the development roadmap for transforming HyperBiz from a single-tenant application to a multi-tenant SaaS platform.

---

## Phase 1: Multi-Tenancy Foundation ✅

**Status:** Completed

### Database Changes
- [x] Add company_id column to users table
- [x] Add is_platform_admin column to users table
- [x] Create mst_company table with subscription fields
- [x] Add company_id foreign key to all tenant-specific tables

### Models & Scopes
- [x] Create Company model with relationships
- [x] Implement BelongsToCompany trait with global scope
- [x] Apply global scope to all tenant models (Product, Customer, SalesOrder, etc.)
- [x] Add hasCompany() and isPlatformAdmin() methods to User model

### Middleware
- [x] Create EnsureHasCompany middleware
- [x] Redirect users without company to onboarding
- [x] Exclude platform admins from company requirement

---

## Phase 2: Platform Administration ✅

**Status:** Completed

### Platform Admin Dashboard
- [x] Create PlatformAdminController
- [x] Dashboard with company statistics (total, active, trial, expired)
- [x] Recent companies list
- [x] Subscription status chart

### Company Management
- [x] Companies list with search and status filter
- [x] Company detail page with company info, user list, quick stats, subscription status
- [x] Edit subscription status modal (change trial/active/expired/cancelled)

### Admin Routes & Navigation
- [x] Define /admin/* routes with platform admin protection
- [x] Separate sidebar menu for platform admin
- [x] Platform admin topbar customization

---

## Phase 3: Onboarding Flow ✅

**Status:** Completed

### Onboarding Pages
- [x] Welcome page (/onboarding)
- [x] Company Setup form (/onboarding/company-setup)
- [x] Completion page (/onboarding/complete)

### Onboarding Features
- [x] Progress indicator (3 steps)
- [x] Company creation with trial status
- [x] Industry selection
- [x] Auto-assign superadmin role to company creator
- [x] Quick start links on completion page

### Trial Configuration
- [x] Configurable trial days in config/app.php
- [x] Environment variable support (APP_TRIAL_DAYS)
- [x] Trial end date calculation
- [x] Days remaining display

---

## Phase 4: Subscription Plans Management ✅

**Status:** Completed

### Database
- [x] Create subscription_plans table
- [x] SubscriptionPlan model with relationships and scopes

### Platform Admin Features
- [x] Plans list page (/admin/plans)
- [x] Create/Edit plan modal with full form
- [x] Toggle plan active status (with SweetAlert2 confirmation)
- [x] Delete plan (with protection for plans with active subscribers)
- [x] Drag-and-drop reorder plans
- [x] Table view and Card view toggle
- [x] Search and status filter
- [x] Stats summary (total, active, inactive plans)

### Plan Features to Track
- [x] Maximum users, products, customers, monthly orders
- [x] Custom features list (JSON array)

### Sample Plans Created
- Starter: IDR 99,000/mo - 3 users, 100 products, 200 customers, 100 orders/mo
- Professional: IDR 299,000/mo - 10 users, 500 products, 1,000 customers, 500 orders/mo
- Enterprise: IDR 799,000/mo - Unlimited everything

---

## Phase 5: Tenant Subscription & Billing 🟡

**Status:** In Progress (Core features complete, payment gateway pending)

### Subscription Flow
- [x] View available plans page (/subscription/plans)
- [x] Plan comparison table with features list
- [x] Upgrade flow (select plan → choose billing → generate invoice)
- [x] Subscription confirmation page
- [x] Current subscription status page (/subscription)

### Billing Integration (Bank Transfer - Manual Verification)
- [x] Invoice generation (automatic when tenant selects plan)
- [x] Invoice model with number, amount, due date, status
- [x] Bank transfer payment method
- [x] Payment proof upload (transfer amount now optional)
- [x] Payment proof image storage and display
- [x] Admin verification page (/admin/payment-verifications)
- [x] Amount mismatch warning for admin approval
- [x] Mandatory verification checkbox before approval
- [x] Subscription auto-activation upon payment approval
- [x] Payment rejection with reason tracking
- [ ] Payment gateway (Midtrans/Stripe) - Future
- [ ] Auto-renewal handling - Future

### Subscription Management
- [x] Current plan display in subscription page
- [x] Usage statistics vs plan limits
- [x] Global subscription status banner (top of every page)
- [x] Upgrade prompts via banner CTA buttons
- [ ] Grace period for expired subscriptions - Future

### Admin Payment Verification
- [x] Platform admin verification page
- [x] Filter by status (pending, approved, rejected)
- [x] View payment proof details modal
- [x] Approve/Reject with SweetAlert2 confirmation
- [x] Amount mismatch warning during approval
- [x] Verification checkbox requirement
- [x] Fixed SweetAlert z-index with dialog elements

### Subscription Banner (SubscriptionBanner.vue)
- [x] Full-width banner at top of content
- [x] Color-coded by status severity
- [x] Dismissible for non-critical trial warnings
- [x] Non-dismissible for expired/suspended/past_due
- [x] Hidden for platform admins and active subscriptions

### Scheduled Commands (Laravel 11)
- [x] CheckExpiredTrials - Daily trial expiry check
- [x] CheckExpiredSubscriptions - Daily subscription check
- [x] CheckPastDueInvoices - Daily overdue invoice check

---

## Phase 6: Company Settings (Tenant) 🔲

**Status:** Pending

### Company Profile
- [ ] Company details edit form
- [ ] Logo upload with image cropping
- [ ] Business information (tax ID, address)
- [ ] Contact information

### Preferences
- [ ] Default currency setting
- [ ] Date/time format preferences
- [ ] Invoice numbering format
- [ ] Email notification settings

### Team Management
- [ ] Invite users to company
- [ ] Pending invitations list
- [ ] User role management within company
- [ ] Remove user from company

---

## Phase 7: Usage Limits & Enforcement 🔲

**Status:** Pending

### Limit Tracking
- [ ] Track current usage (users, products, customers, orders)
- [ ] Compare against plan limits
- [ ] Warning thresholds (80%, 90%, 100%)

### Enforcement
- [ ] Block actions when limits exceeded
- [ ] Graceful error messages
- [ ] Upgrade prompts
- [ ] Admin override capability

### Usage Dashboard
- [ ] Visual usage meters
- [ ] Usage history chart
- [ ] Projected usage alerts

---

## Phase 8: Platform Analytics 🔲

**Status:** Pending

### Revenue Analytics
- [ ] Monthly Recurring Revenue (MRR)
- [ ] Annual Recurring Revenue (ARR)
- [ ] Revenue by plan
- [ ] Revenue growth chart

### Customer Analytics
- [ ] New signups over time
- [ ] Conversion rate (trial to paid)
- [ ] Churn rate
- [ ] Customer lifetime value

### Usage Analytics
- [ ] Active companies
- [ ] Feature adoption rates
- [ ] Most used features
- [ ] API usage statistics

### Reports
- [ ] Export to CSV/Excel
- [ ] Scheduled email reports
- [ ] Custom date ranges

---

## Phase 9: Platform Settings 🔲

**Status:** Pending

### General Settings
- [ ] Application name and branding
- [ ] Default trial period
- [ ] Allowed registration (open/invite-only)
- [ ] Maintenance mode

### Email Settings
- [ ] Email templates management
- [ ] SMTP configuration
- [ ] Email from address

### Security Settings
- [ ] Password policies
- [ ] Session timeout
- [ ] 2FA enforcement options
- [ ] IP whitelist for admin

### Feature Flags
- [ ] Enable/disable features globally
- [ ] Beta features toggle
- [ ] A/B testing support

---

## Phase 10: Advanced Features 🔲

**Status:** Future

### White-Label Support
- [ ] Custom domain per company
- [ ] Custom branding per company
- [ ] Custom email templates

### API Access
- [ ] API key management
- [ ] Rate limiting per plan
- [ ] API documentation
- [ ] Webhook support

### Integrations
- [ ] Third-party integrations marketplace
- [ ] OAuth connections
- [ ] Import/Export tools

### Audit & Compliance
- [ ] Detailed audit logs
- [ ] Data export (GDPR compliance)
- [ ] Account deletion workflow

---

## Technical Debt & Improvements

### Code Quality
- [ ] Add comprehensive tests for multi-tenancy
- [ ] Test subscription flows
- [ ] Test limit enforcement

### Performance
- [ ] Cache company data
- [ ] Optimize global scope queries
- [ ] Index optimization for company_id

### Security
- [ ] Audit tenant isolation
- [ ] Penetration testing
- [ ] Security headers review

---

## File Reference

### Key Files Modified/Created

| File | Purpose |
|------|---------|
| `app/Models/Company.php` | Company model with subscription methods |
| `app/Models/User.php` | Added company relationship, platform admin check |
| `app/Models/SubscriptionPlan.php` | Subscription plan model with pricing & limits |
| `app/Http/Middleware/EnsureHasCompany.php` | Redirect users without company |
| `app/Http/Controllers/OnboardingController.php` | Onboarding flow |
| `app/Http/Controllers/PlatformAdminController.php` | Platform admin features |
| `app/Http/Controllers/SubscriptionPlanController.php` | Subscription plans CRUD & reordering |
| `config/app.php` | Added `trial_days` configuration |
| `resources/js/Pages/Onboarding/*.vue` | Onboarding UI pages |
| `resources/js/Pages/Admin/*.vue` | Platform admin UI pages |
| `resources/js/Pages/Admin/Plans/Index.vue` | Subscription plans management (table/card views) |
| `resources/js/Components/Admin/PlanModal.vue` | Create/Edit plan modal |
| `resources/js/Components/Metronic/Sidebar.vue` | Dual menu (admin/tenant) |

---

## Environment Variables

```env
# Trial Configuration
APP_TRIAL_DAYS=14

# Future: Payment Gateway
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

# Future: Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

*Last Updated: February 1, 2026*