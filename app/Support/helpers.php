<?php

use Carbon\Carbon;

if (!function_exists('format_sar')) {
    function format_sar(float|int $amount, int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals, '.', ',') . ' ريال';
    }
}

if (!function_exists('format_usd')) {
    function format_usd(float|int $amount, int $decimals = 2): string
    {
        return '$' . number_format((float) $amount, $decimals, '.', ',');
    }
}

if (!function_exists('sar_to_usd')) {
    function sar_to_usd(float|int $amount, float $rate = 0.27): float
    {
        return round((float) $amount * $rate, 2);
    }
}

if (!function_exists('usd_to_sar')) {
    function usd_to_sar(float|int $amount, float $rate = 3.75): float
    {
        return round((float) $amount * $rate, 2);
    }
}

if (!function_exists('format_currency_decimals')) {
    function format_currency_decimals(float|int $amount, int $decimals = 2, string $currency = 'SAR'): string
    {
        return number_format((float) $amount, $decimals, '.', ',') . ' ' . $currency;
    }
}

if (!function_exists('format_large_currency')) {
    function format_large_currency(float|int $amount): string
    {
        $amount = (float) $amount;

        if ($amount >= 1_000_000) {
            return number_format($amount / 1_000_000, 1, '.', '') . 'M ريال';
        }

        if ($amount >= 1_000) {
            return number_format($amount / 1_000, 1, '.', '') . 'K ريال';
        }

        return number_format($amount, 2, '.', ',') . ' ريال';
    }
}

if (!function_exists('format_currency_signed')) {
    function format_currency_signed(float|int $amount): string
    {
        $sign = $amount < 0 ? '-' : '+';

        return $sign . number_format(abs((float) $amount), 2, '.', ',') . ' ريال';
    }
}

if (!function_exists('format_currency_whole')) {
    function format_currency_whole(float|int $amount): string
    {
        return number_format((float) $amount, 0, '.', ',') . ' ريال';
    }
}

if (!function_exists('format_multi_currency')) {
    function format_multi_currency(float|int $amount, string $currency): string
    {
        $symbols = [
            'SAR' => 'ريال',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $value = number_format((float) $amount, 2, '.', ',');

        if ($currency === 'SAR') {
            return $value . ' ' . ($symbols[$currency] ?? $currency);
        }

        $symbol = $symbols[$currency] ?? $currency;

        return $symbol . $value;
    }
}

if (!function_exists('budget_percentage')) {
    function budget_percentage(float|int $spent, float|int $total): float
    {
        if ((float) $total === 0.0) {
            return 0.0;
        }

        return round(((float) $spent / (float) $total) * 100, 1);
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|int $amount, string $currency = 'USD', int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals, '.', ',') . ' ' . $currency;
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage(float|int $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', ',') . '%';
    }
}

if (!function_exists('format_large_number')) {
    function format_large_number(float|int $number): string
    {
        $number = (float) $number;

        if ($number >= 1_000_000) {
            return number_format($number / 1_000_000, 1, '.', '') . 'M';
        }

        if ($number >= 1_000) {
            return number_format($number / 1_000, 1, '.', '') . 'K';
        }

        $formatted = number_format($number, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}

if (!function_exists('format_file_size')) {
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);

        if ($bytes === 0) {
            return '0 B';
        }

        $pow = (int) floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $value = $bytes / (1024 ** $pow);

        $formatted = number_format($value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return ($formatted === '' ? '0' : $formatted) . ' ' . $units[$pow];
    }
}

if (!function_exists('formatBytes')) {
    /**
     * Alias for format_file_size() - used by backup views
     */
    function formatBytes(int|float|null $bytes): string
    {
        return format_file_size((int) ($bytes ?? 0));
    }
}

if (!function_exists('format_phone')) {
    function format_phone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($digits, '+966')) {
            $subscriber = substr($digits, 4);
            $subscriber = str_pad($subscriber, 9, '0');
            return sprintf('+966 %s %s %s', substr($subscriber, 0, 2), substr($subscriber, 2, 3), substr($subscriber, 5));
        }

        return $digits;
    }
}

if (!function_exists('calculate_percentage_change')) {
    function calculate_percentage_change(float|int $old, float|int $new): float
    {
        $old = (float) $old;
        $new = (float) $new;

        if ($old === 0.0) {
            return $new > 0 ? 100.0 : 0.0;
        }

        return (($new - $old) / $old) * 100;
    }
}

if (!function_exists('round_to_nearest')) {
    function round_to_nearest(float|int $number, int $nearest = 5): float
    {
        return round((float) $number / $nearest) * $nearest;
    }
}

