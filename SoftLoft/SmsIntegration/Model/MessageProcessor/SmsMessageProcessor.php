<?php
/*
 * author information
 */
declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Model\MessageProcessor;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use SoftLoft\SmsIntegration\Api\Data\NotificationInterface;
use SoftLoft\SmsIntegration\Api\SmsClientProviderInterface;
use SoftLoft\SmsIntegration\Api\SmsMessageProcessorInterface;
use SoftLoft\SmsIntegration\Api\SmsTemplatesRepositoryInterface;
use SoftLoft\SmsIntegration\Api\VariableFilterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SoftLoft\SmsIntegration\Model\ResourceModel\SmsBatchIteratorFactory;
use SoftLoft\SmsIntegration\Model\ResourceModel\SmsIntegration;

class SmsMessageProcessor implements SmsMessageProcessorInterface
{
    //Missing type comment
    /**
     * @var SmsClientProviderInterface
     */

    //Suggestion remove type of class property
    private SmsClientProviderInterface $smsClientProvider;
    private ScopeConfigInterface $scopeConfig;
    private Json $json;
    private SmsBatchIteratorFactory $smsBatchIteratorFactory;
    private VariableFilterInterface $variableFilter;
    private SmsTemplatesRepositoryInterface $smsTemplatesRepository;
    private SmsIntegration $smsIntegration;

    /**
     * @param SmsClientProviderInterface $smsClientProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param SmsBatchIteratorFactory $smsBatchIteratorFactory
     * @param VariableFilterInterface $variableFilter
     * @param SmsTemplatesRepositoryInterface $smsTemplatesRepository
     * @param SmsIntegration $ //Fix this
     */
    public function __construct(
        SmsClientProviderInterface $smsClientProvider,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        SmsBatchIteratorFactory $smsBatchIteratorFactory,
        VariableFilterInterface $variableFilter,
        SmsTemplatesRepositoryInterface $smsTemplatesRepository,
        SmsIntegration $smsIntegration
    ) {
        $this->smsClientProvider = $smsClientProvider;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->smsBatchIteratorFactory = $smsBatchIteratorFactory;
        $this->variableFilter = $variableFilter;
        $this->smsTemplatesRepository = $smsTemplatesRepository;
        $this->smsIntegration = $smsIntegration;
    }

    /**
     * Redudant @throw was removed.
     */
    public function process(): void
    {
        $isEnabled = $this->scopeConfig->getValue(self::IS_SENDING_ENABLE);
        $maxCountAttempts = $this->scopeConfig->getValue(self::MAX_COUNT_ATTEMPTS);

        if ($isEnabled) {
            $batchIterator = $this->smsBatchIteratorFactory->create();

            while ($rows = $batchIterator->getBatch()) {
                foreach ($rows as $index => &$row) {
                    if ($row['count_attempts'] > $maxCountAttempts) {
                        unset($rows[$index]);
                        continue;
                    }
                    $row['count_attempts']++;
                    //Request for message should be cached in repository, so it should create additional load on the system
                    //remove to messageBuilder method
                    $message = $this->smsTemplatesRepository->getMessageTemplateByEventTypeCode($row['event_type_code'], $row['store_id']);

                    foreach ($this->json->unserialize($row['notification_data']) as $key => $value) {
                        $message = $this->variableFilter->filter($key, (string) $value, $message);
                    }

                    try {
                        $this->smsClientProvider->send($row['phone_number'], $message);
                        $row['status'] = NotificationInterface::STATUS_COMPLETE;
                    } catch (\Exception $e) {
                        $row['status'] = NotificationInterface::STATUS_FAILED;
                    }
                }

                $this->smsIntegration->saveBatch($rows);
            }
        }
    }
}
