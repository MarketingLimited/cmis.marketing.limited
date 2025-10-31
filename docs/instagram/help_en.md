## Instagram API Command Help

📘 **Overview**
Fetch Instagram media, captions, and insights directly from the Instagram Graph API.

──────────────────────────────────────────────

### 🔹 Usage
```bash
php artisan instagram:api <account> media [options]
```

### 🔹 Options
| Option | Description |
|--------|-------------|
| `--by=username|id` | Define how to search for the account. |
| `--from=<YYYY-MM-DD>` | Start date for filtering. |
| `--to=<YYYY-MM-DD>` | End date for filtering. |
| `--limit=<N>` | Number of posts to fetch (default 25, up to 100 when using date filters). |
| `--metric=<field>` | Sort by metric (reach, likes, comments, etc). |
| `--sort=desc|asc` | Define sort order. |
| `--debug` | Enable compact debug logs. |
| `--debug-full` | Enable detailed API response logs. |

──────────────────────────────────────────────

### 🔹 Examples
```bash
# Fetch the latest 3 posts
php artisan instagram:api marketing.limited media --limit=3

# Fetch posts from August 2025
php artisan instagram:api marketing.limited media --from=2025-08-01 --to=2025-08-31

# Fetch the 5 most engaging posts of 2024
php artisan instagram:api marketing.limited media --from=2024-01-01 --to=2024-12-31 --limit=5 --metric=total_interactions --sort=desc
```

──────────────────────────────────────────────
✅ **End of Help**
