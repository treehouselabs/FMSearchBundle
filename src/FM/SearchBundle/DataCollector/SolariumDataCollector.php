<?php

namespace FM\SearchBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

use Solarium\Core\Plugin\Plugin;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\PreExecuteRequest;
use Solarium\Core\Event\PostExecuteRequest;

class SolariumDataCollector extends Plugin implements DataCollectorInterface
{
    private $queries = array();
    private $times = array();

    /**
     * Registers event listeners
     */
    protected function initPluginType()
    {
        $dispatcher = $this->client->getEventDispatcher();
        $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'));
        $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'));
    }

    public function preExecuteRequest(PreExecuteRequest $event)
    {
        $id = spl_object_hash($event->getRequest());
        $this->times[$id][] = microtime(true);
    }

    public function postExecuteRequest(PostExecuteRequest $event)
    {
        $id = spl_object_hash($event->getRequest());
        $this->times[$id][] = microtime(true);

        $duration = null;
        if (sizeof($this->times[$id]) === 2) {
            $duration = max($this->times[$id]) - min($this->times[$id]);
        }

        $this->queries[] = array(
            'request' => $event->getRequest(),
            'response' => $event->getResponse(),
            'duration' => $duration
        );
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $time = 0;

        foreach ($this->queries as $query) {
            $time += $query['duration'];
        }

        $this->data = array(
            'queries'     => $this->queries,
            'total_time'  => $time,
        );
    }

    public function getQueries()
    {
        return array_key_exists('queries', $this->data) ? $this->data['queries'] : array();
    }

    public function getQueryCount()
    {
        return count($this->getQueries());
    }

    public function getTotalTime()
    {
        return array_key_exists('total_time', $this->data) ? $this->data['total_time'] : 0;
    }

    public function getName()
    {
        return 'solr';
    }
}
