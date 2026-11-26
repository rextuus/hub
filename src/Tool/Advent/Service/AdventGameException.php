<?php

declare(strict_types=1);

namespace App\Tool\Advent\Service;

/** Regelverstoß beim Spielen (Tür zu, schon beantwortet …); die Meldung ist für Teilnehmer gedacht. */
final class AdventGameException extends \RuntimeException
{
}
