<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface NotificationInterface extends ExtensibleDataInterface
{
    public const EVENT_TYPE_CODE = 'event_type_code';
    public const STORE_ID = 'store_id';
    public const STATUS = 'status';
    public const CONTENT = 'content';
    public const ENTITY_ID = 'entity_id';
    public const SORT_ORDER = 'sort_order';
    public const BANNER_ID = 'banner_id';
    public const COUNT_ATTEMPTS = 'count_attempts';
    public const ICON_POSITION = 'icon_position';
    public const IS_ACTIVE = 'is_active';
    public const HTML = 'html';
    public const STATUS_ACTIVE = 1;

    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    /**
     * Get banner_id
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Get banner_id
     * @param int $entity_id
     * @return NotificationInterface
     */
    public function setEntityId(int $entity_id): NotificationInterface;

    /**
     * Get content
     * @return string|null
     */
    public function getNotificationData(): ?string;

    /**
     * Set content
     * @param string $notificationData
     * @return NotificationInterface
     */
    public function setNotificationData(string $notificationData): NotificationInterface;

    /**
     * Get EventTypeCode
     * @return string|null
     */
    public function getEventTypeCode(): ?string;

    /**
     * Set link
     * @param string $eventTypeCode
     * @return NotificationInterface
     */
    public function setEventTypeCode(string $eventTypeCode): NotificationInterface;

    /**
     * Get customerPhone
     * @return string|null
     */
    public function getCustomerPhone(): ?string;

    /**
     * Set customerPhone
     * @param string $customerPhone
     * @return NotificationInterface
     */
    public function setCustomerPhone(string $customerPhone): NotificationInterface;

    /**
     * Get is_active
     *
     * @return int|null
     */
    public function getCountAttempts(): ?int;

    /**
     * Set is_active
     * @param int $countAttempts
     * @return NotificationInterface
     */
    public function setCountAttempts(int $countAttempts): NotificationInterface;

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status
     *
     * @param string $status
     * @return NotificationInterface
     */
    public function setStatus(string $status): NotificationInterface;

    /**
     * @param int $storeId
     * @return NotificationInterface
     */
    public function setStoreId(int $storeId): NotificationInterface;

    /**
     * @return int
     */
    public function getStoreId(): int;
}
