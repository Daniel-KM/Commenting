<style type="text/css">
.input-block ul {
    list-style: none outside none;
}
</style>
<?php echo js_tag('vendor/tiny_mce/tiny_mce'); ?>
<script type="text/javascript">
jQuery(window).load(function () {
  Omeka.wysiwyg({
    mode: 'specific_textareas',
    editor_selector: 'html-editor'
  });
});
</script>
<?php js_tag('commenting-config-form'); ?>
<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_pages',
            __('Pages where to add comments')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __('The type of pages where commenting is enabled.'
        . ' Note that you need to add the hook "commenting_comments" in corresponding pages of your theme too.'); ?></p>
        <div class="input-block">
            <ul>
            <?php
                $commentingPages = unserialize(get_option('commenting_pages'));

                $pages = array(
                    'collections/show' => __('Collections'),
                    'items/show' => __('Items'),
                    'files/show' => __('Files'),
                );
                if (plugin_is_active('SimplePages')) {
                    $pages['page/show'] = __('Simple pages');
                }
                if (plugin_is_active('ExhibitBuilder')) {
                    $pages['exhibits/summary'] = __('Exhibits summary');
                    $pages['exhibits/show'] = __('Exhibits pages');
                }

                foreach ($pages as $page => $label) {
                    echo '<li>';
                    echo $this->formCheckbox('commenting_pages[]', $page, array(
                        'checked' => in_array($page, $commentingPages ? $commentingPages : array()) ? 'checked' : '',
                    ));
                    echo ' ' . $label;
                    echo '</li>';
                }
            ?>
            </ul>
        </div>
    </div>
</div>

    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_threaded',
                __('Use Threaded Comments?')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __("If checked, replies will be displayed indented below the comment."); ?></p>
            <div class="input-block">
                <?php echo $this->formCheckbox('commenting_threaded', null, array(
                    'checked' => (bool) get_option('commenting_threaded') ? 'checked' : '',
                )); ?>
            </div>
        </div>
    </div>

    <div class='field'>
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_comments_label',
                __('Text for comments label')); ?>
        </div>
        <div class='inputs five columns omega'>
            <p class='explanation'><?php echo __("A label instead of 'Comments' to use. Leave empty to use 'Comments'."); ?></p>
            <div class='input-block'>
                <?php echo $this->formText('commenting_comments_label', get_option('commenting_comments_label')); ?>
            </div>
        </div>
    </div>

    <div class='field'>
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_legal_text',
                __('Legal agreement')); ?>
        </div>
        <div class='inputs five columns omega'>
            <div class='input-block'>
                <?php echo $this->formTextarea(
                    'commenting_legal_text',
                    get_option('commenting_legal_text'),
                    array(
                        'rows' => 5,
                        'cols' => 60,
                        'class' => array('textinput', 'html-editor'),
                     )
                ); ?>
                <p class="explanation">
                    <?php echo __('This text will be shown beside the legal checkbox.'
                        . " Let empty if you don't want to use a legal agreement."); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_allow_public',
                __('Allow public commenting?')); ?>
        </div>
        <div class="inputs five columns omega">
                <p class="explanation"><?php echo __("Allows everyone, including non-registered users to comment. Using this without Akismet is strongly discouraged."); ?></p>
            <div class="input-block">
                <?php echo $this->formCheckbox('commenting_allow_public', null, array(
                    'checked' => (bool) get_option('commenting_allow_public') ? 'checked' : '',
                )); ?>
            </div>
        </div>
    </div>

<div class='field' id='commenting-moderate-public'>
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_require_public_moderation',
            __('Require moderation for all public comments?')); ?>
    </div>
    <div class='inputs five columns omega'>
        <p class='explanation'><?php echo __("If unchecked, comments will appear immediately."); ?></p>
        <div class="input-block">
            <?php echo $this->formCheckbox('commenting_require_public_moderation', null, array(
                'checked' => (bool) get_option('commenting_require_public_moderation') ? 'checked' : '',
            )); ?>
        </div>
    </div>
</div>

