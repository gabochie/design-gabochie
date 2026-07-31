<?php
defined('ABSPATH') || exit;

class GS_SMS {

    public function __construct() {}

    public function is_configured(): bool {
        return class_exists('SendinblueApiClient')
            && !empty(get_option('sib_api_key_v3', ''));
    }

    private function client(): ?SendinblueApiClient {
        if (!$this->is_configured()) return null;
        return new SendinblueApiClient();
    }

    public function get_sender(): string {
        return get_option('gs_sms_sender', 'Gabochie');
    }

    public function send(string $to, string $message, array $opts = []): array {
        $client = $this->client();
        if (!$client) {
            return ['success' => false, 'error' => 'Brevo API key not configured'];
        }

        $phone = $this->format_phone($to);
        if (!$phone) {
            return ['success' => false, 'error' => "Invalid phone number: $to"];
        }

        $payload = [
            'sender'    => $opts['sender'] ?? $this->get_sender(),
            'recipient' => $phone,
            'content'   => $message,
            'type'      => $opts['type'] ?? 'transactional',
            'tag'       => $opts['tag'] ?? 'gabochie-outreach',
        ];

        if (!empty($opts['web_url'])) {
            $payload['webUrl'] = $opts['web_url'];
        }

        $result = $client->post('/transactionalSMS/send', $payload);
        $code = $client->getLastResponseCode();

        if ($code >= 200 && $code < 300) {
            return [
                'success'     => true,
                'message_id'  => $result['messageId'] ?? '',
                'credits_rem' => $result['remainingCredits'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error'   => $result['message'] ?? "HTTP $code",
            'code'    => $code,
        ];
    }

    public function test_connection(): array {
        $client = $this->client();
        if (!$client) {
            return ['success' => false, 'error' => 'Brevo API key not configured.'];
        }

        $result = $client->getAccount();
        $code = $client->getLastResponseCode();

        if ($code === 200 && !empty($result['email'])) {
            $credits = $result['plan'][0]['credits'] ?? 0;
            $sms_credits = 0;
            foreach (($result['plan'] ?? []) as $p) {
                if (($p['type'] ?? '') === 'sms') {
                    $sms_credits = (int) ($p['credits'] ?? 0);
                }
            }
            return [
                'success'     => true,
                'message'     => "Connected as {$result['email']} (Plan: {$result['plan'][0]['type']}, Credits: {$result['plan'][0]['credits']}, SMS: $sms_credits)",
                'sms_credits' => $sms_credits,
            ];
        }

        return ['success' => false, 'error' => $result['message'] ?? "HTTP $code — check API key"];
    }

    public function get_credits(): ?int {
        $client = $this->client();
        if (!$client) return null;

        $result = $client->getAccount();
        if ($client->getLastResponseCode() !== 200) return null;

        foreach (($result['plan'] ?? []) as $p) {
            if (($p['type'] ?? '') === 'sms') {
                return (int) ($p['credits'] ?? 0);
            }
        }
        return (int) (($result['plan'][0]['credits'] ?? 0));
    }

    public function get_plan(): ?string {
        $client = $this->client();
        if (!$client) return null;
        $result = $client->getAccount();
        if ($client->getLastResponseCode() !== 200) return null;
        return $result['plan'][0]['type'] ?? null;
    }

    public function get_account_email(): ?string {
        $client = $this->client();
        if (!$client) return null;
        $result = $client->getAccount();
        if ($client->getLastResponseCode() !== 200) return null;
        return $result['email'] ?? null;
    }

    // Ghana numbers: 0XX -> 233XX
    private function format_phone(string $phone): ?string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return null;

        if (strlen($phone) === 9 && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '233' . substr($phone, 1);
        } elseif (str_starts_with($phone, '233')) {
            // already correct
        } elseif (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } else {
            return null;
        }

        if (strlen($phone) < 11 || strlen($phone) > 14) return null;

        return '+' . $phone;
    }
}
