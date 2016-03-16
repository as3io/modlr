<?php

namespace As3\Modlr\Command\Metadata;

use As3\Modlr\Metadata\Cache\CacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears/warms the metadata cache.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class ClearCacheCommand extends Command
{
    /**
     * @var CacheWarmer
     */
    private $cacheWarmer;

    /**
     * Constructor.
     *
     * @param   CacheWarmer     $cacheWarmer
     */
    public function __construct(CacheWarmer $cacheWarmer)
    {
        parent::__construct();
        $this->cacheWarmer = $cacheWarmer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('modlr:metadata:cache:clear')
            ->setDescription('Clears/warms metadata for all models, or for a specific model.')
            ->addArgument('type', InputArgument::OPTIONAL, 'Specify the model type to clear.')
            ->addOption('no-warm', null, InputOption::VALUE_NONE, 'If set, warming will be disabled.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type') ?: null;
        $noWarm = $input->getOption('no-warm');

        $action = (true === $noWarm) ? 'Clearing' : 'Warming';
        $types = (null === $type) ? 'all types' : sprintf('model type "%s"', $type);

        $output->writeln(sprintf('<info>%s the metadata cache for %s</info>', $action, $types));

        $result = (true === $noWarm) ? $this->cacheWarmer->clear($type) : $this->cacheWarmer->warm($type);

        $output->writeln(sprintf('<info>Action complete for: %s</info>', implode(', ', $result)));
    }
}
