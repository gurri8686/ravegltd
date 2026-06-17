<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Mail\Mailable;
use GuzzleHttp\Client;

trait SendsResendMail
{
    /**
     * Send a Laravel Mailable via the Resend HTTP API (https://api.resend.com/emails).
     *
     * Used because the production host blocks all outbound SMTP ports. Guzzle over
     * HTTPS (443) is never blocked. Throws an \Exception on any non-2xx response so
     * existing try/catch blocks at the call sites surface a useful message.
     *
     * @param  string                          $toEmail
     * @param  \Illuminate\Mail\Mailable       $mailable
     * @return void
     */
    protected function sendMailable($toEmail, Mailable $mailable)
    {
        // render() runs build() internally exactly once and returns the HTML string.
        // We read subject/cc/attachments AFTER render() so build() has populated them,
        // and we never call build() ourselves — that avoids double-attaching.
        $html = $mailable->render();

        $subject = (string) ($mailable->subject ?? '');

        // Collect CC addresses (each entry: ['address' => ..., 'name' => ...]).
        $cc = [];
        foreach ((array) ($mailable->cc ?? []) as $entry) {
            if (is_array($entry) && !empty($entry['address'])) {
                $cc[] = $entry['address'];
            } elseif (is_string($entry) && $entry !== '') {
                $cc[] = $entry;
            }
        }

        // Collect attachments.
        $attachments = [];

        // Raw (in-memory) attachments via attachData(): ['data','name','options'].
        foreach ((array) ($mailable->rawAttachments ?? []) as $att) {
            if (!isset($att['data'])) {
                continue;
            }
            $attachments[] = [
                'filename' => $att['name'] ?? 'attachment',
                'content'  => base64_encode($att['data']),
            ];
        }

        // File-path attachments via attach(): ['file','options'].
        foreach ((array) ($mailable->attachments ?? []) as $att) {
            if (empty($att['file']) || !is_file($att['file'])) {
                continue;
            }
            $attachments[] = [
                'filename' => $att['options']['as'] ?? basename($att['file']),
                'content'  => base64_encode((string) file_get_contents($att['file'])),
            ];
        }

        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name');
        $from        = $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress;

        $apiKey = env('MAIL_PASSWORD') ?: env('RESEND_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('Resend API key is not configured (MAIL_PASSWORD / RESEND_API_KEY).');
        }

        $payload = [
            'from'    => $from,
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $html,
        ];
        if (!empty($cc)) {
            $payload['cc'] = $cc;
        }
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        $client = new Client(['timeout' => 30]);

        $response = $client->request('POST', 'https://api.resend.com/emails', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json'         => $payload,
            'http_errors'  => false,
        ]);

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new \Exception('Resend API error (HTTP ' . $status . '): ' . $body);
        }
    }
}
