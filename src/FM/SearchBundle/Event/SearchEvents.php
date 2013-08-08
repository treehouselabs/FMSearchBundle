<?php

namespace FM\SearchBundle\Event;

final class SearchEvents
{
    const PRE_PERSIST     = 'pre_persist';
    const POST_PERSIST    = 'post_persist';
    const HYDRATE         = 'hydrate';
    const PRE_SET_FIELDS  = 'pre_set_fields';
    const POST_SET_FIELDS = 'post_set_fields';

    /**
     * Dispatched a the begining of an update operation, passes the document that will be updated
     */
    const PRE_UPDATE     = 'pre_update';

    /**
     * Dispatched a the end of an update operation, passes the document that has been updated
     */
    const POST_UPDATE    = 'post_update';

    /**
     * Dispatched a the begining of a commit operation, passes the schemas that will be committed
     */
    const PRE_COMMIT     = 'pre_commit';

    /**
     * Dispatched a the end of a commit operation, passes the schemas that have been committed
     */
    const POST_COMMIT    = 'post_commit';
}
