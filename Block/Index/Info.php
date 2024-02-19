<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Blog\Block\Index;

use Magento\Framework\View\Element\Template;

/**
 * Blog index info
 */
class Info extends Template
{
    public function getDescription(): string
    {
        return (string)$this->_scopeConfig->getValue(
            'mfblog/index_page/description',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