if (!function_exists('format_arabic_number')) {
    function format_arabic_number(string|int|float $number): string
    {
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($western, $eastern, (string) $number);
    }
}

if (!function_exists('calculate_average')) {
    function calculate_average(array $numbers): float
    {
        if (empty($numbers)) {
            return 0.0;
        }

        return array_sum($numbers) / count($numbers);
    }
}

if (!function_exists('format_with_separator')) {
    function format_with_separator(float|int $number, string $separator = ','): string
    {
        return number_format((float) $number, 0, '.', $separator);
    }
}

if (!function_exists('calculate_discount')) {
    function calculate_discount(float|int $price, float|int $discountPercent): float
    {
        return (float) $price * ((float) $discountPercent / 100);
    }
}

if (!function_exists('format_rating')) {
    function format_rating(float|int $rating, int $max = 5): string
    {
        return number_format((float) $rating, 1, '.', '') . ' / ' . $max;
    }
}

if (!function_exists('format_date_display')) {
    function format_date_display(string|Carbon $date, string $format = 'Y-m-d'): string
    {
        return Carbon::parse($date)->format($format);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(string|Carbon $date): string
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}

if (!function_exists('format_date_human')) {
    function format_date_human(string|Carbon $date): string
    {
        return Carbon::parse($date)->diffForHumans();
    }
}

if (!function_exists('format_date_arabic')) {
    function format_date_arabic(string|Carbon $date): string
    {
        Carbon::setLocale('ar');

        return Carbon::parse($date)->translatedFormat('d F Y');
    }
}

if (!function_exists('convert_timezone')) {
    function convert_timezone(string|Carbon $date, string $timezone = 'Asia/Riyadh'): Carbon
    {
        return Carbon::parse($date)->timezone($timezone);
    }
}

if (!function_exists('get_start_of_day')) {
    function get_start_of_day(string|Carbon $date): Carbon
    {
        return Carbon::parse($date)->startOfDay();
    }
}

if (!function_exists('get_end_of_day')) {
    function get_end_of_day(string|Carbon $date): Carbon
    {
        return Carbon::parse($date)->endOfDay();
    }
}

if (!function_exists('calculate_age')) {
    function calculate_age(string|Carbon $birthdate): int
    {
        return Carbon::parse($birthdate)->age;
    }
}

if (!function_exists('is_past_date')) {
    function is_past_date(string|Carbon $date): bool
    {
        return Carbon::parse($date)->isPast();
    }
}

if (!function_exists('is_future_date')) {
    function is_future_date(string|Carbon $date): bool
    {
        return Carbon::parse($date)->isFuture();
    }
}

if (!function_exists('format_month_year')) {
    function format_month_year(string|Carbon $date): string
    {
        return Carbon::parse($date)->format('F Y');
    }
}

if (!function_exists('get_quarter')) {
    function get_quarter(string|Carbon $date): int
    {
        return Carbon::parse($date)->quarter;
    }
}

if (!function_exists('format_iso8601')) {
    function format_iso8601(string|Carbon $date): string
    {
        return Carbon::parse($date)->toIso8601String();
    }
}

if (!function_exists('array_flatten_custom')) {
    function array_flatten_custom(array $array): array
    {
        $result = [];

        array_walk_recursive($array, function ($value) use (&$result) {
            $result[] = $value;
        });

        return $result;
    }
}

if (!function_exists('array_pluck_custom')) {
    function array_pluck_custom(array $array, string $key): array
    {
        return array_map(static function ($item) use ($key) {
            return is_array($item) ? ($item[$key] ?? null) : null;
        }, $array);
    }
}

if (!function_exists('array_group_by')) {
    function array_group_by(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? 'other') : 'other';
            $result[$groupKey][] = $item;
        }

        return $result;
    }
}

if (!function_exists('array_remove_nulls')) {
    function array_remove_nulls(array $array): array
    {
        return array_values(array_filter($array, static fn ($value) => $value !== null));
    }
}

if (!function_exists('array_remove_empty')) {
    function array_remove_empty(array $array): array
    {
        return array_values(array_filter($array, static function ($value) {
            return !empty($value) || $value === 0 || $value === '0';
        }));
    }
}

if (!function_exists('array_sort_by_key')) {
    function array_sort_by_key(array $array, string $key, string $direction = 'asc'): array
    {
        usort($array, static function ($a, $b) use ($key, $direction) {
            $aValue = is_array($a) ? ($a[$key] ?? null) : null;
            $bValue = is_array($b) ? ($b[$key] ?? null) : null;

            return $direction === 'asc' ? ($aValue <=> $bValue) : ($bValue <=> $aValue);
        });

        return $array;
    }
}

