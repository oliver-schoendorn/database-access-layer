<?php

namespace OS\DatabaseAccessLayer\Test\UnitTest\Driver\MysqlPdo;


use OS\DatabaseAccessLayer\Driver;
use OS\DatabaseAccessLayer\Driver\MysqlPdo\Specification;
use OS\DatabaseAccessLayer\Test\Helper\ObjectInspector;
use OS\DatabaseAccessLayer\Test\TestCase\UnitTestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

class SpecificationTest extends UnitTestCase
{
    /**
     * @return Specification
     * @throws Exception
     */
    private function getSpecificationStub(): Specification
    {
        /** @var Driver|object $driver */
        $driver = $this->getMockBuilder(Driver::class)->getMockForAbstractClass();
        return new Driver\MysqlPdo\Specification($driver);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorWillStoreDriverReference()
    {
        /** @var Driver|MockObject $driver */
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new Driver\MysqlPdo\Specification($driver);
        verify((new ObjectInspector($subject))->getValue('driver'))->same($driver);
    }

    /**
     * @throws Exception
     */
    public function testGettersWillReturnStrings()
    {
        $subject = $this->getSpecificationStub();

        verify($subject->getIdentifierQuote())->internalType('string');
        verify($subject->getIdentifierSeparator())->internalType('string');
        verify($subject->getValueQuote())->internalType('string');
    }

    /**
     * @throws Exception
     */
    public function testQuoteIdentifierWillWrapIdentifier()
    {
        $subject = $this->getSpecificationStub();
        $identifier = 'foo';

        $response = $subject->quoteIdentifier($identifier);
        verify($response)->contains($identifier);
        verify($response)->regExp('/^.{1}\w+.{1}$/');
    }

    /**
     * @throws Exception
     */
    public function testQuoteIdentifierWillNotWrapPlaceholder()
    {
        $subject = $this->getSpecificationStub();
        $response = $subject->quoteIdentifier('*');
        verify($response)->equals('*');
    }

    public function identifierChainDataProvider()
    {
        return [
            'array' => [ [ 'foo', '*', 'bar' ] ],
            'string' => [ 'foo.bar.*' ]
        ];
    }

    /**
     * @param array|string $identifierChain
     *
     * @throws Exception
     *
     * @dataProvider identifierChainDataProvider
     */
    public function testQuoteIdentifierChain($identifierChain)
    {
        $subject = $this->getSpecificationStub();
        $response = $subject->quoteIdentifierChain($identifierChain);
        verify($response)->internalType('string');
        verify($response)->regExp('/^(?:.{1}\w+.{1}|\*)\.(?:.{1}\w+.{1}|\*)\.(?:.{1}\w+.{1}|\*)$/');
    }

    /**
     *
     */
    public function testQuoteValueWillDelegateEscapingToDriver()
    {
        $expectedValue = 'FooBar';

        /** @var Driver|MockObject $driverMock */
        $driverMock = $this->getMockBuilder(Driver::class)
            ->setMethods([ 'escape' ])
            ->getMockForAbstractClass();

        $driverMock->expects($this::once())
            ->method('escape')
            ->with($expectedValue)
            ->willReturnArgument(0);

        $subject = new Specification($driverMock);
        $response = $subject->quoteValue($expectedValue);
        verify($response)->equals($expectedValue);
    }

    public function testQuoteTrustedValueWillWrapValueWithQuotes()
    {
        $expectedValue = 'FooBar';

        /** @var Driver|MockObject $driverMock */
        $driverMock = $this->getMockBuilder(Driver::class)
            ->setMethods([ 'escape' ])
            ->getMockForAbstractClass();

        $driverMock->expects($this::never())->method('escape');

        $subject = new Specification($driverMock);
        $response = $subject->quoteTrustedValue($expectedValue);
        verify($response)->contains($expectedValue);
        verify($response)->regExp('/^.{1}\w+.{1}$/');
    }
}
