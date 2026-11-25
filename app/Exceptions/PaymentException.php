<?php

namespace App\Exceptions;

use RuntimeException;

/** A user-facing payment problem (declined card, expired reservation...). */
class PaymentException extends RuntimeException {}