if (!function_exists('array_get_nested')) {
    function array_get_nested(array $array, string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}

if (!function_exists('array_set_nested')) {
    function array_set_nested(array &$array, string $path, mixed $value): array
    {
        $segments = explode('.', $path);
        $current = &$array;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current = $value;

        return $array;
    }
}

if (!function_exists('array_merge_deep')) {
    function array_merge_deep(array $array1, array $array2): array
    {
        return array_merge_recursive($array1, $array2);
    }
}

if (!function_exists('array_is_assoc')) {
    function array_is_assoc(mixed $array): bool
    {
        if (!is_array($array) || $array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists('is_valid_url')) {
    function is_valid_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('get_domain')) {
    function get_domain(string $url): string
    {
        $parsed = parse_url($url);

        return $parsed['host'] ?? '';
    }
}

if (!function_exists('add_utm_params')) {
    function add_utm_params(string $url, array $params): string
    {
        $components = parse_url($url);
        $existing = [];

        if (isset($components['query'])) {
            parse_str($components['query'], $existing);
        }

        $query = array_merge($existing, $params);
        $queryString = http_build_query($query);

        $scheme = $components['scheme'] ?? 'http';
        $host = $components['host'] ?? '';
        $port = isset($components['port']) ? ':' . $components['port'] : '';
        $path = $components['path'] ?? '';
        $fragment = isset($components['fragment']) ? '#' . $components['fragment'] : '';

        $base = sprintf('%s://%s%s%s', $scheme, $host, $port, $path);

        if ($queryString !== '') {
            $base .= '?' . $queryString;
        }

        return $base . $fragment;
    }
}

if (!function_exists('extract_utm_params')) {
    function extract_utm_params(string $url): array
    {
        $parsed = parse_url($url);
        $params = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
        }

        return array_filter($params, static fn ($key) => str_starts_with($key, 'utm_'), ARRAY_FILTER_USE_KEY);
    }
}

if (!function_exists('shorten_url_display')) {
    function shorten_url_display(string $url, int $length = 50): string
    {
        if (mb_strlen($url) <= $length) {
            return $url;
        }

        return mb_substr($url, 0, $length) . '...';
    }
}

if (!function_exists('build_query_string')) {
    function build_query_string(array $params): string
    {
        return http_build_query($params);
    }
}

if (!function_exists('parse_query_string')) {
    function parse_query_string(string $query): array
    {
        parse_str($query, $params);

        return $params;
    }
}

if (!function_exists('is_secure_url')) {
    function is_secure_url(string $url): bool
    {
        return str_starts_with($url, 'https://');
    }
}

if (!function_exists('remove_query_params')) {
    function remove_query_params(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';

        return sprintf('%s://%s%s%s', $scheme, $host, $port, $path);
    }
}

if (!function_exists('append_path')) {
    function append_path(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_valid_saudi_phone')) {
    function is_valid_saudi_phone(string $phone): bool
    {
        $pattern = '/^(\+966|00966|966|0)?5[0-9]{8}$/';
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        return preg_match($pattern, $cleaned) === 1;
    }
}

if (!function_exists('is_valid_uuid')) {
    function is_valid_uuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}

if (!function_exists('contains_arabic')) {
    function contains_arabic(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }
}

if (!function_exists('is_strong_password')) {
    function is_strong_password(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }
}

if (!function_exists('is_valid_credit_card')) {
    function is_valid_credit_card(string $number): bool
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return $sum % 10 === 0;
    }
}

if (!function_exists('is_valid_date')) {
    function is_valid_date(string $date, string $format = 'Y-m-d'): bool
    {
        $parsed = \DateTime::createFromFormat($format, $date);

        return $parsed !== false && $parsed->format($format) === $date;
    }
}

if (!function_exists('is_valid_json')) {
    function is_valid_json(string $string): bool
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('is_valid_ip')) {
    function is_valid_ip(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input(string $input): string
    {
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_allowed_extension')) {
    function is_allowed_extension(string $filename, array $allowed = ['jpg', 'png', 'pdf']): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $allowed, true);
    }
}

if (!function_exists('is_valid_hashtag')) {
    function is_valid_hashtag(string $tag): bool
    {
        return preg_match('/^#[\p{L}\p{N}_]+$/u', $tag) === 1;
    }
}
