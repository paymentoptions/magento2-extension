<?php


namespace Agtech\Paymentoptions\Test\Unit\Gateway\Command;


use Agtech\Paymentoptions\Gateway\Command\InitializeCommand;
use Agtech\Paymentoptions\Test\TestCase;
use Magento\Framework\DataObject;

class InitializeCommandTest extends TestCase
{
    public function testExecute_statusPendingPayment()
    {
        $command = new InitializeCommand();
        $commandSubject = [
            'stateObject' => new DataObject(),
        ];

        $command->execute($commandSubject);
        $this->assertEquals('pending_payment', $commandSubject['stateObject']->getStatus());
        $this->assertEquals('pending_payment', $commandSubject['stateObject']->getState());
    }
}