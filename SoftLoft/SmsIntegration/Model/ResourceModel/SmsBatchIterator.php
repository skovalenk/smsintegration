<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class SmsBatchIterator
{
    private const DEFAULT_BATCH_SIZE = 1000;
    private int $currentPage = 0;
    private ResourceConnection $resourceConnection;
    private int $batchSize;

    /**
     * @param ResourceConnection $resourceConnection
     * @param int $batchSize
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->batchSize = $batchSize;
    }

    /**
     * @return array
     */
    public function getBatch(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('sms_messages')
            ->where('status IN(?)', ['pending', 'failed']);

        $select->limitPage($this->currentPage++, $this->batchSize);
        return $connection->fetchAll($select);
    }
}
