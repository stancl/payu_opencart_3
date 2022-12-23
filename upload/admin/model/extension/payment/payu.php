<?php

class ModelExtensionPaymentPayu extends Model
{
    public function getOrderStatus($orderId)
    {
        if (! $orderId) {
            return 'UNKNOWN';
        }

        $query = 'SELECT order_id, status FROM ' . DB_PREFIX . 'payu_so WHERE order_id ="' . $this->db->escape($orderId) . '"';
        $rows = $this->db->query($query)->rows;

        if (! $rows) {
            return 'UNKNOWN';
        }

        $status = 'UNKNOWN';

        foreach ($rows as $row) {
            if ($row['status'] === 'COMPLETED') {
                // takes precedence over everything
                return 'COMPLETED';
            }

            if ($row['status'] === 'WAITING_FOR_CONFIRMATION') {
                // essentially an early stage of COMPLETED; takes precedence over everything. can't coexist with COMPLETED (see comment below)
                return 'WAITING_FOR_CONFIRMATION';
            }

            if ($row['status'] === 'CANCELED') {
                // the above statuses can only exist once: they represent a successful transaction so there's no need for another transaction to exist
                // however, CANCELED can exist multiple times (multiple failed attempts, and then a successful one)
                // so we don't immediately return (in case the next value is COMPLETED or WAITING_FOR_CONFIRMATION)
                $status = 'CANCELED';
            }

            if ($row['status'] === 'PENDING' && $status !== 'CANCELED') {
                // lowest priority, we only set it if the $status isn't already CANCELED from another transaction/attempt
                // PENDING essentially indicates a bounced/unfinished payment page (or a payment that's being processed *right now*)
                $status = 'PENDING';
            }
        }

        return $status;
    }

    public function createDatabaseTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payu_so` (
            `bind_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_id` int(32) NOT NULL,
            `session_id` varchar(32) NOT NULL,
            `status` varchar(32)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->db->query($sql);
    }

    public function dropDatabaseTables()
    {
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "payu_so`;";
        $this->db->query($sql);
    }
}
