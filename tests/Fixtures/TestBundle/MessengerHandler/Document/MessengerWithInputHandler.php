<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\MessengerHandler\Document;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\MessengerWithInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MessengerInput;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessengerWithInputHandler implements MessageHandlerInterface
{
    public function __invoke(MessengerInput $data)
    {
        $object = new MessengerWithInput();
        $object->name = 'test';
        $object->id = 1;

        return $object;
    }
}
