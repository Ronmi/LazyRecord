<?php
namespace MetricApp\Model;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\TemplateSchema;

class MetricValueSchema extends TemplateSchema
{
    public function schema() 
    {
        $this->column('val')->double(5,3)->default(0)->notNull();

        $this->column('published_at')->timestamp()->isa('DateTime')->notNull();

        $this->column('unit')->varchar(3);
    }

    public function provideSchemas() {
        $schemas = array();

        foreach (['co2', 'o3', 'so'] as $t) {
            $schema = new self;
            $schema->table($t);
            $schemas[] = $schema;
        }

        return $schemas;
    }
}