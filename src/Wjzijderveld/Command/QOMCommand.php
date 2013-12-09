<?php
/**
 * Created at 30/11/13 11:11
 */

namespace Wjzijderveld\Command;


use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\SessionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QOMCommand extends Command
{
    public function configure()
    {
        $this->setName('tutorial:qom');
        $this->setDescription('Query the repository');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadFixtures();

        $session = $this->getSession();

        /** @var QueryObjectModelFactoryInterface $qomFactory */
        $qomFactory = $session->getWorkspace()->getQueryManager()->getQOMFactory();

        $source = $qomFactory->selector('news', 'nt:unstructured');
        $titleColumn = $qomFactory->column('news', 'title', 'title');
        $qom = $qomFactory->createQuery(
            $source,
            $qomFactory->descendantNode('news', '/queryExamples/news'),
            array(),
            array($titleColumn)
        );
        $result = $qom->execute();
        $output->writeln(sprintf('Found <info>%d</info> news items', count($result->getRows())));

        foreach ($result->getRows() as $newsItem) {
            $output->writeln(sprintf('Title: <info>%s</info>', $newsItem->getValue('title')));
        }

        $source = $qomFactory->selector('news', 'nt:unstructured');
        $qom = $qomFactory->createQuery(
            $source,
            $qomFactory->andConstraint(
                $qomFactory->descendantNode('news', '/queryExamples/news'),
                $qomFactory->comparison(
                    $qomFactory->propertyValue('news', 'jcr:author'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                    $qomFactory->literal('foo')
                )
            )
        );

        $result = $qom->execute();
        $output->writeln(sprintf('Found <info>%d</info> news items from author <info>foo</info>', count($result->getRows())));

        $qom = $qomFactory->createQuery(
            $source,
            $qomFactory->andConstraint(
                $qomFactory->descendantNode('news', '/queryExamples/news'),
                $qomFactory->andConstraint(
                    $qomFactory->comparison(
                        $qomFactory->propertyValue('news', 'jcr:author'),
                        QueryObjectModelConstantsInterface::JCR_OPERATOR_NOT_EQUAL_TO,
                        $qomFactory->literal('bar')
                    ),
                    $qomFactory->orConstraint(
                        $qomFactory->notConstraint(
                            $qomFactory->propertyExistence('news', 'categories')
                        ),
                        $qomFactory->comparison(
                            $qomFactory->propertyValue('news', 'categories'),
                            QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                            $qomFactory->literal('foo')
                        )
                    )
                )
            ),
            array(
                $qomFactory->descending($qomFactory->nodeName('news'))
            )
        );

        $rows = $qom->execute()->getRows();
        $output->writeln(sprintf('Found <info>%d</info> results with the following statement: <comment>%s</comment>', count($rows), PHP_EOL . $qom->getStatement()));
        foreach ($rows as $row) {
            $output->writeln(sprintf('Path: <info>%s</info>', $row->getPath()));
        }

    }

    public function loadFixtures()
    {
        $rootNode = $this->getSession()->getRootNode();

        if ($rootNode->hasNode('queryExamples')) {
            $rootNode->getNode('queryExamples')->remove();
        }
        $this->getSession()->importXML('/', __DIR__ . '/../Resources/data/fixtures.xml', ImportUUIDBehaviorInterface::IMPORT_UUID_CREATE_NEW);

        /*
        $queryExamplesNode = $rootNode->addNode('queryExamples');

        $news = $queryExamplesNode->addNode('news');


        $newsItem1 = $news->addNode('item1');
        $newsItem1->setProperty('jcr:created', new \DateTime('-20 DAYS 13:37'));
        $newsItem1->setProperty('jcr:author', 'foo');

        $newsItem2 = $news->addNode('item2');
        $newsItem2->setProperty('jcr:created', new \DateTime('-18 DAYS 23:18'));
        $newsItem2->setProperty('jcr:author', 'foo');
        $newsItem2->setProperty('categories', array('foo', 'bar'));

        $newsItem3 = $news->addNode('item3');
        $newsItem3->setProperty('jcr:created', new \DateTime('-15 DAYS 15:46'));
        $newsItem3->setProperty('jcr:author', 'bar');
        $newsItem3->setProperty('categories', array('foo'));

        $newsItem4 = $news->addNode('item4');
        $newsItem4->setProperty('jcr:created', new \DateTime('-13 DAYS 09:18'));
        $newsItem4->setProperty('jcr:author', 'bar');
        $newsItem4->setProperty('categories', array('bar'));

        */
        $this->getSession()->save();
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->getHelper('phpcr')->getSession();
    }
} 