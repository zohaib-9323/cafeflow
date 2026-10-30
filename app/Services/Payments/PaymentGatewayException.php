<?php

namespace App\Services\Payments;

use RuntimeException;

/** The gateway could not be reached or rejected the request. */
class PaymentGatewayException extends RuntimeException {}
