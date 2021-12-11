<?php

namespace Hyperf\Seata\SqlParser\Antlr\MySql;

use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\ParserRuleContext;
use Hyperf\Seata\SqlParser\Antlr\MySql\Visit\StatementSqlVisitor;
use Hyperf\Seata\SqlParser\Antlr\SQLOperateRecognizerHolderFactory;
use Hyperf\Seata\SqlParser\Core\SQLRecognizerFactory;
use Hyperf\Seata\SqlParser\Antlr\MySql\Parser\MySqlLexer;
use Hyperf\Seata\SqlParser\Antlr\MySql\Parser\MySqlParser;

class AntlrMySQLRecognizerFactory implements SQLRecognizerFactory
{

    public function create(string $sql, string $dbType)
    {
        // TODO: 待看清楚这里的处理方式
        $lexer = new MySqlLexer(InputStream::fromString($sql));

        $tokenStream = new CommonTokenStream($lexer);

        $parser = new MySqlParser($tokenStream);

        $sqlStatementsContext = $parser->sqlStatements();

        $sqlStatementContexts = $sqlStatementsContext->sqlStatement();
//
        $recognizers = null;
        $recognizer = null;
//
        foreach ($sqlStatementContexts as $sqlStatementContext) {
            $visitor = new StatementSqlVisitor();

            $originalSQL = $visitor->visit($sqlStatementContext);

            $recognizerHolder = SQLOperateRecognizerHolderFactory::getSQLRecognizerHolder(strtolower($dbType));

            if ($sqlStatementContext->dmlStatement()->updateStatement() != null) {
                $recognizer = $recognizerHolder->getUpdateRecognizer($originalSQL);
            } elseif ($sqlStatementContext->dmlStatement()->insertStatement() != null) {
                $recognizer = $recognizerHolder->getInsertRecognizer($originalSQL);
            }elseif ($sqlStatementContext->dmlStatement()->deleteStatement() != null) {
                $recognizer = $recognizerHolder->getDeleteRecognizer($originalSQL);
            } elseif ($sqlStatementContext->dmlStatement()->selectStatement() != null) {
                var_dump('--select');
                $recognizer = $recognizerHolder->getSelectForUpdateRecognizer($originalSQL);
            }

            if (! isset($recognizers)) {
                $recognizers = [];
            }

            if (isset($recognizers) && ! empty($recognizer)) {
                $recognizers[] = $recognizer;
            }
        }

        return $recognizers;
    }
}