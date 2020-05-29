<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Exception;

use Throwable;

/**
 * The implementing throwable message can be displayed to users
 */
interface PublicMessageException extends Throwable
{
}
