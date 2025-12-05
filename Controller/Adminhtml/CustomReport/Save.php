<?php declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Controller\Adminhtml\CustomReport;

use BCMarketplace\CustomReportSuite\Api\CustomReportRepositoryInterface;
use BCMarketplace\CustomReportSuite\Model\CustomReportFactory;
use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'BCMarketplace_CustomReportSuite::customreport_save';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    
    /**
     * @var CustomReportRepositoryInterface
     */
    private $customReportRepository;
    
    /**
     * @var CustomReportFactory
     */
    private $customReportFactory;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param CustomReportRepositoryInterface $customReportRepository
     * @param CustomReportFactory $customReportFactory
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor,
        CustomReportRepositoryInterface $customReportRepository,
        CustomReportFactory $customReportFactory
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->customReportRepository = $customReportRepository;
        $this->customReportFactory = $customReportFactory;
        parent::__construct($context);
    }

    /**
     * Save action
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            // Handle both old and new column names for backward compatibility
            if (empty($data['report_id']) && !empty($data['report_id'])) {
                $data['report_id'] = $data['report_id'];
            }
            if (empty($data['report_id'])) {
                $data['report_id'] = null;
            }

            $id = $this->getRequest()->getParam('report_id') ?: $this->getRequest()->getParam('report_id');
            if ($id) {
                $customReport = $this->customReportRepository->getById($id);
            } else {
                $customReport = $this->customReportFactory->create();
            }

            $customReport->setData($data);

            try {
                $this->customReportRepository->save($customReport);
                $this->messageManager->addSuccessMessage(__('You saved the report.'));
                $this->dataPersistor->clear('bcmarketplace_customreportsuite_customreport');
                if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath(
                    '*/*/edit',
                    ['report_id' => $customReport->getId(), '_current' => true]
                );
                }

                return $resultRedirect->setPath('*/*/listing');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the data.'));
            }

            $this->dataPersistor->set('bcmarketplace_customreportsuite_customreport', $data);

            $reportId = $this->getRequest()->getParam('report_id') ?: $this->getRequest()->getParam('report_id');
            return $resultRedirect->setPath(
                '*/*/edit',
                ['report_id' => $reportId]
            );
        }

        return $resultRedirect->setPath('*/*/listing');
    }
}
