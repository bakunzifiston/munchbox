<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A checkout problem whose message is safe to show to the customer.
 */
class CheckoutException extends RuntimeException {}
