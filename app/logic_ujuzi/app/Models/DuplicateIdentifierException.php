<?php

namespace App\Models;

/** An email or phone number another account already uses. */
class DuplicateIdentifierException extends \DomainException
{
}
