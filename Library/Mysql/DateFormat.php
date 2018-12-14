<?php
namespace Eckinox\Library\Mysql;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DateFormatFunction ::= "DATE_FORMAT" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 */
class DateFormat extends FunctionNode
{
	const FUNCTION_NAME = 'DATE_FORMAT';
    
	/**
	 * @var \Doctrine\ORM\Query\AST\Node
	 */
    public $date = null;
    
	/**
	 * @var \Doctrine\ORM\Query\AST\Node
	 */
    public $format = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); 
        $parser->match(Lexer::T_OPEN_PARENTHESIS); 
        $this->date = $parser->ArithmeticPrimary();
        
        $parser->match(Lexer::T_COMMA);
        $this->format = $parser->ArithmeticPrimary();
        
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); 
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'DATEDIFF(' .
            $this->date->dispatch($sqlWalker) . ', ' .
            $this->format->dispatch($sqlWalker) .
        ')'; 
    }
}