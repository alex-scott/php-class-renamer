<?php

require_once __DIR__ . '/../vendor/autoload.php';


class Test_FileProcessor extends PHPUnit_Framework_TestCase
{
    function testOk()
    {
        $changer = new \PhpClassRenamer\ClassNameChanger();
        $changer->addFixed('Am_Record', 'Am_Orm_Record');
        $changer->addFixed('Am_Table', 'Am_Orm_Table');
        $changer->addPattern('^Am_Record\b', 'Am_Orm');
        $changer->addToNs('Am_'); //convert all class names starting with Am_ to namespaces

        $tr = new \PhpClassRenamer\FileProcessor();
        $tr->addAction(new \PhpClassRenamer\Action\RenameClass($changer));
        $tr->addAction(new \PhpClassRenamer\Action\MoveClassToNs($changer));

        
    }
    
}