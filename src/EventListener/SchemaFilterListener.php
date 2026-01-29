<?php

namespace CircuitBreakerBundle\EventListener;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractNamedObject;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

final class SchemaFilterListener
{
    private bool $enabled = false;

    public function __construct(
        private readonly string $tableName
    ) {
    }

    public function __invoke(AbstractAsset|AbstractNamedObject|string $asset): bool
    {
        if (!$this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractAsset) {
            $asset = $asset instanceof AbstractNamedObject
                ? $asset->getObjectName()->toString()
                : $asset->getName();
        }

        return $asset !== $this->tableName;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (class_exists(ValidateSchemaCommand::class) && $command instanceof ValidateSchemaCommand) {
            $this->enabled = true;
        }

        if (class_exists(UpdateCommand::class) && $command instanceof UpdateCommand) {
            $this->enabled = true;
        }

        if (class_exists(DiffCommand::class) && $command instanceof DiffCommand) {
            $this->enabled = true;
        }
    }
}
