<?php

namespace FM\SearchBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PK\CommandExtraBundle\Command\Command as CommandExtra;

class CleanupCommand extends CommandExtra
{
    protected function configure()
    {
        $this
            ->setName('search:cleanup')
            ->setDescription('Cleans up Solr index by removing any documents that does not exist in the database.')
            ->addArgument('entity', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The entit[y|ies] to clean up. Can be any form that the entitymanager accepts.')
            ->isSingleProcessed()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cleanup($input->getArgument('entity'), $output);

        return 0;
    }

    /**
     * Removes non-existent properties from solr
     *
     * @param array           $entities
     * @param OutputInterface $output
     */
    protected function cleanup(array $entities = array(), OutputInterface $output)
    {
        $em = $this->getEntityManager();
        $dm = $this->get('fm_search.document_manager');

        $client = $dm->getClient();

        $toIds = function($doc) {
            return (int) $doc['id'];
        };

        $batchSize = 1000;

        foreach ($entities as $entity) {
            $meta = $em->getClassMetadata($entity);
            $repo = $em->getRepository($meta->getName());

            $schema    = $dm->getSchema($meta->getName());
            $endpoint  = $dm->getEndpoint($schema);
            $uniqueKey = $schema->getUniqueKeyField()->getName();
            $fields    = array_unique(array('id', $uniqueKey));

            $s = 0;
            while (true) {
                // create manual query to get all properties in the index
                $query = $client->createSelect();
                $query->setQuery('*');
                $query->setFields($fields);
                $query->setStart($s);
                $query->setRows($batchSize);

                $resultset = $client->execute($query, $endpoint);

                if ($resultset->count() < 1) {
                    break;
                }

                $s += $resultset->count();

                // map id's => unique keys
                $map = array();
                foreach ($resultset->getDocuments() as $document) {
                    $map[(int) $document['id']] = $document[$uniqueKey];
                }

                // check which id's we can actually find in the database
                $qb = $repo->createQueryBuilder('x');
                $qb->select('x.id');
                $qb->where('x.id IN (:ids)');
                $qb->setParameter('ids', array_keys($map));

                $result = $qb->getQuery()->getArrayResult();

                $ids = array_map($toIds, $resultset->getDocuments());
                $foundIds = array_map($toIds, $result);

                foreach (array_diff($ids, $foundIds) as $notFoundId) {
                    $output->writeln(sprintf('<fg=red>- %d', $notFoundId));
                    $dm->removeById($schema, $map[$notFoundId]);
                }

                $dm->commit();
                $em->clear();
            }
        }

        $output->write('Optimizing Solr cores... ');
        $dm->optimize();
        $output->writeln('<info>done!</info>');
    }
}
