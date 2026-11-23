<?php

namespace App\Domain\Payments\Webhooks;

use RuntimeException;

class InvalidWebhookSignature extends RuntimeException {}
