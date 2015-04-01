<?php
class Commenting_CommentForm extends Omeka_Form
{
    protected $_record;
    private $_antispam;

    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     * @return void
     */
    public function __construct($record = null, $antispam = array())
    {
        $this->_record = $record;
        $this->_antispam = $antispam;

        parent::__construct();
    }

    public function init()
    {
        parent::init();
        $this->setAction(WEB_ROOT . '/commenting/comment/add');
        $this->setAttrib('id', 'comment-form');
        $user = current_user();

        //assume registered users are trusted and don't make them play recaptcha
        if (!$user && get_option('recaptcha_public_key') && get_option('recaptcha_private_key')) {
            $this->addElement('captcha', 'captcha',  array(
                'label' => __("Please verify you're a human"),
                'captcha' => array(
                    'captcha' => 'ReCaptcha',
                    'pubkey' => get_option('recaptcha_public_key'),
                    'privkey' => get_option('recaptcha_private_key'),
                    'ssl' => true, //make the connection secure so IE8 doesn't complain. if works, should branch around http: vs https:
                ),
                'decorators' => array(),
            ));
            $this->getElement('captcha')->removeDecorator('ViewHelper');
        }

        $urlOptions = array(
            'label' => __('Website'),
        );
        $emailOptions = array(
            'label' => __('Email (required)'),
            'required' => true,
            'validators' => array(
                array(
                    'validator' => 'EmailAddress',
                ),
            ),
        );
        $nameOptions = array('label' => __('Your name'));

        if ($user) {
            $emailOptions['value'] = $user->email;
            $nameOptions['value'] = $user->name;
        }
        $this->addElement('text', 'author_name', $nameOptions);
        $this->addElement('text', 'author_url', $urlOptions);
        $this->addElement('text', 'author_email', $emailOptions);
        $this->addElement('textarea', 'body', array(
            'label' => __('Comment'),
            'description' => __("Allowed tags:") . " &lt;p&gt;, &lt;a&gt;, &lt;em&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;",
            'id' => 'comment-form-body',
            'required' => true,
            'filters' => array(
                array(
                    'StripTags',
                    array(
                        'allowTags' => array('p', 'span', 'em', 'strong', 'a','ul','ol','li'),
                        'allowAttribs' => array('style', 'href'),
                    ),
                ),
            ),
        ));

        // The legal agreement is checked by default for logged users.
        if (get_option('commenting_legal_text')) {
            $this->addElement('checkbox', 'commenting_legal_text', array(
                'description' => get_option('commenting_legal_text'),
                'value' => (boolean) $user,
                'required' => true,
                'uncheckedValue' => '',
                'checkedValue' => 'checked',
                'validators' => array(
                    array('notEmpty', true, array(
                        'messages' => array(
                            'isEmpty' => __('You must agree to the terms and conditions.'),
                        ),
                    )),
                ),
            ));
        }

        // The simple antispam for anonymous people.
        if (get_option('commenting_antispam') && empty($user)) {
            if ($this->_antispam) {
                $a = array_shift($this->_antispam);
                $b = array_shift($this->_antispam);
            }
            else {
                // Return only one digit.
                $a = mt_rand(0, 6);
                $b = mt_rand(1, 3);
            }
            $result = (string) ($a + $b);

            $question = __('How much is %d plus %d?', $a, $b);
            // Use the name "address" for spam.
            $this->addElement('hidden', 'address_a', array('value' => $a, 'decorators' => array('ViewHelper')));
            $this->addElement('hidden', 'address_b', array('value' => $b, 'decorators' => array('ViewHelper')));
            $this->addElement('text', 'address', array(
                'label' => '',
                'description' => __('To prove you are not a robot, answer this question: %s', $question),
                'required' => true,
                'validators' => array(
                    array('notEmpty', true, array(
                        'messages' => array(
                            'isEmpty' => __('You must answer the antispam question.'),
                        ),
                    )),
                    array('identical', false, array(
                        'token' => $result,
                        'messages' => array(
                            'notSame' => __('Check your anwser to the antispam question.'),
                        ),
                    )),
                ),
            ));
        }

        // A simple honey pot for spam bots.
        if (get_option('commenting_honeypot') && empty($user)) {
            $this->addElement('hidden', 'city', array('value' => '', 'decorators' => array('ViewHelper')));
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $params = $request->getParams();

        $record_id = $this->_getRecordId($params);
        $record_type = $this->_getRecordType($params);

        $this->addElement('hidden', 'record_id', array('value' => $record_id, 'decorators' => array('ViewHelper')));
        $this->addElement('hidden', 'path', array('value' => $request->getPathInfo(), 'decorators' => array('ViewHelper')));
        if (isset($params['module'])) {
            $this->addElement('hidden', 'module', array('value' => $params['module'], 'decorators' => array('ViewHelper')));
        }
        $this->addElement('hidden', 'record_type', array('value' => $record_type, 'decorators' => array('ViewHelper')));
        $this->addElement('hidden', 'parent_comment_id', array('id' => 'parent-id', 'value' => null, 'decorators' => array('ViewHelper')));
        fire_plugin_hook('commenting_form', array('comment_form' => $this));
        $this->addElement('submit', 'submit', array('label' => __('Submit')));
    }

    /**
     * Helper to get record id from request params.
     *
     * @see plugins/Commenting/views/helpers/Comments.php
     *
     * @todo To be merged.
     */
    private function _getRecordId($params)
    {
        if (!empty($this->_record)) {
            return $this->_record->id;
        }

        if (isset($params['module'])
                && $params['module'] == 'commenting'
                && $params['controller'] == 'comment'
                && $params['action'] == 'add'
            ) {
            return $params['record_id'];
        }

//TODO: update exhibit-builder handling for 2.0
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
//TODO: check the ifs for an exhibit showing an item
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
     * @see plugins/Commenting/views/helpers/Comments.php
     *
     * @todo To be merged.
     */
    private function _getRecordType($params)
    {
        if (!empty($this->_record)) {
            return get_class($this->_record);
        }

        if (isset($params['module'])
                && $params['module'] == 'commenting'
                && $params['controller'] == 'comment'
                && $params['action'] == 'add'
            ) {
            return $params['record_type'];
        }

//@TODO: update exhibit-builder handling for 2.0
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

    /**
     * Override wrapper classes for simplicity and to guarantee unique
     * selectors for applying cross-theme styles.
     */
    public function getDefaultElementDecorators()
    {
        return array(
            array('Description', array('tag' => 'p', 'class' => 'commenting-explanation', 'escape'=>false)),
            'ViewHelper',
            array('Errors', array('class' => 'error')),
            'Label',
            array(array('FieldTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'commenting-field'))
        );
    }
}