<div class="field" id='moderate-options'>
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_moderate_roles',
            __('User roles that can moderate comments')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("The user roles that are allowed to moderate comments."); ?></p>
        <div class="input-block">
            <ul>
            <?php
                $moderateRoles = unserialize(get_option('commenting_moderate_roles'));
                $userRoles = get_user_roles();
                unset($userRoles['super']);
                foreach ($userRoles as $role => $label) {
                    echo '<li>';
                    echo $this->formCheckbox('commenting_moderate_roles[]', $role, array(
                        'checked' => in_array($role, $moderateRoles) ? 'checked' : '',
                    ));
                    echo ' ' . $label;
                    echo '</li>';
                }
            ?>
            </ul>
        </div>
    </div>
</div>

<div id='non-public-options'>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_comment_roles',
                __('User roles that can comment')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __("Select the roles that can leave comments"); ?></p>
            <div class="input-block">
                <ul>
                <?php
                    $commentRoles = unserialize(get_option('commenting_comment_roles'));
                    echo '<ul>';
                    foreach ($userRoles as $role => $label) {
                        echo '<li>';
                        echo $this->formCheckbox('commenting_comment_roles[]', $role, array(
                            'checked' => in_array($role, $commentRoles) ? 'checked' : '',
                        ));
                        echo ' ' . $label;
                        echo '</li>';
                    }
                ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_reqapp_comment_roles',
                __('User roles that require moderation before publishing')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"><?php echo __("If the role is allowed to moderate comments, that will override the setting here."); ?></p>
            <div class="input-block">
                <ul>
                <?php
                    $reqAppCommentRoles = unserialize(get_option('commenting_reqapp_comment_roles'));
                    foreach ($userRoles as $role => $label) {
                        echo '<li>';
                        echo $this->formCheckbox('commenting_reqapp_comment_roles[]', $role, array(
                            'checked' => in_array($role, $reqAppCommentRoles) ? 'checked' : '',
                        ));
                        echo ' ' . $label;
                        echo '</li>';
                    }
                ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_allow_public_view',
                __('Allow public to view comments?')); ?>
        </div>
        <div class="inputs five columns omega">
            <p class="explanation"></p>
            <div class="input-block">
                <?php echo $this->formCheckbox('commenting_allow_public_view', null, array(
                    'checked' => (bool) get_option('commenting_allow_public_view') ? 'checked' : '',
                )); ?>
            </div>
        </div>
    </div>
</div>

    <div class="field view-options">
        <div class="two columns alpha">
            <?php echo $this->formLabel('commenting_view_roles',
                __('User roles that can view comments')); ?>
        </div>
        <div class="inputs five columns omega">
            <div class="input-block">
                <ul>
                <?php
                    $thisRoles = unserialize(get_option('commenting_view_roles'));
                    if (!$thisRoles) {
                        $thisRoles = array();
                    }
                    foreach ($userRoles as $role=> $label) {
                        echo '<li>';
                        echo $this->formCheckbox('commenting_view_roles[]', $role, array(
                            'checked' => in_array($role, $thisRoles) ? 'checked' : '',
                        ));
                        echo ' ' . $label;
                        echo '</li>';
                    }
                ?>
                </ul>
            </div>
        </div>
    </div>

<?php if (!Omeka_Captcha::isConfigured()): ?>
<p class="alert"><?php echo __("You have not entered your %s API keys under %s. We recommend adding these keys, or the commenting form will be vulnerable to spam.", '<a href="http://recaptcha.net/">reCAPTCHA</a>', "<a href='" . url('settings/edit-security#recaptcha_public_key') . "'>" . __('security settings') . "</a>");?></p>
<?php endif; ?>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_wpapi_key',
            __('WordPress API key for Akismet')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
            <?php echo $this->formText('commenting_wpapi_key', get_option('commenting_wpapi_key'),
                array('size' => 45)
            );?>
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_antispam', __('Simple antispam')); ?>
    </div>
    <div class='inputs five columns omega'>
        <?php echo $this->formCheckbox('commenting_antispam', true, array('checked' => (boolean) get_option('commenting_antispam'))); ?>
        <p class="explanation">
            <?php echo __('If checked, a simple antispam will be added for anonymous people (an addition of two digits).'); ?>
        </p>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $this->formLabel('commenting_honeypot', __('Honey pot')); ?>
    </div>
    <div class='inputs five columns omega'>
        <?php echo $this->formCheckbox('commenting_honeypot', true, array('checked' => (boolean) get_option('commenting_honeypot'))); ?>
        <p class="explanation">
            <?php echo __('If checked, a honey pot will be added for spam bots.'); ?>
        </p>
    </div>
</div>
