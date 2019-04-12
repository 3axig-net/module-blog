<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Blog\Api;

use Magefan\Blog\Model\Category;

/**
 * Interface PostRepositoryInterface
 * @package Magefan\Blog\Api
 */
interface CategoryRepositoryInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function save(Category $category);

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getById($categoryId);

    /**
     * Retrieve Category matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface
    $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param Category $category
     * @return mixed
     */
    public function delete(Category $category);

    /**
     * Delete Category by ID.
     *
     * @param int $$postId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($categoryId);
}
