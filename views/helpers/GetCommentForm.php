<?php
class Commenting_View_Helper_GetCommentForm extends Zend_View_Helper_Abstract
{
    public function getCommentForm($record = null)
    {
        if ((get_option('commenting_allow_public') == 1) || is_allowed('Commenting_Comment', 'add')) {
            require_once dirname(dirname(dirname(__FILE__))) . '/forms/CommentForm.php';
            $commentSession = new Zend_Session_Namespace('commenting');
            // First form: prepare values for simple antispam.
            if (!$commentSession->post) {
                // Return only one digit.
                $a = mt_rand(0, 6);
                $b = mt_rand(1, 3);
                $form = new Commenting_CommentForm($record, array($a, $b));
            }
            // There is a post, so reset antispam.
            else {
                $post = unserialize($commentSession->post);
                if (empty($post['address'])) {
                    $a = mt_rand(0, 6);
                    $b = mt_rand(1, 3);
                }
                else {
                    $a = $post['address_a'];
                    $b = $post['address_b'];
                }
                $form = new Commenting_CommentForm($record, array($a, $b));
                $form->isValid($post);
                unset($commentSession->post);
            }
            return $form;
        }
    }
}
