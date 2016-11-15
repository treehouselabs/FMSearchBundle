<?php

namespace FM\SearchBundle\Plugin;

use Psr\Log\LoggerInterface;
use Solarium\Core\Plugin\Plugin;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\PreExecuteRequest;
use Solarium\Core\Event\PostExecuteRequest;

class Logger extends Plugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    protected function initPluginType()
    {
        $dispatcher = $this->client->getEventDispatcher();
        $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'));
        $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'));
    }

    /**
     * @param PreExecuteRequest $event
     */
    public function preExecuteRequest(PreExecuteRequest $event)
    {
        $this->logger->info((string) $event->getRequest());
    }

    /**
     * @param PostExecuteRequest $event
     */
    public function postExecuteRequest(PostExecuteRequest $event)
    {
    }
}
