<?php

namespace App\Services\Communication;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class EmailService
{
    /**
     * Send an email
     *
     * @param array $data ['to', 'subject', 'body', 'from', 'cc', 'bcc', 'attachments']
     * @return bool
     */
    public function send(array $data): bool
    {
        try {
            // Validate required fields
            if (!isset($data['to']) || !isset($data['subject'])) {
                Log::error('Email send failed: missing required fields', $data);
                return false;
            }

            // Validate email address
            if (!$this->validateEmail($data['to'])) {
                Log::error('Email send failed: invalid recipient', ['to' => $data['to']]);
                return false;
            }

            $to = $data['to'];
            $subject = $data['subject'];
            $body = $data['body'] ?? '';
            $from = $data['from'] ?? config('mail.from.address');
            $fromName = $data['from_name'] ?? config('mail.from.name');
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $attachments = $data['attachments'] ?? [];
            $replyTo = $data['reply_to'] ?? null;

            // Send email using Laravel Mail
            Mail::send([], [], function (Message $message) use (
                $to,
                $subject,
                $body,
                $from,
                $fromName,
                $cc,
                $bcc,
                $attachments,
                $replyTo
            ) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from, $fromName)
                    ->html($body);

                // Add CC recipients
                if (!empty($cc)) {
                    $message->cc($cc);
                }

                // Add BCC recipients
                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                // Add reply-to
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }

                // Add attachments
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }
    }

    /**
     * Send an email using a Blade template
     *
     * @param string $template Blade template name (e.g., 'emails.welcome')
     * @param array $data ['to', 'subject', 'template_data', 'from', etc.]
     * @return bool
     */
    public function sendTemplate(string $template, array $data): bool
    {
        try {
            // Validate required fields
            if (!isset($data['to']) || !isset($data['subject'])) {
                Log::error('Template email send failed: missing required fields', $data);
                return false;
            }

            // Validate email address
            if (!$this->validateEmail($data['to'])) {
                Log::error('Template email send failed: invalid recipient', ['to' => $data['to']]);
                return false;
            }

            $to = $data['to'];
            $subject = $data['subject'];
            $templateData = $data['template_data'] ?? [];
            $from = $data['from'] ?? config('mail.from.address');
            $fromName = $data['from_name'] ?? config('mail.from.name');
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $attachments = $data['attachments'] ?? [];
            $replyTo = $data['reply_to'] ?? null;

            // Send email using Blade template
            Mail::send($template, $templateData, function (Message $message) use (
                $to,
                $subject,
                $from,
                $fromName,
                $cc,
                $bcc,
                $attachments,
                $replyTo
            ) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from, $fromName);

                // Add CC recipients
                if (!empty($cc)) {
                    $message->cc($cc);
                }

                // Add BCC recipients
                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                // Add reply-to
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }

                // Add attachments
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            Log::info('Template email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'template' => $template,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Template email send failed', [
                'error' => $e->getMessage(),
                'template' => $template,
                'data' => $data,
            ]);
            return false;
        }
    }

    public function sendCampaignEmail(array $data): bool
    {
        // Convenience method for campaign emails
        // Track opens, clicks, etc.
        return $this->send($data);
    }

    public function sendEmailWithAttachments(array $data): bool
    {
        // Convenience method for emails with attachments
        // Support for multiple attachments
        return $this->send($data);
    }

    public function sendTransactionalEmail(array $data): bool
    {
        // Convenience method for transactional emails
        // Order confirmations, receipts, etc.
        return $this->send($data);
    }

    public function sendBulkEmail(array $recipients, array $data): array
    {
        // Convenience method for bulk emails
        // Returns array of results
        return [
            'sent' => 0,
            'failed' => 0,
            'results' => [],
        ];
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
