<?php

namespace MailerLite\LaravelElasticsearch;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * Class Facade.
 */
class Facade extends BaseFacade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'elasticsearch';
    }
}
