<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SmsIntegration extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sms_messages', 'entity_id');
    }

    /**
     * @param array $smsMessages
     */
    public function saveBatch(array $smsMessages): void
    {
        $data = [];

        foreach ($smsMessages as $smsMessage) {
            $data[] = [
                'entity_id' => $smsMessage['entity_id'],
                'status' => $smsMessage['status'],
                'count_attempts' => $smsMessage['count_attempts'],
            ];
        }
        $this->getConnection()->insertOnDuplicate(
            'sms_messages',
            ['entity_id', 'status', 'count_attempts'],
            $data
        );
    }
}
