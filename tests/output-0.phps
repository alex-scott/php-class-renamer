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
        
    }
    
    function run(array $xx)
    {
        return null;
    }
}

namespace Am\Orm\Table;
class Xx { }

namespace Am\Orm\Record;
class Xx extends \Am\Orm\Record
{
    function run2(\Am\Orm\Record $r, array $x, $y)
    {
        
    }
}

namespace Am\Orm;
class Aa extends Table\Xx { }