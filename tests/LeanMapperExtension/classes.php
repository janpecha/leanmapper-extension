<?php

declare(strict_types=1);

namespace Dibi\Drivers {
	if (class_exists(MySqlDriver::class)) {
		class FakeMySqlDriver extends MySqlDriver
		{
			public function __construct()
			{
			}
		}
	}
}
