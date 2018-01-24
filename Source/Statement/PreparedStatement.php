<?php

namespace OS\DatabaseAccessLayer\Statement;


use OS\DatabaseAccessLayer\Statement\Exception\MissingParameterValueException;
use OS\DatabaseAccessLayer\Statement\Exception\StatementExecutionException;

interface PreparedStatement
{
    /**
     * @param ParameterContainerInterface|null $container
     *
     * @return Result
     * @throws MissingParameterValueException
     * @throws StatementExecutionException
     */
    public function execute(ParameterContainerInterface $container = null): Result;

    /**
     * Returns the stored sql query
     *
     * If a parameter container is supplied, placeholders in the query will be replaced.
     *
     * @param ParameterContainerInterface|null $parameterContainer
     *
     * @return string
     * @throws MissingParameterValueException
     */
    public function getDebugData(ParameterContainerInterface $parameterContainer = null): string;
}
