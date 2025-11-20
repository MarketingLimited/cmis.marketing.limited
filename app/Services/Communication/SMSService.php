<?php

namespace App\Services\Communication;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SMSService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
        $this->config = config("services.sms.{$this->provider}", []);
    }

    /**
     * Send SMS message
     *
     * @param array $data ['to', 'message', 'from']
     * @return bool
     */
    public function send(array $data): bool
    {
        try {
            // Validate required fields
            if (!isset($data['to']) || !isset($data['message'])) {
                Log::error('SMS send failed: missing required fields', $data);
                return false;
            }

            // Validate phone number
            $to = $this->normalizePhoneNumber($data['to']);
            if (!$this->validatePhoneNumber($to)) {
                Log::error('SMS send failed: invalid phone number', ['to' => $data['to']]);
                return false;
            }

            $message = $data['message'];
            $from = $data['from'] ?? $this->config['from'] ?? null;

            // Send SMS based on provider
            $result = match($this->provider) {
                'twilio' => $this->sendViaTwilio($to, $message, $from),
                'nexmo', 'vonage' => $this->sendViaVonage($to, $message, $from),
                default => $this->sendViaGeneric($to, $message, $from),
            };

            if ($result['success']) {
                Log::info('SMS sent successfully', [
                    'to' => $to,
                    'provider' => $this->provider,
                    'message_id' => $result['message_id'] ?? null,
                ]);

                // Store SMS record in database
                $this->storeSMSRecord($to, $message, $result['message_id'] ?? null);
            }

            return $result['success'];

        } catch (\Exception $e) {
            Log::error('SMS send failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }
    }

    /**
     * Send SMS (convenience method)
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    public function sendSMS(string $to, string $message): array
    {
        $success = $this->send([
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => $success,
            'message_id' => $success ? 'sms_' . uniqid() : null,
        ];
    }

    /**
     * Schedule SMS for future delivery
     *
     * @param string $to
     * @param string $message
     * @param string $scheduleDate
     * @return array
     */
    public function scheduleSMS(string $to, string $message, string $scheduleDate): array
    {
        try {
            // Validate phone number
            $to = $this->normalizePhoneNumber($to);
            if (!$this->validatePhoneNumber($to)) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number',
                ];
            }

            // Store scheduled SMS in database
            $scheduleId = 'scheduled_' . uniqid();

            DB::table('cmis.scheduled_sms')->insert([
                'schedule_id' => $scheduleId,
                'to' => $to,
                'message' => $message,
                'scheduled_at' => $scheduleDate,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('SMS scheduled successfully', [
                'to' => $to,
                'schedule_id' => $scheduleId,
                'scheduled_at' => $scheduleDate,
            ]);

            return [
                'success' => true,
                'scheduled_id' => $scheduleId,
            ];

        } catch (\Exception $e) {
            Log::error('SMS scheduling failed', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS using a template
     *
     * @param string $template
     * @param array $data
     * @return bool
     */
    public function sendTemplate(string $template, array $data): bool
    {
        try {
            // Load template from database or config
            $templateContent = DB::table('cmis.sms_templates')
                ->where('template_name', $template)
                ->value('content');

            if (!$templateContent) {
                Log::error('SMS template not found', ['template' => $template]);
                return false;
            }

            // Replace placeholders in template
            $message = $this->replaceTemplatePlaceholders($templateContent, $data['variables'] ?? []);

            // Send SMS with templated message
            return $this->send([
                'to' => $data['to'],
                'message' => $message,
                'from' => $data['from'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Template SMS send failed', [
                'error' => $e->getMessage(),
                'template' => $template,
            ]);
            return false;
        }
    }

    /**
     * Validate phone number format
     *
     * @param string $phone
     * @return bool
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Remove all non-digit characters for validation
        $digitsOnly = preg_replace('/\D/', '', $phone);

        // Check if it has a reasonable length (7-15 digits)
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return false;
        }

        // Basic E.164 format validation (starts with + and has 7-15 digits)
        if (preg_match('/^\+?[1-9]\d{6,14}$/', $phone)) {
            return true;
        }

        // Also accept common formats like (123) 456-7890
        if (preg_match('/^(\+\d{1,3}[- ]?)?\(?\d{3}\)?[- ]?\d{3}[- ]?\d{4}$/', $phone)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // If doesn't start with +, assume default country code
        if (!str_starts_with($normalized, '+')) {
            $defaultCountryCode = $this->config['default_country_code'] ?? '+1';
            $normalized = $defaultCountryCode . $normalized;
        }

        return $normalized;
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $to
     * @param string $message
     * @param string|null $from
     * @return array
     */
    protected function sendViaTwilio(string $to, string $message, ?string $from): array
    {
        try {
            $sid = $this->config['account_sid'] ?? '';
            $token = $this->config['auth_token'] ?? '';
            $from = $from ?? $this->config['from'] ?? '';

            if (empty($sid) || empty($token) || empty($from)) {
                Log::warning('Twilio credentials not configured');
                return ['success' => false, 'error' => 'Twilio not configured'];
            }

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($url, [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via Vonage (formerly Nexmo)
     *
     * @param string $to
     * @param string $message
     * @param string|null $from
     * @return array
     */
    protected function sendViaVonage(string $to, string $message, ?string $from): array
    {
        try {
            $apiKey = $this->config['api_key'] ?? '';
            $apiSecret = $this->config['api_secret'] ?? '';
            $from = $from ?? $this->config['from'] ?? '';

            if (empty($apiKey) || empty($apiSecret) || empty($from)) {
                Log::warning('Vonage credentials not configured');
                return ['success' => false, 'error' => 'Vonage not configured'];
            }

            $url = 'https://rest.nexmo.com/sms/json';

            $response = Http::post($url, [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'to' => $to,
                'from' => $from,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageData = $data['messages'][0] ?? [];

                if (($messageData['status'] ?? '') === '0') {
                    return [
                        'success' => true,
                        'message_id' => $messageData['message-id'] ?? null,
                    ];
                }
            }

            return ['success' => false, 'error' => $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via generic provider (stub for testing)
     *
     * @param string $to
     * @param string $message
     * @param string|null $from
     * @return array
     */
    protected function sendViaGeneric(string $to, string $message, ?string $from): array
    {
        // Generic/test implementation
        Log::info('SMS sent via generic provider (test mode)', [
            'to' => $to,
            'from' => $from,
            'message' => substr($message, 0, 50) . '...',
        ]);

        return [
            'success' => true,
            'message_id' => 'generic_' . uniqid(),
        ];
    }

    /**
     * Store SMS record in database
     *
     * @param string $to
     * @param string $message
     * @param string|null $messageId
     * @return void
     */
    protected function storeSMSRecord(string $to, string $message, ?string $messageId): void
    {
        try {
            DB::table('cmis.sms_log')->insert([
                'message_id' => $messageId ?? 'sms_' . uniqid(),
                'to' => $to,
                'message' => $message,
                'provider' => $this->provider,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store SMS record', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Replace template placeholders
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    protected function replaceTemplatePlaceholders(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
            $template = str_replace("{{ " . $key . " }}", $value, $template);
        }

        return $template;
    }
}
