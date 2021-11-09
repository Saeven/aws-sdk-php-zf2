<?php

declare(strict_types=1);

namespace AwsModule\View\Exception;

use InvalidArgumentException;

/**
 * Exception thrown when an invalid CloudFront domain is passed
 */
class InvalidDomainNameException extends InvalidArgumentException
{
}
