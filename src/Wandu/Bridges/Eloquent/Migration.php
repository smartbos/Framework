<?php
namespace Wandu\Bridges\Eloquent;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Builder;
use Wandu\Database\Migrator\MigrationInterface;

abstract class Migration implements MigrationInterface
{
    /** @var string */
    protected $connection = 'default';

    /** @var \Illuminate\Database\Capsule\Manager */
    protected $manager;
    
    /**
     * @param \Illuminate\Database\Capsule\Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    public function up()
    {
        $this->migrate($this->manager->schema($this->connection));
    }

    public function down()
    {
        $this->rollback($this->manager->schema($this->connection));
    }

    /**
     * @param \Illuminate\Database\Schema\Builder $schema
     */
    abstract public function migrate(Builder $schema);

    /**
     * @param \Illuminate\Database\Schema\Builder $schema
     */
    abstract public function rollback(Builder $schema);
}
