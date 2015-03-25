<?php
namespace Am\Orm;

/**
 * a test class
 * Am_Record
 */
class Record 
{
    /*
     * 
     */
    function __construct(Record\Xx $xx) {
        $b = new self;
        $c = new Record();
        throw new \Exception;
    }
    
    function run(array $xx)
    {
        $x = (new Table\Xx);
        return null;
    }
}

namespace Am;
interface Grid {}

namespace Am\Orm\Table;
class Xx { }

namespace Am\Orm\Record;
class Xx extends \Am\Orm\Record
{
    /**
     * @param $x \Am\Orm\Record
     * @param \Am\Grid
     * @return Xx
     */    
    function run2(\Am\Orm\Record $r, array $x, $y, \Iterator $it)
    {
        $c = new $this->x;
    }
}

namespace Am\Orm;
class Aa extends Table\Xx { }

namespace Am\Oo\My;
abstract class Grid implements \Am\Grid {}