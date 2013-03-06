<?php

namespace FM\SearchBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PK\CommandExtraBundle\Command\Command as CommandExtra;

class ImportCommand extends CommandExtra
{
    protected function configure()
    {
        $this
            ->setName('search:import')
            ->setDescription('Imports entities from database into search engine index')
            ->setDefinition(array(
                new InputArgument('entity', InputArgument::REQUIRED, 'The entity to index. Can be any form that the entitymanager accepts.'),
                new InputOption('where', null, InputOption::VALUE_OPTIONAL, 'Optional where clause to use in DQL (use "x" as root alias).'),
            ))
            ->preventLogging()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        $where = $input->getOption('where');

        $this->import($entity, $where, $output);

        return 0;
    }

    public function import($entity, $where = null, OutputInterface $output)
    {
        $em = $this->getEntityManager();
        $manager = $this->get('fm_search.document_manager');

        $i = 0;
        $batchSize = 50;

        $meta = $em->getClassMetadata($entity);
        $identifier = $meta->getSingleIdentifierFieldName();

        while (true) {

            $qb = $em->createQueryBuilder();
            $qb->select('x');
            $qb->from($entity, 'x');
            $qb->setFirstResult($i);
            $qb->setMaxResults($batchSize);
            $qb->orderBy(sprintf('x.%s', $identifier), 'ASC');

            if ($where) {
                $qb->where($where);
            }

            $result = $qb->getQuery()->iterate();

            $hasResults = false;

            foreach ($result as $row) {

                $hasResults = true;

                $i++;

                $object = $row[0];

                $id = $meta->getIdentifierValues($object);

                try {

                    $manager->index($object);
                    $output->writeln(sprintf('%s: %s', json_encode(array_values($id)), $this->entityToString($object)));

                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                }

                if (($i % $batchSize) === 0) {
                    $manager->commit();
                    $em->clear();
                }
            }

            if (!$hasResults) {
                break;
            }
        }

        // flush remaining updates
        $manager->commit();
        $manager->optimize();
    }

    protected function entityToString($entity)
    {
        return method_exists($entity, '__toString') ? (string) $entity : get_class($entity) . '@' . spl_object_hash($entity);
    }
}
