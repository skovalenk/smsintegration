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
    //Suggestion: remove type property. e.g. bellow

    /**
     * @var SmsClientProviderInterface
     */
    private $smsClientProvider;

    private ScopeConfigInterface $scopeConfig;
    private Json $json;
    private SmsBatchIteratorFactory $smsBatchIteratorFactory;
    private VariableFilterInterface $variableFilter;
    private SmsTemplatesRepositoryInterface $smsTemplatesRepository;
    private SmsIntegration $smsIntegration;

    //please be sure to add variable name - $smsIntegration
    /**
     * @param SmsClientProviderInterface $smsClientProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param SmsBatchIteratorFactory $smsBatchIteratorFactory
     * @param VariableFilterInterface $variableFilter
     * @param SmsTemplatesRepositoryInterface $smsTemplatesRepository
     * @param SmsIntegration $ 
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

    // Redudant @throw was removed. Pelase add method description. 
    public function process(): void
    {
        $isEnabled = $this->scopeConfig->getValue(self::IS_SENDING_ENABLE);
        $maxCountAttempts = $this->scopeConfig->getValue(self::MAX_COUNT_ATTEMPTS);

        if ($isEnabled) {
            $batchIterator = $this->smsBatchIteratorFactory->create();

            while ($rows = $batchIterator->getBatch()) {
                //We should not use variable references in the code. It is best practice. Please use $rows[$index] = ? instead.
                //Otherwise decalre a variable to store processed results.
                foreach ($rows as $index => &$row) {
                    //There is no check if key (count_attempts isset there). Also variables are not casted to int type.
                    if ($row['count_attempts'] > $maxCountAttempts) {
                        //Path complexity should be reached here, as we should not have as big nested level, please move the logic to the separate function or separate service to reduce that kind of complexity
                        unset($rows[$index]);
                        continue;
                    }
                    $row['count_attempts']++;
                    //1. We are not supposed to call repository in loop, because of performance degradation
                    //2. No additional check on event_type_code and store_id either
                    //3. Suggestion: move the code to messageTextBuilder method
                    //4. Please be sure that, a string line is less than 120 symbols, like this.
                    $message = $this->smsTemplatesRepository->getMessageTemplateByEventTypeCode($row['event_type_code'], $row['store_id']);
                    //What if json->unserialize will return not traversable object?
                    //Please be sure that json->unserialize return array first
                    foreach ($this->json->unserialize($row['notification_data']) as $key => $value) {
                        $message = $this->variableFilter->filter($key, (string) $value, $message);
                    }

                    try {
                        //No additional check for phone_number key.
                        $this->smsClientProvider->send($row['phone_number'], $message);
                        $row['status'] = NotificationInterface::STATUS_COMPLETE;
                    } catch (\Exception $e) {
                        //As it is CRON process we need to add some exceptions to CRON job, to have visibility on processed jobs.
                        //But as we are processing bulk requests, we can`t throw Exception directly here, instead of this
                        //please collect exception messages first, and throw them in the end of the function
                        $row['status'] = NotificationInterface::STATUS_FAILED;
                    }
                }

                //What if exception will be raised here. Please process exceptions of this method as well to do not stop
                //batch process
                $this->smsIntegration->saveBatch($rows);
            }
        }
    }
}
