
<?php
function flatten_text($text) {
    return preg_replace('/\s+/', ' ', $text);
}
?>