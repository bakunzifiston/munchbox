<?php

namespace App\Models\Concerns;

use App\Support\BusinessTime;
use Carbon\CarbonInterface;

/**
 * Dates are stored in the app (UTC) timezone but read back in the business
 * timezone, so every ->format() shows local Kigali time.
 */
trait UsesBusinessTimezone
{
    protected function asDateTime($value)
    {
        $date = parent::asDateTime($value);

        return $date instanceof CarbonInterface ? $date->setTimezone(BusinessTime::timezone()) : $date;
    }

    public function fromDateTime($value)
    {
        return empty($value) ? $value : parent::asDateTime($value)
            ->setTimezone(config('app.timezone'))
            ->format($this->getDateFormat());
    }
}
