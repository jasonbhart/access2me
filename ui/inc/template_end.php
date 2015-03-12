<?php
/**
 * template_end.php
 *
 * Author: pixelcave
 *
 * The last block of code used in every page of the template
 *
 * We put it in a separate file for consistency. The reason we
 * separated template_scripts.php and template_end.php is for enabling us
 * put between them extra javascript code needed only in specific pages
 *
 */
?>

    <script type="text/javascript">
        ;(function() {
            var messages = <?php echo json_encode(\Access2Me\Helper\Template::getFlashMessages()); ?>;
            for (var i=0; i<messages.length; i++)
                App.flashMessages.add(messages[i].message, messages[i].type);
        })();
    </script>

    </body>
</html>