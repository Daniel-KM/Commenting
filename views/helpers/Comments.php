<?php
/**
 * Helper to get some comments.
 *
 * If options 'all_rights' is not set and true, rights are checked.
 */
class Commenting_View_Helper_Comments extends Zend_View_Helper_Abstract
{
    protected $_table;

    /**
     * Load the hit table one time only.
     */
    public function __construct()
    {
        $this->_table = get_db()->getTable('Comment');
    }

    /**
     * Get the comments.
     *
     * @return This view helper.
     */
    public function comments()
    {
        return $this;
    }

    public function get($options = array())
    {
        $searchParams = $this->_getSearchParams($options);
        $select = $this->_table->getSelectForFindBy($searchParams);
        if (isset($options['order'])) {
            $select->order("ORDER BY added " . $options['order']);
        }
        return $this->_table->fetchObjects($select);
    }

    public function total($options = array())
    {
        $searchParams = $this->_getSearchParams($options);
        return $this->_table->count($searchParams);
    }

    private function _getSearchParams($options)
    {
        $searchParams = $options;

        if (isset($options['record']) && $options['record']) {
            $searchParams['record_type'] = get_class($options['record']);
            $searchParams['record_id'] = $options['record']->id;
            unset($searchParams['record']);
        }
        elseif (!(isset($options['record_type']) && isset($options['record_id']))) {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $params = $request->getParams();
            $searchParams['record_type'] = $this->_getRecordType($params);
            $searchParams['record_id'] = $this->_getRecordId($params);
        }

        if (!(isset($options['all_rights']) && $options['all_rights'])) {
            if ((get_option('commenting_allow_public') == 1)
                    || (get_option('commenting_allow_public_view') == 1)
                    || is_allowed('Commenting_Comment', 'show')
                ) {
                $searchParams['approved'] = true;
            }

            if (!is_allowed('Commenting_Comment', 'update-approved')) {
                $searchParams['flagged'] = 0;
                $searchParams['is_spam'] = 0;
            }
        }

        return $searchParams;
    }

    /**
     * Helper to get record id from request params.
     *
     * @see plugins/Commenting/forms/CommentForm.php
     *
     * @todo To be merged.
     */
    private function _getRecordId($params)
    {
        if (isset($params['module'])
                && $params['module'] == 'commenting'
                && $params['controller'] == 'comment'
                && $params['action'] == 'add'
            ) {
            return $params['record_id'];
        }

        if (isset($params['module'])) {
            switch ($params['module']) {
                case 'exhibit-builder':
                    $view = get_view();
                    // ExhibitBuilder uses slugs in the params, so need to
                    // negotiate around those to dig up the record_id and model.
                    if (isset($view->exhibit) && isset($view->exhibit_pages)) {
                        $id = $view->exhibit->id;
                    }
                    elseif (isset($view->exhibit_page)) {
                        $id = $view->exhibit_page->id;
                    }
//todo: check the ifs for an exhibit showing an item
                    elseif (isset($params['item_id'])) {
                        $id = $params['item_id'];
                    }
                    else {
                        $id = isset($params['id']) ? $params['id'] : null;
                    }
                    break;

                default:
                    $id = isset($params['id']) ? $params['id'] : null;
                    break;
            }
        }
        // Default for collections, items and files.
        else {
            $id = $params['id'];
        }
        return $id;
    }

    /**
     * Helper to get record type from request params.
     *
     * @see plugins/Commenting/forms/CommentForm.php
     *
     * @todo To be merged.
     */
    private function _getRecordType($params)
    {
        if (isset($params['module'])
                && $params['module'] == 'commenting'
                && $params['controller'] == 'comment'
                && $params['action'] == 'add'
            ) {
            return $params['record_type'];
        }

        if (isset($params['module'])) {
            switch ($params['module']) {
                case 'exhibit-builder':
                    $view = get_view();
                    // ExhibitBuilder uses slugs in the params, so need to
                    // negotiate around those to dig up the record_id and model.
                    if (isset($view->exhibit) && isset($view->exhibit_pages)) {
                        $model = 'Exhibit';
                    }
                    elseif (isset($view->exhibit_page)) {
                        $model = 'ExhibitPage';
                    }
// Todo: check the ifs for an exhibit showing an item.
                    else {
                        $model = 'Item';
                    }
                    break;

                default:
                    $model = Inflector::camelize($params['module']) . ucfirst( $params['controller'] );
                    break;
            }
        }
        // Default for collections, items and files.
        else {
            $model = ucfirst(Inflector::singularize($params['controller']));
        }
        return $model;
    }
}
