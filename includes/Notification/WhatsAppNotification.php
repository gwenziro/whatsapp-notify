<?php

/**
 * WhatsApp Notification Handler Class
 *
 * @package WhatsApp_Notify
 * @since   1.0.0
 */

namespace WANotify\Notification;

use WANotify\Api\ApiClient;
use WANotify\Logging\Logger;
use WANotify\Utils\Formatter;

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WhatsAppNotification
 * 
 * Implementasi pengirim notifikasi melalui WhatsApp
 */
class WhatsAppNotification
{
    /**
     * API Client
     *
     * @var ApiClient
     */
    protected $api;

    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ApiClient $api API Client
     * @param Logger $logger Logger instance
     */
    public function __construct(ApiClient $api, Logger $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Kirim notifikasi WhatsApp
     *
     * @param string $recipient Nomor penerima
     * @param string $message Pesan notifikasi
     * @param array $params Parameter tambahan
     * @return array Response dari API
     */
    public function send($recipient, $message, $params = [])
    {
        // Format nomor penerima untuk memastikan format yang benar
        $recipient = Formatter::phone_number($recipient);

        if (empty($recipient)) {
            $this->logger->error('Recipient number is empty or invalid');
            return [
                'success' => false,
                'message' => 'Recipient number is empty or invalid'
            ];
        }

        // Log detail pengiriman
        $this->logger->info('Sending WhatsApp notification', [
            'recipient' => $recipient,
            'form_id' => $params['form_id'] ?? null
        ]);

        // Tambahkan emoji pada awal pesan jika belum ada
        if (!preg_match('/^[\x{1F300}-\x{1F6FF}]/u', $message)) {
            $default_emoji = '🔔';
            $message = $default_emoji . ' ' . $message;
        }

        // Kirim notifikasi melalui API Client
        $form_id = isset($params['form_id']) ? $params['form_id'] : null;
        $result = $this->api->send_notification($recipient, $message, $form_id);

        // Log hasil pengiriman
        if ($result['success']) {
            $this->logger->info('WhatsApp notification sent successfully', [
                'recipient' => $recipient,
                'form_id' => $form_id
            ]);
        } else {
            $this->logger->error('Failed to send WhatsApp notification', [
                'recipient' => $recipient,
                'form_id' => $form_id,
                'error' => $result['message']
            ]);
        }

        return $result;
    }

    /**
     * Kirim notifikasi grup WhatsApp
     *
     * @param string $group_id ID grup WhatsApp
     * @param string $message Pesan notifikasi
     * @param array $params Parameter tambahan
     * @return array Response dari API
     */
    public function send_to_group($group_id, $message, $params = [])
    {
        if (empty($group_id)) {
            $this->logger->error('Group ID is empty');
            return [
                'success' => false,
                'message' => 'Group ID is empty'
            ];
        }

        // Log detail pengiriman
        $this->logger->info('Sending WhatsApp group notification', [
            'group_id' => $group_id,
            'form_id' => $params['form_id'] ?? null
        ]);

        // Tambahkan emoji pada awal pesan jika belum ada
        if (!preg_match('/^[\x{1F300}-\x{1F6FF}]/u', $message)) {
            $default_emoji = '🔔';
            $message = $default_emoji . ' ' . $message;
        }

        // Kirim notifikasi grup melalui API Client
        $form_id = isset($params['form_id']) ? $params['form_id'] : null;
        $result = $this->api->send_group_notification($group_id, $message, $form_id);

        // Log hasil pengiriman
        if ($result['success']) {
            $this->logger->info('WhatsApp group notification sent successfully', [
                'group_id' => $group_id,
                'form_id' => $form_id
            ]);
        } else {
            $this->logger->error('Failed to send WhatsApp group notification', [
                'group_id' => $group_id,
                'form_id' => $form_id,
                'error' => $result['message']
            ]);
        }

        return $result;
    }

    /**
     * Tes koneksi WhatsApp
     *
     * @return array Response dari API
     */
    public function test_connection()
    {
        return $this->api->test_connection();
    }
}
