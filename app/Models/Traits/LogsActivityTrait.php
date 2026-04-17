<?php

namespace App\Models\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTrait
{
    use LogsActivity;
	
	protected $ignoredTables = ['role_has_permissions'];
	
	public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y'); // Example: 09 Oct 2025
    }
	
	public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y'); // Example: 09 Oct 2025
    }

    /**
     * Configure Spatie activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {	
		if(!in_array($this->getTable(), $this->ignoredTables)){
			return LogOptions::defaults()
				->logAll() // log all attributes
				->useLogName(class_basename($this)) // log name = model name
				->setDescriptionForEvent(fn(string $eventName) => "{$this->getTable()} has been {$eventName}");
		}
    }
}
