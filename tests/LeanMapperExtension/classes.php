<?php

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
