<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use SoftLoft\SmsIntegration\Api\Data\NotificationInterface;
use SoftLoft\SmsIntegration\Api\EventLoggerInterface;
use SoftLoft\SmsIntegration\Api\Data\NotificationInterfaceFactory;
use SoftLoft\SmsIntegration\Api\NotificationRepositoryInterface;
use SoftLoft\SmsIntegration\Logger\Logger;

class EventLogger implements EventLoggerInterface
{
    private NotificationInterfaceFactory $notificationInterfaceFactory;
    private NotificationRepositoryInterface $notificationRepository;
    private Logger $logger;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param NotificationInterfaceFactory $notificationInterfaceFactory
     * @param NotificationRepositoryInterface $notificationRepository
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        NotificationInterfaceFactory $notificationInterfaceFactory,
        NotificationRepositoryInterface $notificationRepository,
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->notificationInterfaceFactory = $notificationInterfaceFactory;
        $this->notificationRepository = $notificationRepository;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function saveNotificationData(
        string $eventTypeCode,
        string $data,
        string $customerPhone,
        int $storeId
    ): void {
        $notification = $this->notificationInterfaceFactory->create();
        $notification->setEventTypeCode($eventTypeCode);
        $notification->setNotificationData($data);
        $notification->setCustomerPhone($customerPhone);
        $notification->setStatus(NotificationInterface::STATUS_PENDING);
        $notification->setStoreId($storeId);
        $notification->setCountAttempts(0);
        $this->notificationRepository->save($notification);

        if ($this->scopeConfig->getValue(self::ENABLE_LOGGER_PATH)) {
            $this->saveNotificationLog($eventTypeCode, $data, $customerPhone, $storeId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function saveNotificationLog(
        string $eventTypeCode,
        string $data,
        string $customerPhone,
        int $storeId
    ): void {
        $this->logger->info(
            'Event type code: ' . $eventTypeCode . ', '
           . 'Data: ' . $data . ', '
           . 'Customer Phone: ' . $customerPhone . ', '
           . 'Status: '. NotificationInterface::STATUS_PENDING . ', '
           . 'Store Id: '. $storeId
        );
    }
}
