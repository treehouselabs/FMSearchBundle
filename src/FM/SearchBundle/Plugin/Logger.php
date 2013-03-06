<?php

namespace FM\SearchBundle\Plugin;

use Solarium\Core\Plugin\Plugin;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\PreExecuteRequest;
use Solarium\Core\Event\PostExecuteRequest;

class Logger extends Plugin
{
    private $logger;

    public function setLogger(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    protected function initPluginType()
    {
        $dispatcher = $this->client->getEventDispatcher();
        $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'));
        $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'));
    }

    public function preExecuteRequest(PreExecuteRequest $event)
    {
        $this->logger->info((string) $event->getRequest());
    }

    public function postExecuteRequest(PostExecuteRequest $event)
    {
    }
}
