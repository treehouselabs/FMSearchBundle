<?php

namespace FM\SearchBundle\Event\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use FM\SearchBundle\DocumentManager;

class IndexListener
{
    private $manager;
    private $dirtyCount = 0;
    private $batchSize = 50;

    public function __construct(DocumentManager $manager)
    {
        $this->manager = $manager;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->index($args->getEntity());
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->index($args->getEntity());
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->remove($args->getEntity());
    }

    public function onKernelTerminate()
    {
        $this->commit();
    }

    public function onConsoleTerminate()
    {
        $this->commit();
    }

    /**
     * This is a rather nasty hack to simulate a kernel.terminate event.
     * Support for a console.terminate event is coming (see
     * https://github.com/symfony/symfony/pull/3889), when it does, remove this
     * and register a proper console.terminate event handler.
     */
    public function __destruct()
    {
        // if we're in CLI context, commit now
        if (PHP_SAPI === 'cli') {
            $this->onConsoleTerminate();
        }
    }

    protected function index($entity)
    {
        if ($this->manager->supports($entity)) {
            $this->manager->index($entity);

            if (++$this->dirtyCount > $this->batchSize) {
                $this->commit();
            }
        }
    }

    protected function remove($entity)
    {
        if ($this->manager->supports($entity)) {
            $this->manager->remove($entity);

            if (++$this->dirtyCount > $this->batchSize) {
                $this->commit();
            }
        }
    }

    /**
     * Commits changes to document manager.
     */
    protected function commit()
    {
        $this->manager->commit();
        $this->dirtyCount = 0;
    }
}
