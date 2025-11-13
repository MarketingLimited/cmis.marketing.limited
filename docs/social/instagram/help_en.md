## Instagram API Command Help

📘 **Overview**
Fetch Instagram media, captions, and insights directly from the Instagram Graph API.

### 🔹 Usage
```bash
php artisan instagram:api <account> media [options]
```

### 🔹 Options
| Option | Description |
|--------|-------------|
| `--from=<YYYY-MM-DD>` | Start date for filtering. |
| `--to=<YYYY-MM-DD>` | End date for filtering. |
| `--limit=<N>` | Number of posts to fetch. |
| `--metric=<field>` | Sort by metric (reach, likes, etc). |
| `--sort=desc|asc` | Define sort order. |

### 🔹 Examples
```bash
php artisan instagram:api marketing.limited media --limit=3
php artisan instagram:api marketing.limited media --from=2025-08-01 --to=2025-08-31
```

✅ **End of Help**


### 🔹 Sync Command
This command synchronizes all Instagram data (media, metrics, captions) between the Graph API and the CMIS database.

#### 🧠 Usage
```bash
php artisan instagram:sync [--debug] [--from=<YYYY-MM-DD>] [--to=<YYYY-MM-DD>]
```

#### 💡 Description
- Automatically syncs all posts and their insights.
- Can be filtered by date range.
- Updates or inserts posts in the local database.
- Supports debug logging for detailed inspection.

#### 🧩 Examples
```bash
php artisan instagram:sync
php artisan instagram:sync --from=2024-01-01 --to=2024-12-31 --debug
```

✅ **End of Sync Help**
