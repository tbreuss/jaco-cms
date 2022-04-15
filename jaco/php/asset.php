<?php

namespace jaco\asset;

function inject_javascript(string $html, string $js): string
{
    $injected = file_get_contents('js/' . $js);
    $html = str_replace('</body>', "<script>$injected</script></body>", $html);
    return $html;
}
