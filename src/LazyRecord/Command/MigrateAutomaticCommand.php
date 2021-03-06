<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ServiceContainer;

class MigrateAutomaticCommand extends BaseCommand
{
    public function brief() { return 'Run upgrade automatically.'; }

    public function aliases() {
        return array('au', 'auto');
    }

    public function options($opts) 
    {
        parent::options($opts);
        $opts->add('no-drop-column', 'Do not drop column in automatic migration process');
    }

    public function execute() {
        $dsId = $this->getCurrentDataSourceId();
        $container = ServiceContainer::getInstance();
        $runner = new MigrationRunner($dsId);
        $runner->runUpgradeAutomatically($this->options);
        $this->logger->info('Done.');
    }

}


