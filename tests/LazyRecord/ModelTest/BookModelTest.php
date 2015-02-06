<?php
use SQLBuilder\RawValue;

class BookModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 'TestApp\Model\BookSchema' );
    }

    public function testImmutableColumn()
    {
        $b = new \TestApp\Model\Book;
        // $b->autoReload = false;
        result_ok( $b->create(array( 'isbn' => '123123123' )) );
        $ret = $b->update(array('isbn'  => '456456' ));
        ok($ret->error , 'should not update immutable column' ); // should be failed.
        $b->delete();
    }


    /**
     * TODO: Should we validate the field ? think again.
     * @expectedException LazyRecord\DatabaseException
     */
    public function testUpdateUnknownColumn() {
        $b = new \TestApp\Model\Book;
        // Column not found: 1054 Unknown column 'name' in 'where clause'
        $b->find(array('name' => 'LoadOrCreateTest'));
    }

    public function testFlagHelper() {
        $b = new \TestApp\Model\Book;
        $b->create([ 'title' => 'Test Book' ]);

        $schema = $b->getSchema();
        ok($schema);

        $cA = $schema->getColumn('is_hot');
        $cB = $schema->getColumn('is_selled');
        ok($cA);
        ok($cB);

        $ret = $b->update([ 'is_hot' => true ]);
        result_ok( $ret );

        $ret = $b->update([ 'is_selled' => true ]);
        result_ok( $ret );

        $b->delete();
    }

    public function testTraitMethods() {
        $b = new \TestApp\Model\Book;
        $this->assertSame(['link1', 'link2'], $b->getLinks());
        $this->assertSame(['store1', 'store2'], $b->getStores());
    }

    public function testLoadOrCreate() {
        $results = array();
        $b = new \TestApp\Model\Book;

        $ret = $b->create(array( 'title' => 'Should Not Load This' ));
        result_ok( $ret );
        $results[] = $ret;

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        result_ok( $ret );
        $results[] = $ret;

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id, $b->id, 'is the same ID');
        $results[] = $ret;


        $b2 = new \TestApp\Model\Book;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id,$b2->id);
        $results[] = $ret;

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        result_ok($ret);
        ok($b2);
        ok($id != $b2->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3 = new \TestApp\Model\Book;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        result_ok($ret);
        ok($b3);
        ok($id != $b3->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3->delete();

        foreach( $results as $r ) {
            result_ok( \TestApp\Model\Book::delete($r->id)->execute() );
        }
    }

    public function testTypeConstraint()
    {
        $book = new \TestApp\Model\Book;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'publisher_id' => '""',  /* cast this to null or empty */
            // 'publisher_id' => NULL,  /* cast this to null or empty */
        ));


        // FIXME: in sqlite, it works, in pgsql, can not be cast to null
        // ok( $ret->success );
#          print_r($ret->sql);
#          print_r($ret->vars);
#          echo $ret->exception;
    }

    public function testRawSQL()
    {
        $n = new \TestApp\Model\Book;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );

        $ret = $n->update(array( 
            'view' => new RawValue('view + 1')
        ), array('reload' => 1));

        ok( $ret->success );
        is( 1 , $n->view );

        $n->update(array( 
            'view' => new RawValue('view + 3')
        ), array('reload' => 1));
        is( 4, $n->view );
    }

    public function testZeroInflator()
    {
        $b = new \TestApp\Model\Book;
        $ret = $b->create(array( 'title' => 'Create X' , 'view' => 0 ));
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );

        // test incremental
        $ret = $b->update(array( 'view'  => new RawValue('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 1,  $b->view );

        $ret = $b->update(array( 'view'  => new RawValue('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 2,  $b->view );

        $ret = $b->delete();
        result_ok($ret);
    }

    public function testGeneralInterface() 
    {
        $a = new \TestApp\Model\Book;
        ok($a);
        ok( $a->getQueryDriver('default') );
        ok( $a->getWriteQueryDriver() );
        ok( $a->getReadQueryDriver() );
    }
}

