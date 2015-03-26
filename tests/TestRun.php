<?php
namespace PhpClassRenamer;

require_once __DIR__ . '/../vendor/autoload.php';

class Test_FileProcessor extends \PHPUnit_Framework_TestCase
{
    
    function testClassNameChanger()
    {
        $c = new ClassNameChanger();
        $c->addFixed('Am_Record', 'Am_Orm');
        $c->addPattern('^Am_Oo', 'Am_Pp');
        $c->addToNs('Am_');
        $c->moveExtends('Am_Rr', '\\Am\\Xx\\');
        
        $this->assertEquals('\\Am\\Orm', $c->replace('Am_Record'));
        $this->assertEquals('\\Am\\Orm\\Xx', $c->replace('Am_Orm_Xx'));
        $this->assertEquals('\\Am\\Pp\\Xx', $c->replace('Am_Pp_Xx'));
        $this->assertEquals('\\Am\\Xx\\Cc', $c->replace('Cc', 'Am_Rr'));
        $this->assertEquals('\\Am\\Xx\\Cc', $c->replace('Cc')); // it is remembered
    }
    
    function testParseFunctionArgs()
    {
        $source = file_get_contents(__DIR__ . '/input-1.phps');
        $ts = new TokenStream($source, '1');
        //file_put_contents(__DIR__ . '/output-1.txt', $ts->dumpTokens());
        $this->assertEquals(file_get_contents(__DIR__ . '/output-1.txt'), $ts->dumpTokens());
        $this->assertEquals(6, $ts->findPrevToken(T_FUNCTION, 13));
        $this->assertEquals(null, $ts->findPrevToken(T_FUNCTION, 13, 1)); // limit works
        $this->assertEquals(68, $ts->findPrevToken(T_WHITESPACE, 68, 1));
    }
    
    function testStoreFiles()
    {
        $exp = file_get_contents(__DIR__ . '/output-2.txt');
        foreach (preg_split('/(==(.+?)==)\n/ms', $exp, -1, PREG_SPLIT_DELIM_CAPTURE) as $k => $split)
        {
            if ($k == 0) continue;
            if (($k % 3) == 2)
                $fn = $split;
            if (($k % 3) == 0)
                $expected[$fn] = $split;
        }
        
        $source = file_get_contents(__DIR__ . '/input-2.phps');
        
        $ts = new TokenStream($source, '2');
        $files = $ts->getFilesAndContent();
      //  print_r($files);
        //$this->assertEquals($expected, $files);
    }
    
    function testStoreSimpleFile()
    {
        $source = '<' . <<<P
?php
namespace Aa\Bb;
class Cc {}
P;
        $ts = new TokenStream($source, '2');
        $files = $ts->getFilesAndContent();
        $this->assertEquals($files['Aa/Bb/Cc.php'], '<' . '?php
namespace Aa\Bb;
class Cc {}');
    }
    function testStore2Class()
    {
        $source = '<' . <<<P
?php
namespace Aa\Bb;
abstract class Cc {}
/**
 the doc block */
class Dd { abstract function xx(); }
// Oo
class Ee {}
P;
        $ts = new TokenStream($source, '2');
        $files = $ts->getFilesAndContent();
        $this->assertEquals('<' . '?php
namespace Aa\Bb;
abstract class Cc {}', $files['Aa/Bb/Cc.php']);
        $this->assertEquals('<' . '?php
namespace Aa\Bb;


/**
 the doc block */
class Dd { abstract function xx(); }', $files['Aa/Bb/Dd.php']);
        $this->assertEquals('<' . '?php
namespace Aa\Bb;


// Oo
class Ee {}', $files['Aa/Bb/Ee.php']);
    }
    
    function testOk()
    {
        $changer = new ClassNameChanger();
        $changer->addFixed('Am_Record', 'Am_Orm_Record');
        $changer->addFixed('Am_Table', 'Am_Orm_Table');
        $changer->addPattern('^Am_Record(_|$)', 'Am_Orm_Record$1');
        $changer->addPattern('^Am_Table(_|$)', 'Am_Orm_Table$1');
        $changer->addToNs('Am_'); //convert all class names starting with Am_ to namespaces
        
        $changer->moveExtends('Am_Table', '\\Am\\Orm\\');
        $changer->moveExtends('Am_Table_WithData', '\\Am\\Orm\\');

        $tr = new FileProcessor();
        $tr->addAction(new Action\RenameClass($changer));
        $tr->addAction(new Action\RenameClassRefs($changer));
        $tr->addAction(new Action\MoveClassToNs($changer), 2);
        $tr->addAction(new Action\FixDocBlocks($changer), 3);
        $tr->addAction(new Action\FixStringClassNames($changer), 3)
            ->replaceStringClassStartingWith('Am_');
        
        $fn = __DIR__ . '/input-0.phps';
        $tr->addFile($fn, 'xx');
        $tr->addFile(__DIR__ . '/input-1.phps', 'yy');
        $tr->process();
        $output = $tr->getFileContent($fn);
        
        //file_put_contents(__DIR__ . '/output-0.phps', $output);
        $this->assertEquals(file_get_contents(__DIR__ . '/output-0.phps'), $output);
        $this->assertEquals('Am/Orm/Record.php', $tr->getFileName($fn));
    }
    
}