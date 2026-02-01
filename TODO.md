# HyperBiz TODO List

## Security & Authentication

### Routes Missing Auth Middleware
The following routes are accessible to unauthenticated users and may need auth middleware:

- [ ] `/payments` - Payment list page
- [ ] `/products` - Products page
- [ ] `/sales-order/list` - Sales order list page

**Action:** Review `routes/web.php` and add `auth` middleware to these routes if they should be protected.

**Discovered:** 2025-02-01 during E2E testing

---

## Future Improvements

_Add future tasks here_
