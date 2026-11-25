<?php

namespace App\Exceptions;

use RuntimeException;

/** A user-facing booking problem (sold out, not on sale, bad quantity...). */
class BookingException extends RuntimeException {}
