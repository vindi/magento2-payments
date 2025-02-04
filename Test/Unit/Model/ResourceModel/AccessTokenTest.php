<?php

declare(strict_types=1);

namespace Vindi\VP\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vindi\VP\Model\ResourceModel\AccessToken;

class AccessTokenTest extends TestCase
{
    /**
     * @var AccessToken
     */
    private $accessToken;

    /**
     * @var MockObject|AdapterInterface
     */
    private $connectionMock;

    /**
     * @var MockObject|Context
     */
    private $contextMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $resourceMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceMock->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->contextMock = $this->createMock(Context::class);

        $this->contextMock->method('getResources')
            ->willReturn($resourceMock);

        $this->accessToken = $this->getMockBuilder(AccessToken::class)
            ->setConstructorArgs([$this->contextMock])
            ->onlyMethods(['getMainTable'])
            ->getMock();

        $this->accessToken->method('getMainTable')
            ->willReturn('vindi_vp_access_tokens');
    }

    public function testGetValidAccessToken()
    {
        $selectMock = $this->createMock(Select::class);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $selectMock->expects($this->once())
            ->method('from')
            ->with('vindi_vp_access_tokens', ['access_token'])
            ->willReturnSelf();

        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['access_token_expiration > ?', $this->greaterThan(0)],
                ['store_id = ?', '0']
            )
            ->willReturnSelf();

        $selectMock->expects($this->once())
            ->method('order')
            ->with('entity_id DESC')
            ->willReturnSelf();

        $selectMock->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn('valid_access_token');

        $result = $this->accessToken->getValidAccessToken(0);

        $this->assertEquals('valid_access_token', $result);
    }

    public function testGetLastRefreshToken()
    {
        $selectMock = $this->createMock(Select::class);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $selectMock->expects($this->once())
            ->method('from')
            ->with('vindi_vp_access_tokens', ['access_token', 'refresh_token'])
            ->willReturnSelf();

        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['refresh_token_expiration > ?', $this->greaterThan(0)],
                ['store_id = ?', '0']
            )
            ->willReturnSelf();

        $selectMock->expects($this->once())
            ->method('order')
            ->with('entity_id DESC')
            ->willReturnSelf();

        $selectMock->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($selectMock)
            ->willReturn([
                'access_token' => 'valid_access_token',
                'refresh_token' => 'valid_refresh_token',
            ]);

        $result = $this->accessToken->getLastRefreshToken(0);

        $this->assertEquals(
            [
                'access_token' => 'valid_access_token',
                'refresh_token' => 'valid_refresh_token',
            ],
            $result
        );
    }
}
