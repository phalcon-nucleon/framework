<?php

declare(strict_types=1);

namespace Neutrino\Process\Exception;

use RuntimeException;

/**
 * A process that cannot be started or used, and the parent of the process exceptions.
 */
class ProcessException extends RuntimeException {}
