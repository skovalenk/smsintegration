<?php

namespace SoftLoft\SmsIntegration\Test\Unit;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SoftLoft\SmsIntegration\Model\MessageProcessor\Filter\DefaultFilter;
use SoftLoft\SmsIntegration\Model\MessageProcessor\SmsMessageProcessor;
use SoftLoft\SmsIntegration\Model\ResourceModel\SmsBatchIterator;
use SoftLoft\SmsIntegration\Model\ResourceModel\SmsBatchIteratorFactory;
use SoftLoft\SmsIntegration\Model\ResourceModel\SmsIntegration;
use SoftLoft\SmsIntegration\Model\SmsTemplatesRepository;

class TestSmsMessageProcessor extends \PHPUnit\Framework\TestCase
{
    private ObjectManager $objectManagerHelper;

    /** @var SmsMessageProcessor  */
    private $smsMessageProcessor;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\SoftLoft\SmsIntegration\Model\Client\DefaultSmsClient
     */
    private $scopeConfigMock;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\SoftLoft\SmsIntegration\Model\Client\DefaultSmsClient
     */
    private $smsClientProvider;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|SmsBatchIteratorFactory
     */
    private $smsBatchIteratorFactory;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\SoftLoft\SmsIntegration\Model\MessageProcessor\Filter\DefaultFilter
     */
    private $variableFilter;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|SmsTemplatesRepository
     */
    private $smsTemplateRepository;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|SmsIntegration
     */
    private $smsIntegration;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|SmsBatchIterator
     */
    private $smsBatchIterator;


    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->scopeConfigMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->smsClientProvider = $this->getMockBuilder(\SoftLoft\SmsIntegration\Model\Client\DefaultSmsClient::class)
            ->onlyMethods(['send'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->smsBatchIteratorFactory = $this->getMockBuilder(SmsBatchIteratorFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->smsBatchIterator = $this->getMockBuilder(SmsBatchIterator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->smsBatchIteratorFactory->method('create')
            ->willReturn($this->smsBatchIterator);
        $this->smsTemplateRepository = $this->getMockBuilder(SmsTemplatesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->smsIntegration = $this->getMockBuilder(SmsIntegration::class)
            ->onlyMethods(['saveBatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->smsMessageProcessor = $this->objectManagerHelper->getObject(
            SmsMessageProcessor::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'smsClientProvider' => $this->smsClientProvider,
                'smsBatchIteratorFactory' => $this->smsBatchIteratorFactory,
                'smsTemplatesRepository' => $this->smsTemplateRepository,
                'smsIntegration' => $this->smsIntegration,
                'json' => $this->objectManagerHelper->getObject(Json::class),
                'variableFilter' => $this->objectManagerHelper->getObject(DefaultFilter::class)
            ]
        );

        parent::setUp();
    }

    /**
     * @dataProvider oneRowBatch
     */
    public function testOneBatchProcessing(array $row, $templateMessage)
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $this->smsBatchIterator->expects($this->any())
            ->method('getBatch')
            ->willReturn([$row], []);
        $this->smsTemplateRepository
            ->expects($this->exactly(1))
            ->method('getMessageTemplateByEventTypeCode')
            ->with('unit_test', 1)
            ->willReturn($templateMessage);
        $this->smsClientProvider->expects($this->once())
            ->method('send')
            ->with($row['phone_number'], 'This is test sms message with 213');
        $row['status'] = 'complete';
        $row['count_attempts'] = 1;
        $this->smsIntegration->expects($this->once())
            ->method('saveBatch')
            ->with([$row]);
        $this->smsMessageProcessor->process();
    }

    /**
     * @return array[]
     */
    public function oneRowBatch(): array
    {
        return [
            [
                [
                    'entity_id' => 1,
                    'event_type_code' => 'unit_test',
                    'store_id' => 1,
                    'count_attempts' => 0,
                    'phone_number' => '102',
                    'notification_data' => json_encode(['test_variable' => 213])
                ],
                'This is test sms message with %test_variable%'
            ]
        ];
    }
}
