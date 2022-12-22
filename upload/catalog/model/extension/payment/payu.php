<?php
/*
* PayU Payment Modules
*
* @copyright  Copyright 2015 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/

class ModelExtensionPaymentPayu extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/payu');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_payu_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_payu_total') > 0 && $this->config->get('payment_payu_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_payu_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }


        $method_data = array();
        if ($status) {
            $method_data = array(
                'code' => 'payu',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_payu_sort_order')
            );
        }

        return $method_data;
    }


    public function bindOrderIdAndSessionId($orderId, $sessionId)
    {
        $query = 'INSERT INTO ' . DB_PREFIX . 'payu_so VALUES (NULL, "'.(int)$orderId.'", "'.$this->db->escape($sessionId).'", "'.OpenPayuOrderStatus::STATUS_PENDING.'")';
        return $this->db->query($query);
    }

    public function updateSatatus($sessionId, $status)
    {
        $query = 'UPDATE ' . DB_PREFIX . 'payu_so SET status="'.$this->db->escape($status).'" WHERE session_id ="'.$this->db->escape($sessionId).'"';
        return $this->db->query($query);
    }

    public function getOrderInfoBySessionId($sessionId)
    {
        $query = 'SELECT order_id, status FROM ' . DB_PREFIX . 'payu_so WHERE session_id ="'.$this->db->escape($sessionId).'"';
        $result = $this->db->query($query)->row;

        if(!$result)
        {
            return null;
        }

        return $result;
    }

    public function getSessionsForOrder($orderId)
    {
        $query = 'SELECT order_id, status FROM ' . DB_PREFIX . 'payu_so WHERE order_id ="' . $this->db->escape($orderId) . '"';
        $result = $this->db->query($query)->rows;

        if (!$result) {
            return null;
        }

        return $result;
    }
}
