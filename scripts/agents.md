# دليل الوكلاء - Scripts Layer (scripts/)

## 1. Purpose

طبقة Scripts توفر **Deployment & Upgrade Automation**:
- **Deployment Scripts**: Production deployment
- **Upgrade Scripts**: Version upgrades
- **Bash Utilities**: System maintenance

## 2. Owned Scope

```
scripts/
├── deployment/          # Production deployment
│   └── deploy.sh
│
└── upgrade/            # Version upgrades
    └── upgrade.sh
```

## 3. Common Scripts

### Deployment
```bash
# Deploy to production
./scripts/deployment/deploy.sh

# Typical flow:
# 1. Git pull
# 2. composer install --no-dev
# 3. npm run build
# 4. php artisan migrate --force
# 5. php artisan config:cache
# 6. php artisan route:cache
# 7. php artisan view:cache
# 8. Restart services
```

### Upgrades
```bash
# Upgrade to new version
./scripts/upgrade/upgrade.sh v2.0.0

# Handles:
# - Database migrations
# - Config updates
# - Cache clearing
# - Service restarts
```

## 4. Notes

- **Always backup** قبل deployment
- **Test locally** أولاً
- **Use version control** للـ scripts
