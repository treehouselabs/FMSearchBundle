<?php

namespace FM\SearchBundle\Event;

final class SearchEvents
{
    const PRE_PERSIST     = 'pre_persist';
    const POST_PERSIST    = 'post_persist';
    const HYDRATE         = 'hydrate';
    const PRE_SET_FIELDS  = 'pre_set_fields';
    const POST_SET_FIELDS = 'post_set_fields';
}
