# دليل الوكلاء - System Layer (system/)

## 1. Purpose

طبقة System توفر **GPT Runtime & Database Tools**:
- **Runtime Documentation**: GPT system docs
- **SQL Optimization**: Database performance
- **Bootstrap Scripts**: System initialization
- **Monitoring**: Performance tracking

## 2. Owned Scope

```
system/
├── gpt_runtime_*.md     # GPT runtime documentation (10 files)
│   ├── gpt_runtime_readme.md
│   ├── gpt_runtime_flow.md
│   ├── gpt_runtime_dashboard.md
│   ├── gpt_runtime_security.md
│   └── ...
│
├── gpt_runtime_*.sql    # SQL utilities
│   ├── gpt_runtime_bootstrap.sql
│   ├── gpt_runtime_optimize.sql
│   ├── gpt_runtime_repair.sql
│   └── ...
│
├── install_artisan_cron.sh
└── optimize_embeddings_tables.sql
```

## 3. Key Files

- `gpt_runtime_readme.md`: System overview
- `gpt_runtime_bootstrap.sql`: Initialize GPT runtime
- `gpt_runtime_optimize.sql`: Performance optimization
- `optimize_embeddings_tables.sql`: pgvector optimization

## 4. Usage

```bash
# Bootstrap GPT runtime
psql -U begin -d cmis -f system/gpt_runtime_bootstrap.sql

# Optimize database
psql -U begin -d cmis -f system/gpt_runtime_optimize.sql

# Install cron jobs
./system/install_artisan_cron.sh
```

## 5. Notes

- **System-level operations** - use with caution
- **Database optimization** scripts
- **GPT runtime** للـ AI operations
