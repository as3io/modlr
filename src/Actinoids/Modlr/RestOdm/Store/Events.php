<?php

namespace Actinoids\Modlr\RestOdm\Store;

use Actinoids\Modlr\RestOdm\Models\Model;

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
}
