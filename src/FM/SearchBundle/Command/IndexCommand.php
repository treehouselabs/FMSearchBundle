<?php

namespace FM\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('search:index')
            ->setDescription('Indexes entities from database into search engine')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The entit[y|ies] to index. Can be any form that the entitymanager accepts.'
            )
            ->addOption(
                'where',
                null,
                InputOption::VALUE_OPTIONAL,
                'Optional where clause to use in DQL (use "x" as root alias).'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = $input->getArgument('entity');
        $where    = $input->getOption('where');

        $this->index($entities, $where, $output);

        return 0;
    }

    protected function index(array $entities, $where = null, OutputInterface $output)
    {
        $em      = $this->getContainer()->get('doctrine')->getManager();
        $manager = $this->getContainer()->get('fm_search.document_manager');

        $i         = 0;
        $batchSize = 50;

        foreach ($entities as $entity) {
            $meta       = $em->getClassMetadata($entity);
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
                        $output->writeln(
                            sprintf('%s: %s', json_encode(array_values($id)), $this->entityToString($object))
                        );
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
