<?php

class Commenting_CommentController extends Omeka_Controller_AbstractActionController
{
    protected $_browseRecordsPerPage = 10;

    /**
     * Controller-wide initialization. Sets the underlying model to use.
     */
    public function init()
    {
        $this->_helper->db->setDefaultModelName('Comment');
    }

    public function browseAction()
    {
        if (!$this->hasParam('sort_field')) {
            $this->setParam('sort_field', 'added');
        }

        if (!$this->hasParam('sort_dir')) {
            $this->setParam('sort_dir', 'd');
        }
        parent::browseAction();
    }

    public function batchDeleteAction()
    {
        $ids = $_POST['ids'];
        foreach ($ids as $id) {
            $record = $this->_helper->db->findById($id);
            $record->delete();
        }
        $response = array('status' => 'ok');
        $this->_helper->json($response);
    }

    public function addAction()
    {
        $post = $this->getRequest()->getPost();
        $destination = $post['path'];
        $module = isset($post['module']) ? Inflector::camelize($post['module']) : '';
        $destArray = array(
            'module' => $module,
            'controller' => strtolower(Inflector::pluralize($post['record_type'])),
            'action' => 'show',
            'id' => $post['record_id']
        );

        $comment = new Comment();
        $user = current_user();
        if ($user) {
            $comment->user_id = $user->id;
        }
        $comment->flagged = 0;

        $form = $this->_getForm();
        $valid = $form->isValid($post);
        if (!$valid) {
            $destination .= "#comment-form";
            $commentSession = new Zend_Session_Namespace('commenting');
            $commentSession->post = serialize($post);
            $this->_helper->redirector->gotoUrl($destination);
        }

        $role = current_user()->role;
        $reqAppCommentRoles = unserialize(get_option('commenting_reqapp_comment_roles'));
        if (empty($reqAppCommentRoles)) $reqAppCommentRoles = array();
        $requiresApproval = in_array($role, $reqAppCommentRoles);
        //via Daniel Lind -- https://groups.google.com/forum/#!topic/omeka-dev/j-tOSAVdxqU
        $reqAppPublicComment = (bool) get_option('commenting_require_public_moderation');
        $requiresApproval = $requiresApproval || (!is_object(current_user()) && $reqAppPublicComment);
        //end Daniel Lind contribution
        if ($requiresApproval) {
            $this->_helper->flashMessenger(__("Your comment is awaiting moderation"), 'success');
        }

        //need getValue to run the filter
        $data = $post;
        $data['body'] = $form->getElement('body')->getValue();
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['approved'] = !$requiresApproval;
        $comment->setArray($data);
        $comment->checkSpam();
        if ($comment->is_spam) {
            $comment->approved = 0;
        }
        $comment->save();

        $this->sendEmailNotifications($comment);

        $destination .= "#comment-" . $comment->id;

        $this->_helper->redirector->gotoUrl($destination);
    }

    public function updateSpamAction()
    {
        $commentIds = $_POST['ids'];
        $spam = $_POST['spam'];
        $table = $this->_helper->db->getTable();
        $wordPressAPIKey = get_option('commenting_wpapi_key');
        $ak = new Zend_Service_Akismet($wordPressAPIKey, WEB_ROOT );
        $response = array('errors' => array());
        foreach ($commentIds as $commentId) {
            $comment = $table->find($commentId);
            $data = $comment->getAkismetData();
            if ($spam) {
                $submitMethod = 'submitSpam';
            } else {
                $submitMethod = 'submitHam';
            }
            try{
                $ak->$submitMethod($data);
                //only save the update if updating to Akismet is successful
                $comment->is_spam = $spam;
                $comment->save();
                $response['status'] = 'ok';
            } catch (Exception $e){
                $response['status'] = 'fail';
                $response['errors'][] = array('id' => $comment->id);
                $response['message'] = $e->getMessage();
                _log($e);
            }
        }
        $this->_helper->json($response);
    }

