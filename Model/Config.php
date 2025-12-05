<?php
/**
 * Copyright © Baako Consulting LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace BCMarketplace\CustomReportSuite\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration model for BCMarketplace Custom Report Suite module
 *
 * Provides methods to read module configuration values.
 *
 * @author Raphael Baako <rbaako@baakoconsultingllc.com>
 * @company Baako Consulting LLC
 */
class Config
{
    private const XML_PATH_GENERAL_ENABLED = 'bcmarketplace_customreportsuite/general/enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if Custom Report Suite is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
