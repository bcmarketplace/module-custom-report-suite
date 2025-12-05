<?php

namespace BCMarketplace\CustomReportSuite\Test\Unit\Controller\Adminhtml\CustomReport;

use BCMarketplace\CustomReportSuite\Api\CustomReportRepositoryInterface;
use BCMarketplace\CustomReportSuite\Controller\Adminhtml\CustomReport\Save;
use BCMarketplace\CustomReportSuite\Model\CustomReportFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    protected $save;

    /**
     * @var Context|Mock
     */
    protected $context;

    /**
     * @var DataPersistorInterface|Mock
     */
    protected $dataPersistor;

    /**
     * @var CustomReportRepositoryInterface|Mock
     */
    protected $customReportRepository;

    /**
     * @var CustomReportFactory|Mock
     */
    protected $customReportFactory;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectPageMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageManagerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(Context::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->context->method('getRequest')->willReturn($this->requestMock);

        $this->redirectPageMock = $this->createMock(\Magento\Framework\Controller\Result\RedirectFactory::class);
        $this->context->method('getResultRedirectFactory')->willReturn($this->redirectPageMock);

        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->context->method('getObjectManager')->willReturn($this->objectManagerMock);

        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->context->method('getMessageManager')->willReturn($this->messageManagerMock);

        $this->dataPersistor = $this->createMock(DataPersistorInterface::class);
        $this->customReportRepository = $this->createMock(CustomReportRepositoryInterface::class);
        $this->customReportFactory = $this->createMock(CustomReportFactory::class);
        $this->save = new Save($this->context, $this->dataPersistor, $this->customReportRepository, $this->customReportFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->save);
        unset($this->context);
        unset($this->dataPersistor);
        unset($this->customReportRepository);
        unset($this->customReportFactory);
    }

    public function testExecute(): void
    {
        $data = [
            'report_id' => 1
        ];
        $this->requestMock->method('getPostValue')->willReturn($data);

        $redirectPageMock = $this->createMock(\Magento\Framework\Controller\Result\Redirect::class);
        $this->redirectPageMock->method('create')->willReturn($redirectPageMock);

        $customReportMock = $this->createMock(\BCMarketplace\CustomReportSuite\Model\CustomReport::class);
        $customReportMock->method('getId')->willReturn(1);
        $this->customReportFactory->method('create')->willReturn($customReportMock);
        $this->customReportRepository->method('save')->willReturn($customReportMock);

        $redirectPageMock->method('setPath')->willReturnSelf();

        $this->save->execute();
    }
}