    public function updateApprovedAction()
    {
        $wordPressAPIKey = get_option('commenting_wpapi_key');
        $commentIds = $_POST['ids'];
        $status = $_POST['approved'];
        $table = $this->_helper->db->getTable();
        if (! $commentIds) {
            return;
        }
        foreach ($commentIds as $commentId) {
            $comment = $table->find($commentId);
            $comment->approved = $status;
            //if approved, it isn't spam
            if (($status == 1) && ($comment->is_spam == 1)) {
                $comment->is_spam = 0;
                $ak = new Zend_Service_Akismet($wordPressAPIKey, WEB_ROOT );
                $data = $comment->getAkismetData();
                try {
                    $ak->submitHam($data);
                    $response = array('status' => 'ok');
                    $comment->save();
                } catch (Exception $e) {
                    _log($e->getMessage());
                    $response = array('status' => 'fail', 'message' => $e->getMessage());
                }

            } else {
                try {
                    $comment->save();
                    $response = array('status' => 'ok');
                } catch(Exception $e) {
                    $response = array('status' => 'fail', 'message' => $e->getMessage());
                    _log($e->getMessage());
                }
            }

        }
        $this->_helper->json($response);
    }


    public function updateFlaggedAction()
    {
        $commentIds = $_POST['ids'];
        $flagged = $_POST['flagged'];

        if ($commentIds) {
            foreach ($commentIds as $id) {
                $comment = $this->_helper->db->getTable('Comment')->find($id);
                $comment->flagged = $flagged;
                $comment->save();
            }
        } else {
            $response = array('status' => 'empty', 'message' => __('No Comments Found'));
        }
        if ($flagged) {
            $action = 'flagged';
        } else {
            $action = 'unflagged';
        }
        $response = array('status' => 'ok', 'action' => $action, 'ids' => $commentIds);
        $this->_helper->json($response);
    }

    public function flagAction()
    {
        $commentId = $_POST['id'];
        $comment = $this->_helper->db->getTable('Comment')->find($commentId);
        $comment->flagged = true;
        $comment->save();
        $this->emailFlagged($comment);
        $response = array('status' => 'ok', 'id' => $commentId, 'action' => 'flagged');
        $this->_helper->json($response);
    }

    public function unflagAction()
    {
        $commentId = $_POST['id'];
        $comment = $this->_helper->db->getTable('Comment')->find($commentId);
        $comment->flagged = 0;
        $comment->save();
        $response = array('status' => 'ok', 'id' => $commentId, 'action' => 'unflagged');
        $this->_helper->json($response);
    }

    private function emailFlagged($comment)
    {
        $mail = new Zend_Mail('UTF-8');
        $mail->addHeader('X-Mailer', 'PHP/' . phpversion());
        $adminEmail = get_option('administrator_email');
        $mail->setFrom($adminEmail, get_option('site_title'));
        $mail->addTo($adminEmail);
        $flagEmail = get_option('commenting_flag_email');
        if ($flagEmail) {
            $mail->addTo($flagEmail);
        }
        $subject = __("A comment on %s has been flagged as inappropriate", get_option('site_title'));
        $body = "<p>" . __("The comment %s has been flagged as inappropriate.", "<blockquote>{$comment->body}</blockquote>" ) . "</p>";
        $body .= "<p>" . __("You can manage the comment %s", "<a href='" . WEB_ROOT ."{$comment->path}'>" . __('here') . "</a>" ) . "</p>";
        $mail->setSubject($subject);
        $mail->setBodyHtml($body);
        try {
            $mail->send();
        } catch(Exception $e) {
            _log($e);
        }
    }

    private function sendEmailNotifications(Comment $comment)
    {
        $recipients = get_option('new_comment_notification_recipients');
        if (strlen($recipients)) {
            // run the whole stuff in background, we don't want delay our visitors
            Zend_Registry::get('bootstrap')->getResource('jobs')
                ->sendLongRunning('Job_CommentNotification', [
                    'id' => $comment->id,
                    'recipients' => (array) explode("\n", $recipients),
                    'webRoot' => rtrim(WEB_ROOT, '/'), // needed for correct URLs from background job to admin/public page
            ]);
        }
    }

    private function _getForm()
    {
        require_once dirname(dirname(__FILE__)) . '/forms/CommentForm.php';
        $post = $this->getRequest()->getPost();
        $a = (integer) empty($post['address_a']) ? 0 : (integer) $post['address_a'];
        $b = (integer) empty($post['address_b']) ? 0 : (integer) $post['address_b'];
        $form = new Commenting_CommentForm(null, array($a, $b));
        return $form;
    }
}
