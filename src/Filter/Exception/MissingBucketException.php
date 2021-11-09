<?php

declare(strict_types=1);

namespace AwsModule\Filter\Exception;

use InvalidArgumentException;

/**
 * Exception thrown when no bucket is passed
 */
class MissingBucketException extends InvalidArgumentException
{
}
