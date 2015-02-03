<?php
namespace PhpClassRenamer;

require_once __DIR__ . '/../vendor/autoload.php';

class Test_FileProcessor extends \PHPUnit_Framework_TestCase
{
    function testOk()
    {
        $changer = new ClassNameChanger();
        $changer->addFixed('Am_Record', 'Am_Orm_Record');
        $changer->addFixed('Am_Table', 'Am_Orm_Table');
        $changer->addPattern('^Am_Record(_|$)', 'Am_Orm_Record$1');
        $changer->addToNs('Am_'); //convert all class names starting with Am_ to namespaces

        $tr = new FileProcessor();
        $tr->addAction(new Action\RenameClass($changer));
        $tr->addAction(new Action\MoveClassToNs($changer));
        ini_set('display_errors', true);
        set_time_limit(1);
        error_reporting(E_ALL | E_NOTICE);
        
        $output = $tr->process(file_get_contents(__DIR__ . '/input-0.phps'), 'xx', 'yy');
        $this->assertEquals(file_get_contents(__DIR__ . '/output-0.phps'), $output);
    }
    
}