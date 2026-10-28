<?php

  namespace HTML;

  /**
   * The rules for HTML elements, from the w3schools element and attribute references
   *
   * Generated from https://www.w3schools.com/tags/ (each tag page, plus ref_attributes.asp and ref_eventattributes.asp).
   * Additions from the HTML standard that w3schools leaves out: the void elements most tag pages don't mention, the
   * WAI-ARIA role and aria-* attributes Bootstrap needs, frame names for target, and some link rel values.
   *
   * Attribute values are 'bool' (present or not), 'number', 'pixels', a list of keywords ('*' means other text is
   * allowed too), or a description of free text.
   *
   * @see https://www.w3schools.com/tags/default.asp
   */
  final class Spec
  {
    const string BOOL = 'bool';

    // Global attributes, allowed on every element (as are data-* and aria-*)
    const array GLOBAL = [
      'accesskey' => 'text',
      'class' => 'text',
      'contenteditable' => ['true', 'false', 'plaintext-only'],
      'dir' => ['ltr', 'rtl', 'auto'],
      'draggable' => ['true', 'false', 'auto'],
      'enterkeyhint' => ['enter', 'done', 'go', 'next', 'previous', 'search', 'send'],
      'hidden' => 'bool',
      'id' => 'text',
      'inert' => 'bool',
      'inputmode' => ['none', 'text', 'decimal', 'numeric', 'tel', 'search', 'email', 'url'],
      'lang' => 'text',
      'popover' => ['auto', 'manual', '*'],
      'spellcheck' => ['true', 'false'],
      'style' => 'text',
      'tabindex' => 'number',
      'title' => 'text',
      'translate' => ['yes', 'no'],
      'role' => 'text'
    ];

    // Attributes holding a space separated list, each value is checked
    const array TOKEN_LISTS = ['rel', 'sandbox'];

    // Per element: void (no content or end tag), attributes, event attributes, parents it must be placed in,
    // parents it may not be placed in, whether it must be the first child, and attributes it must have
    const array ELEMENTS = [
      'a' => [
        'void' => false,
        'attributes' => [
          'download' => 'filename',
          'href' => 'url',
          'hreflang' => 'language code',
          'media' => 'media query',
          'ping' => 'list of urls',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin-when-cross-origin', 'unsafe-url'],
          'rel' => ['alternate', 'author', 'bookmark', 'external', 'help', 'license', 'next', 'nofollow', 'noreferrer', 'noopener', 'prev', 'search', 'tag'],
          'target' => ['_blank', '_parent', '_self', '_top', '*'],
          'type' => 'media type'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'abbr' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'address' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'area' => [
        'void' => true,
        'attributes' => [
          'alt' => 'text',
          'coords' => 'coordinates',
          'download' => 'filename',
          'href' => 'url',
          'hreflang' => 'language code',
          'media' => 'media query',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin-when-cross-origin', 'unsafe-url'],
          'rel' => ['alternate', 'author', 'bookmark', 'help', 'license', 'next', 'nofollow', 'noreferrer', 'prefetch', 'prev', 'search', 'tag'],
          'shape' => ['default', 'rect', 'circle', 'poly'],
          'target' => ['_blank', '_parent', '_self', '_top', '*'],
          'type' => 'media type'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['map'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'article' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'aside' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'audio' => [
        'void' => false,
        'attributes' => [
          'autoplay' => 'bool',
          'controls' => 'bool',
          'loop' => 'bool',
          'muted' => 'bool',
          'preload' => ['auto', 'metadata', 'none'],
          'src' => 'url'
        ],
        'events' => ['onabort', 'onblur', 'oncanplay', 'oncanplaythrough', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onpause', 'onplay', 'onplaying', 'onprogress', 'onratechange', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onstalled', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'b' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'base' => [
        'void' => true,
        'attributes' => [
          'href' => 'url',
          'target' => ['_blank', '_parent', '_self', '_top', '*']
        ],
        'events' => [],
        'parents' => ['head'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'bdi' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'bdo' => [
        'void' => false,
        'attributes' => [
          'dir' => ['ltr', 'rtl']
        ],
        'events' => [],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'blockquote' => [
        'void' => false,
        'attributes' => [
          'cite' => 'url'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'body' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onafterprint', 'onbeforeprint', 'onbeforeunload', 'onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onfocus', 'onhashchange', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onoffline', 'ononline', 'onpagehide', 'onpageshow', 'onpaste', 'onpopstate', 'onresize', 'onscroll', 'onselect', 'onstorage', 'onunload', 'onwheel'],
        'parents' => ['html'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'br' => [
        'void' => true,
        'attributes' => [

        ],
        'events' => [],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'button' => [
        'void' => false,
        'attributes' => [
          'autofocus' => 'bool',
          'command' => ['show-modal', 'close', 'request-close', 'show-popover', 'hide-popover', 'toggle-popover', '*'],
          'commandfor' => 'text',
          'disabled' => 'bool',
          'form' => 'id',
          'formaction' => 'url',
          'formenctype' => ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'],
          'formmethod' => ['get', 'post'],
          'formnovalidate' => 'bool',
          'formtarget' => ['_blank', '_self', '_parent', '_top', '*'],
          'name' => 'text',
          'popovertarget' => 'id',
          'popovertargetaction' => ['hide', 'show', 'toggle'],
          'type' => ['button', 'reset', 'submit'],
          'value' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'canvas' => [
        'void' => false,
        'attributes' => [
          'height' => 'pixels',
          'width' => 'pixels'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'caption' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table'],
        'notin' => null,
        'first' => true,
        'required' => []
      ],
      'cite' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'code' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'col' => [
        'void' => true,
        'attributes' => [
          'span' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['colgroup'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'colgroup' => [
        'void' => false,
        'attributes' => [
          'span' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'data' => [
        'void' => false,
        'attributes' => [
          'value' => 'machine-readable format'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'datalist' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'dd' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['dl'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'del' => [
        'void' => false,
        'attributes' => [
          'cite' => 'url',
          'datetime' => 'datetime'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'details' => [
        'void' => false,
        'attributes' => [
          'name' => 'text',
          'open' => 'bool'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'ontoggle', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'dfn' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'dialog' => [
        'void' => false,
        'attributes' => [
          'open' => 'bool'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'div' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'dl' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'dt' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['dl'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'em' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'embed' => [
        'void' => true,
        'attributes' => [
          'height' => 'pixels',
          'src' => 'url',
          'type' => 'media type',
          'width' => 'pixels'
        ],
        'events' => ['onabort', 'onblur', 'oncanplay', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'fieldset' => [
        'void' => false,
        'attributes' => [
          'disabled' => 'bool',
          'form' => 'id',
          'name' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'figcaption' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['figure'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'figure' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'footer' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'form' => [
        'void' => false,
        'attributes' => [
          'accept-charset' => 'character set',
          'action' => 'url',
          'autocomplete' => ['on', 'off'],
          'enctype' => ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'],
          'method' => ['dialog', 'get', 'post'],
          'name' => 'text',
          'novalidate' => 'bool',
          'rel' => ['external', 'help', 'license', 'next', 'nofollow', 'noopener', 'noreferrer', 'opener', 'prev', 'search'],
          'target' => ['_blank', '_self', '_parent', '_top', '*']
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onreset', 'onscroll', 'onselect', 'onsubmit', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h1' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h2' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h3' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h4' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h5' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'h6' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'head' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => [],
        'parents' => ['html'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'header' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => ['footer', 'address', 'header'],
        'first' => false,
        'required' => []
      ],
      'hgroup' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'hr' => [
        'void' => true,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'html' => [
        'void' => false,
        'attributes' => [
          'xmlns' => ['http://www.w3.org/1999/xhtml']
        ],
        'events' => [],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'i' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'iframe' => [
        'void' => false,
        'attributes' => [
          'allow' => 'text',
          'allowfullscreen' => ['true', 'false'],
          'height' => 'pixels',
          'loading' => ['eager', 'lazy'],
          'name' => 'text',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin-when-cross-origin', 'unsafe-url'],
          'sandbox' => ['allow-forms', 'allow-pointer-lock', 'allow-popups', 'allow-same-origin', 'allow-scripts', 'allow-top-navigation'],
          'src' => 'url',
          'srcdoc' => 'html code',
          'width' => 'pixels'
        ],
        'events' => ['onload'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'img' => [
        'void' => true,
        'attributes' => [
          'alt' => 'text',
          'crossorigin' => ['anonymous', 'use-credentials'],
          'height' => 'pixels',
          'ismap' => 'bool',
          'loading' => ['eager', 'lazy'],
          'longdesc' => 'url',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'unsafe-url'],
          'sizes' => 'sizes',
          'src' => 'url',
          'srcset' => 'url-list',
          'usemap' => '#mapname',
          'width' => 'pixels'
        ],
        'events' => ['onabort', 'onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => ['src', 'alt']
      ],
      'input' => [
        'void' => true,
        'attributes' => [
          'accept' => ['audio/*', 'video/*', 'image/*', '*'],
          'alt' => 'text',
          'autocomplete' => ['on', 'off'],
          'autofocus' => 'bool',
          'checked' => 'bool',
          'dirname' => 'inputname.dir',
          'disabled' => 'bool',
          'form' => 'id',
          'formaction' => 'url',
          'formenctype' => ['application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'],
          'formmethod' => ['get', 'post'],
          'formnovalidate' => 'bool',
          'formtarget' => ['_blank', '_self', '_parent', '_top', '*'],
          'height' => 'pixels',
          'list' => 'id',
          'max' => 'number or date',
          'maxlength' => 'number',
          'min' => 'number or date',
          'minlength' => 'number',
          'multiple' => 'bool',
          'name' => 'text',
          'pattern' => 'regexp',
          'placeholder' => 'text',
          'popovertarget' => 'id',
          'popovertargetaction' => ['hide', 'show', 'toggle'],
          'readonly' => 'bool',
          'required' => 'bool',
          'size' => 'number',
          'src' => 'url',
          'step' => ['any', '*'],
          'type' => ['button', 'checkbox', 'color', 'date', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month', 'number', 'password', 'radio', 'range', 'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week'],
          'value' => 'text',
          'width' => 'pixels'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onsearch', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'ins' => [
        'void' => false,
        'attributes' => [
          'cite' => 'url',
          'datetime' => 'datetime'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'kbd' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'label' => [
        'void' => false,
        'attributes' => [
          'for' => 'id',
          'form' => 'id'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'legend' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['fieldset'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'li' => [
        'void' => false,
        'attributes' => [
          'value' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['ol', 'ul', 'menu'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'link' => [
        'void' => true,
        'attributes' => [
          'crossorigin' => ['anonymous', 'use-credentials'],
          'href' => 'url',
          'hreflang' => 'language code',
          'media' => 'media query',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'unsafe-url'],
          'rel' => ['alternate', 'author', 'dns-prefetch', 'help', 'icon', 'license', 'next', 'pingback', 'preconnect', 'prefetch', 'preload', 'prerender', 'prev', 'search', 'stylesheet', 'manifest', 'apple-touch-icon', 'canonical', 'modulepreload'],
          'sizes' => ['any', '*'],
          'title' => 'text',
          'type' => 'media type'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'main' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => ['article', 'aside', 'footer', 'header', 'nav'],
        'first' => false,
        'required' => []
      ],
      'map' => [
        'void' => false,
        'attributes' => [
          'name' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => ['name']
      ],
      'mark' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'menu' => [
        'void' => false,
        'attributes' => [
          'type' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'meta' => [
        'void' => true,
        'attributes' => [
          'charset' => 'character set',
          'content' => 'text',
          'http-equiv' => ['content-security-policy', 'content-type', 'default-style', 'refresh'],
          'name' => ['application-name', 'author', 'description', 'viewport', '*']
        ],
        'events' => [],
        'parents' => ['head'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'meter' => [
        'void' => false,
        'attributes' => [
          'form' => 'id',
          'high' => 'number',
          'low' => 'number',
          'max' => 'number',
          'min' => 'number',
          'optimum' => 'number',
          'value' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'nav' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'noscript' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'object' => [
        'void' => false,
        'attributes' => [
          'data' => 'url',
          'form' => 'id',
          'height' => 'pixels',
          'name' => 'text',
          'type' => 'media type',
          'usemap' => '#mapname',
          'width' => 'pixels'
        ],
        'events' => ['onabort', 'onblur', 'oncanplay', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'ol' => [
        'void' => false,
        'attributes' => [
          'reversed' => 'bool',
          'start' => 'number',
          'type' => ['1', 'A', 'a', 'I', 'i']
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'optgroup' => [
        'void' => false,
        'attributes' => [
          'disabled' => 'bool',
          'label' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['select'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'option' => [
        'void' => false,
        'attributes' => [
          'disabled' => 'bool',
          'label' => 'text',
          'selected' => 'bool',
          'value' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['select', 'optgroup', 'datalist'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'output' => [
        'void' => false,
        'attributes' => [
          'for' => 'id',
          'form' => 'id',
          'name' => 'text'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'p' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'param' => [
        'void' => true,
        'attributes' => [
          'name' => 'text',
          'value' => 'text'
        ],
        'events' => [],
        'parents' => ['object'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'picture' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'pre' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'progress' => [
        'void' => false,
        'attributes' => [
          'max' => 'number',
          'value' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'q' => [
        'void' => false,
        'attributes' => [
          'cite' => 'url'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'rp' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['ruby'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'rt' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['ruby'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'ruby' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      's' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'samp' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'script' => [
        'void' => false,
        'attributes' => [
          'async' => 'bool',
          'charset' => 'text',
          'crossorigin' => ['anonymous', 'use-credentials'],
          'defer' => 'bool',
          'integrity' => 'filehash',
          'nomodule' => 'bool',
          'referrerpolicy' => ['no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'],
          'src' => 'url',
          'type' => 'scripttype'
        ],
        'events' => ['onerror', 'onload'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'search' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'section' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'select' => [
        'void' => false,
        'attributes' => [
          'autofocus' => 'bool',
          'disabled' => 'bool',
          'form' => 'id',
          'multiple' => 'bool',
          'name' => 'text',
          'required' => 'bool',
          'size' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'small' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'source' => [
        'void' => true,
        'attributes' => [
          'media' => 'media query',
          'sizes' => 'text',
          'src' => 'url',
          'srcset' => 'url',
          'type' => 'mime-type'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['audio', 'video', 'picture'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'span' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'strong' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'style' => [
        'void' => false,
        'attributes' => [
          'media' => 'media query',
          'type' => ['text/css']
        ],
        'events' => ['onerror', 'onload'],
        'parents' => ['head'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'sub' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'summary' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['details'],
        'notin' => null,
        'first' => true,
        'required' => []
      ],
      'sup' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'svg' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'table' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'tbody' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'td' => [
        'void' => false,
        'attributes' => [
          'colspan' => 'number',
          'headers' => 'id',
          'rowspan' => 'number'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['tr'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'template' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'textarea' => [
        'void' => false,
        'attributes' => [
          'autofocus' => 'bool',
          'cols' => 'number',
          'dirname' => 'textareaname.dir',
          'disabled' => 'bool',
          'form' => 'id',
          'maxlength' => 'number',
          'name' => 'text',
          'placeholder' => 'text',
          'readonly' => 'bool',
          'required' => 'bool',
          'rows' => 'number',
          'wrap' => ['hard', 'soft']
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'tfoot' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'th' => [
        'void' => false,
        'attributes' => [
          'abbr' => 'text',
          'colspan' => 'number',
          'headers' => 'id',
          'rowspan' => 'number',
          'scope' => ['col', 'colgroup', 'row', 'rowgroup']
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['tr'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'thead' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'time' => [
        'void' => false,
        'attributes' => [
          'datetime' => 'datetime'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'title' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => [],
        'parents' => ['head'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'tr' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['table', 'thead', 'tbody', 'tfoot'],
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'track' => [
        'void' => true,
        'attributes' => [
          'default' => 'bool',
          'kind' => ['captions', 'chapters', 'descriptions', 'metadata', 'subtitles'],
          'label' => 'text',
          'src' => 'url',
          'srclang' => 'language code'
        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncuechange', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => ['audio', 'video'],
        'notin' => null,
        'first' => false,
        'required' => ['src']
      ],
      'u' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'ul' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'var' => [
        'void' => false,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'video' => [
        'void' => false,
        'attributes' => [
          'autoplay' => 'bool',
          'controls' => 'bool',
          'height' => 'pixels',
          'loop' => 'bool',
          'muted' => 'bool',
          'poster' => 'url',
          'preload' => ['auto', 'metadata', 'none'],
          'src' => 'url',
          'width' => 'pixels'
        ],
        'events' => ['onabort', 'onblur', 'oncanplay', 'oncanplaythrough', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onpause', 'onplay', 'onplaying', 'onprogress', 'onratechange', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onstalled', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ],
      'wbr' => [
        'void' => true,
        'attributes' => [

        ],
        'events' => ['onblur', 'onchange', 'onclick', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpaste', 'onscroll', 'onselect', 'onwheel'],
        'parents' => null,
        'notin' => null,
        'first' => false,
        'required' => []
      ]
    ];

    /**
     * @throws \InvalidArgumentException for an unknown element
     */
    public static function Element(string $Tag): array
    {
      return self::ELEMENTS[$Tag] ?? throw new \InvalidArgumentException("<$Tag> is not an HTML element");
    }

    /**
     * The value rule for an attribute on an element, null when the element doesn't support it
     */
    public static function Attribute(string $Tag, string $Name): string|array|null
    {
      $Element = self::Element($Tag);
      if (str_starts_with($Name, 'data-') || str_starts_with($Name, 'aria-') || in_array($Name, $Element['events'], true))
      {
        return 'text';
      }
      return $Element['attributes'][$Name] ?? self::GLOBAL[$Name] ?? null;
    }

    /**
     * @throws \InvalidArgumentException when the element doesn't support the attribute or value
     */
    public static function CheckAttribute(string $Tag, string $Name, ?string $Value): void
    {
      $Rule = self::Attribute($Tag, $Name);
      if ($Rule === null)
      {
        throw new \InvalidArgumentException("<$Tag> has no attribute '$Name'");
      }
      if ($Value === null)
      {
        return;
      }
      $Valid = match (true)
      {
        $Rule === self::BOOL => $Value === '' || strcasecmp($Value, $Name) === 0,
        $Rule === 'number' => is_numeric($Value),
        $Rule === 'pixels' => ctype_digit($Value),
        is_array($Rule) => in_array('*', $Rule, true) || self::InList($Name, $Value, $Rule),
        default => true,
      };
      if (!$Valid)
      {
        $Allowed = is_array($Rule) ? implode(', ', $Rule) : $Rule;
        throw new \InvalidArgumentException("<$Tag> doesn't allow $Name=\"$Value\" (allowed: $Allowed)");
      }
    }

    private static function InList(string $Name, string $Value, array $Allowed): bool
    {
      // Keywords are case-insensitive (http-equiv="Content-Type")
      $Values = array_map('strtolower', in_array($Name, self::TOKEN_LISTS, true) ? preg_split('/\s+/', trim($Value)) : [$Value]);
      return empty(array_diff($Values, array_map('strtolower', $Allowed)));
    }

    /**
     * @throws \InvalidArgumentException when the element may not be placed in the parent
     */
    public static function CheckPlacement(string $Tag, string $ParentTag): void
    {
      // Text, raw HTML and fragments (an AJAX response's body is a text element) have no rules
      if ($Tag === '' || $ParentTag === '')
      {
        return;
      }
      $Element = self::Element($Tag);
      if ($Element['parents'] !== null && !in_array($ParentTag, $Element['parents'], true))
      {
        $Parents = '<' . implode('>, <', $Element['parents']) . '>';
        throw new \InvalidArgumentException("<$Tag> must be placed in $Parents, not in <$ParentTag>");
      }
      if ($Element['notin'] !== null && in_array($ParentTag, $Element['notin'], true))
      {
        throw new \InvalidArgumentException("<$Tag> may not be placed in <$ParentTag>");
      }
    }
  }
