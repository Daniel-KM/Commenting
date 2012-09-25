<?php

queue_css_file('commenting');
queue_js_file('commenting');
queue_js_string("Commenting.pluginRoot = '" . WEB_ROOT . "/admin/commenting/comment/'");


echo head(array('title' => 'Comments', 'bodyclass' => 'primary'));

?>
<div id='primary'>

    <?php echo flash(); ?>
    <?php if(!Omeka_Captcha::isConfigured()): ?>
    <p class="alert">You have not entered your <a href="http://recaptcha.net/">reCAPTCHA</a> API keys under <a href="<?php echo url('security#recaptcha_public_key'); ?>">security settings</a>. We recommend adding these keys, or the commenting form will be vulnerable to spam.</p>
    <?php endif; ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <div id="browse-meta" class="group">
        <div id="browse-meta-lists">
            <ul class="navigation">
                <li><strong>Quick Filter</strong></li>
            <?php
                echo nav(array(
                    'All' => url('commenting/comment/browse'),
                    'Approved' => url('commenting/comment/browse?approved=1'),
                    'Needs Approval' => url('commenting/comment/browse?approved=0')
                ));
            ?>
            </ul>
        </div>
    </div>
<h1>Comments</h1>
<?php if(has_permission('Commenting_Comment', 'updateapproved') ) :?>
<div id='comment-batch-actions'><input id='batch-select' type='checkbox' /> Select All | With Selected:
<ul class='comment-batch-actions'>
<li onclick="Commenting.batchApprove()">Approve</li>
<li onclick="Commenting.batchUnapprove()">Unapprove</li>
<?php if(get_option('commenting_wpapi_key') != ''): ?>
<li onclick="Commenting.batchReportSpam()">Report Spam</li>
<li onclick="Commenting.batchReportHam()">Report Ham</li>
<?php endif; ?>
<li onclick="Commenting.batchFlag()">Flag Inappropriate</li>
<li onclick="Commenting.batchRemoveFlag()">Remove Flag</li>
<li onclick="Commenting.batchDelete()">Delete</li>
</ul>
</div>
<?php endif; ?>

<?php echo commenting_render_comments($comments, true); ?>
<div class="pagination"><?php echo pagination_links(); ?></div>
</div>

<?php foot(); ?>