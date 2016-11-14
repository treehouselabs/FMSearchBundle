<?php

namespace FM\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteByQueryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('search:delete-by-query')
            ->setDescription('Deletes documents by query from search engine index')
            ->setDefinition(array(
                new InputArgument('schema', InputArgument::REQUIRED, 'The schema to use'),
                new InputArgument('query', InputArgument::REQUIRED, 'The Solr query to use, eg: <comment>"id:123"</comment>'),
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $input->getArgument('query');
        $schema = $input->getArgument('schema');

        $this->delete($query, $schema, $output);

        return 0;
    }

    protected function delete($query, $schema, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('fm_search.document_manager');
        $manager->removeByQuery($schema, $query, true);

        $output->writeln(sprintf(
            'Deleted documents from index <comment>%s</comment> with query <info>%s</info>',
            $schema,
            $query
        ));

        $manager->optimize();
    }
}
