<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\Blog\Model\Import;

use Magento\Framework\Config\ConfigOptionsListConstants;
/**
 * Aw import model
 */
class Mageplaza extends AbstractImport
{
    public function execute()
    {
        $config = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\App\DeploymentConfig');
        $pref = ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT . '/';
        $this->setData(
            'dbhost',
            $config->get($pref . ConfigOptionsListConstants::KEY_HOST)
        )->setData(
            'uname',
            $config->get($pref . ConfigOptionsListConstants::KEY_USER)
        )->setData(
            'pwd',
            $config->get($pref . ConfigOptionsListConstants::KEY_PASSWORD)
        )->setData(
            'dbname',
            $config->get($pref . ConfigOptionsListConstants::KEY_NAME)
        );
        $host = $this->getData('dbhost') ?: $this->getData('host');
        if (false !== strpos($host, '.sock')) {
            $con = $this->_connect = mysqli_connect(
                'localhost',
                $this->getData('uname'),
                $this->getData('pwd'),
                $this->getData('dbname'),
                null,
                $host
            );
        } else {
            $con = $this->_connect = mysqli_connect(
                $host,
                $this->getData('uname'),
                $this->getData('pwd'),
                $this->getData('dbname')
            );
        }
        if (mysqli_connect_errno()) {
            throw new \Exception("Failed connect to magento database", 1);
        }

        $_pref = mysqli_real_escape_string(
            $con,
            $config->get('db/table_prefix')
        );


        /////////////////////////////////////////////////////////////////////////////////////////////



        $sql = 'SELECT * FROM '.$_pref.'mageplaza_blog_category LIMIT 1';

        try {
            $this->_mysqliQuery($sql);
        } catch (\Exception $e) {
            throw new \Exception(__('Mageplaza Blog Extension not detected.'), 1);
        }
        $storeIds = array_keys($this->_storeManager->getStores(true));
        $categories = [];
        $oldCategories = [];
        /* Import categories */
        $sql = 'SELECT
                    t.category_id as old_id,
                    t.name as title,
                    t.url_key as identifier,
                    t.position as position,
                    t.meta_keywords as meta_keywords,
                    t.meta_description as meta_description,
                    t.description as content,
                    t.parent_id as path,
                    t.enabled as is_active,
                    t.store_ids as store_ids_old


                FROM '.$_pref.'mageplaza_blog_category t';
        $result = $this->_mysqliQuery($sql);
        while ($data = mysqli_fetch_assoc($result)) {
            /* Prepare category data */
            /* Find store ids */

//            $s_sql = 'SELECT post_id FROM '.$_pref.'mageplaza_blog_post_category WHERE category_id = "'.$data['old_id'].'"';
//            $s_result = $this->_mysqliQuery($s_sql);
//            while ($s_data = mysqli_fetch_assoc($s_result)) {
//                 $data['post_id'][] = $s_data['post_id'];
//            }


            $data['store_ids'] = [];


            foreach ($data as $key => $value) {
                if ($key == $data['store_ids_old']) {
                    $storeData = explode(',', $value);
                    foreach ($storeData as  $valueStore) {
                        $data['store_ids'][] = $valueStore;
                    }
                }
            }

            unset($data['store_ids_old']);

            $data['identifier'] = trim(strtolower($data['identifier']));
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }
            $category = $this->_categoryFactory->create();
            try {
                /* Initial saving */
                $category->setData($data)->save();
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $oldCategories[$category->getOldId()] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->addDebug('Blog Category Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }
        /* Import posts */
        $sql = 'SELECT * FROM '.$_pref.'mageplaza_blog_post';
        $result = $this->_mysqliQuery($sql);
        while ($data = mysqli_fetch_assoc($result)) {



            $c_sql = 'SELECT  category_id FROM '.$_pref.'mageplaza_blog_post_category WHERE post_id = "'.$data['post_id'].'"';

            $c_result = $this->_mysqliQuery($c_sql);
            while ($c_data = mysqli_fetch_assoc($c_result)) {
                $oldId = $c_data['category_id'];
                if (isset($oldCategories[$oldId])) {
                    $id = $oldCategories[$oldId]->getId();
                    $postCategories[$id] = $id;
                }
            }



            $data = [
              //  'store_ids'         => $data['store_ids'],
                'title'             => $data['name'],
                'meta_keywords'     => $data['meta_keywords'],
                'meta_description'  => $data['meta_description'],
                'identifier'        => $data['post_content'],
                'content'           => str_replace('<!--more-->', '<!-- pagebreak -->', $data['post_content']),
                'creation_time'     => strtotime($data['created_at']),
                'update_time'       => strtotime($data['updated_at']),
                'publish_time'      => strtotime($data['publish_date']),
                'is_active'         => (int)($data['enabled']),
                'featured_img'      => $data['image'],
                'media_gallery'     => $data['image'],
                'categories' => $postCategories
            ];
            $data['identifier'] = trim(strtolower($data['identifier']));
            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();
                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->addDebug('Blog Post Import [' . $data['title'] . ']: '. $e->getMessage());
            }
            unset($post);
        }
        /* end */
        mysqli_close($con);
    }
}
