<?php

namespace jaco\html;

function h(string $tag, $attributes = null, $content = null) : string
{
    return \Spatie\HtmlElement\HtmlElement::render(...func_get_args());
}
