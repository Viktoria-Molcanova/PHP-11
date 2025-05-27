<?php

use PHPUnit\Framework\TestCase;
use App\Telegram\TelegramApiImpl;

class TelegramApiImplTest extends TestCase
{
    public function testGetMessages()
    {
        $telegramApiMock = $this->getMockBuilder(TelegramApiImpl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedMessages = ['message1', 'message2'];
        $telegramApiMock->expects($this->once())
            ->method('getMessages')
            ->with(0)
            ->willReturn($expectedMessages);
        $actualMessages = $telegramApiMock->getMessages(0);

        $this->assertEquals($expectedMessages, $actualMessages);
    }
}