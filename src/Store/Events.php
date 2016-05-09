<?php

namespace As3\Modlr\Store;

use As3\Modlr\Models\Model;

/**
 * Store event name constants.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Events
{
    const postLoad   = 'postLoad';

    const preCommit  = 'preCommit';
    const postCommit = 'postCommit';

    const preCreate  = 'preCreate';
    const postCreate = 'postCreate';

    const preUpdate  = 'preUpdate';
    const postUpdate = 'postUpdate';

    const preDelete  = 'preDelete';
    const postDelete = 'postDelete';

    const preQuery   = 'preQuery';
}
